<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Asset\Packages;
use Symfony\Component\AssetMapper\ImportMap\ImportMapRenderer;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Lightweight Vite helpers with graceful fallback to the existing importmap pipeline.
 *
 * This avoids a hard dependency on symfonycasts/vite-bundle so we can keep the
 * application working while Vite is being introduced.
 */
class ViteExtension extends AbstractExtension
{
    private ?array $manifest = null;

    public function __construct(
        private readonly Packages $packages,
        #[Autowire(service: 'asset_mapper.importmap.renderer', lazy: true)]
        private readonly ?ImportMapRenderer $importMapRenderer = null,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'renderScripts'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'renderLinks'], ['is_safe' => ['html']]),
        ];
    }

    public function renderScripts(string $entryName): string
    {
        if ($entry = $this->findManifestEntry($entryName)) {
            $tags = [];

            foreach ($entry['imports'] ?? [] as $import) {
                if ($dependency = $this->manifest[$import] ?? null) {
                    $tags[] = sprintf('<link rel="modulepreload" href="%s">', $this->assetUrl('build/' . $dependency['file']));
                }
            }

            $tags[] = sprintf('<script type="module" src="%s"></script>', $this->assetUrl('build/' . $entry['file']));

            return implode("\n", $tags);
        }

        // Fallbacks so the app keeps working before Vite is wired up.
        if ('app' === $entryName && $this->importMapRenderer) {
            return $this->importMapRenderer->render('app');
        }

        // Admin/CMS were loaded via asset() previously; mirror that.
        if (\in_array($entryName, ['admin', 'cms'], true)) {
            return sprintf('<script type="module" src="%s"></script>', $this->assetUrl($entryName . '.js'));
        }

        // Unknown entry and no manifest: render nothing to avoid 404s.
        return '';
    }

    public function renderLinks(string $entryName): string
    {
        if ($entry = $this->findManifestEntry($entryName)) {
            $links = [];
            foreach ($entry['css'] ?? [] as $cssFile) {
                $links[] = sprintf('<link rel="stylesheet" href="%s">', $this->assetUrl('build/' . $cssFile));
            }

            // If the Vite entry has no CSS artifacts, fall back to the raw stylesheet.
            if (!$links) {
                if ($cssFallback = $this->cssFallback($entryName)) {
                    $links[] = $cssFallback;
                }
            }

            return implode("\n", $links);
        }

        // Keep the previous CSS links working without Vite.
        if ($cssFallback = $this->cssFallback($entryName)) {
            return $cssFallback;
        }

        return '';
    }

    private function cssFallback(string $entryName): ?string
    {
        $cssPath = sprintf('styles/%s.css', $entryName);
        if (is_file($this->projectDir . '/assets/' . $cssPath)) {
            return sprintf('<link rel="stylesheet" href="%s">', $this->assetUrl($cssPath));
        }

        return null;
    }

    private function assetUrl(string $path): string
    {
        return $this->packages->getUrl($path);
    }

    private function findManifestEntry(string $entryName): ?array
    {
        $manifest = $this->getManifest();
        if (!$manifest) {
            return null;
        }

        $candidates = [
            $entryName,
            $entryName . '.js',
            'assets/' . $entryName . '.js',
        ];

        foreach ($manifest as $key => $entry) {
            if (in_array($key, $candidates, true) || ($entry['name'] ?? null) === $entryName) {
                return $entry;
            }
        }

        return null;
    }

    private function getManifest(): ?array
    {
        if (null !== $this->manifest) {
            return $this->manifest;
        }

        $candidates = [
            $this->projectDir . '/public/build/manifest.json',
            $this->projectDir . '/public/build/.vite/manifest.json',
        ];

        foreach ($candidates as $manifestPath) {
            if (!is_file($manifestPath)) {
                continue;
            }

            $contents = file_get_contents($manifestPath);
            if (false === $contents) {
                continue;
            }

            $data = json_decode($contents, true);
            if (\is_array($data)) {
                return $this->manifest = $data;
            }
        }

        return $this->manifest = null;
    }
}
