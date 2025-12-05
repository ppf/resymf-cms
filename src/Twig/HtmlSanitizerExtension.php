<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for HTML sanitization.
 *
 * Provides a safe way to render user-generated HTML content
 * by stripping dangerous tags and attributes (XSS prevention).
 */
class HtmlSanitizerExtension extends AbstractExtension
{
    public function __construct(
        private readonly HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('sanitize_html', [$this, 'sanitizeHtml'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Sanitize HTML content, removing dangerous tags and attributes.
     */
    public function sanitizeHtml(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return $this->htmlSanitizer->sanitize($html);
    }
}
