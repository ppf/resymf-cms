<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Service for generating unique slugs for entities.
 *
 * This service ensures that slugs are URL-friendly and unique across
 * the specified entity repository. It handles collision detection and
 * automatic suffix generation.
 */
class SlugGenerator
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Generate a unique slug from a given string.
     *
     * @param string $text The text to slugify
     * @param string $entityClass The entity class to check uniqueness against
     * @param int|null $excludeId Optional entity ID to exclude from uniqueness check (for updates)
     * @param string $slugField The field name that stores the slug (default: 'slug')
     * @param int $maxLength Maximum length of the generated slug (default: 255)
     *
     * @return string The generated unique slug
     */
    public function generateUniqueSlug(
        string $text,
        string $entityClass,
        ?int $excludeId = null,
        string $slugField = 'slug',
        int $maxLength = 255,
    ): string {
        // Generate base slug
        $baseSlug = $this->slugger->slug($text)->lower()->toString();

        // Truncate if needed (leave room for suffix)
        if (strlen($baseSlug) > $maxLength - 10) {
            $baseSlug = substr($baseSlug, 0, $maxLength - 10);
        }

        $slug = $baseSlug;
        $counter = 1;

        // Check for uniqueness and append counter if needed
        while ($this->slugExists($slug, $entityClass, $excludeId, $slugField)) {
            $suffix = '-' . $counter;
            $maxBaseLength = $maxLength - strlen($suffix);
            $truncatedBase = substr($baseSlug, 0, $maxBaseLength);
            $slug = $truncatedBase . $suffix;
            ++$counter;

            // Safety check to prevent infinite loops
            if ($counter > 1000) {
                // Fallback: add timestamp
                $slug = $baseSlug . '-' . time();

                break;
            }
        }

        return $slug;
    }

    /**
     * Generate a simple slug without uniqueness check.
     *
     * Useful for generating slugs that will be checked manually
     * or for non-unique fields.
     *
     * @param string $text The text to slugify
     * @param int $maxLength Maximum length of the generated slug
     *
     * @return string The generated slug
     */
    public function slugify(string $text, int $maxLength = 255): string
    {
        $slug = $this->slugger->slug($text)->lower()->toString();

        if (strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
        }

        return $slug;
    }

    /**
     * Validate if a slug is in the correct format.
     *
     * @param string $slug The slug to validate
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidSlug(string $slug): bool
    {
        // Slug should only contain lowercase letters, numbers, and hyphens
        // Should not start or end with a hyphen
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) === 1;
    }

    /**
     * Generate a slug from multiple parts with a separator.
     *
     * Useful for creating slugs from multiple fields (e.g., category + title).
     *
     * @param array<string> $parts The parts to combine
     * @param string $separator The separator to use between parts
     *
     * @return string The generated slug
     */
    public function generateFromParts(array $parts, string $separator = '-'): string
    {
        $cleanParts = array_filter($parts, fn ($part) => !empty($part));
        $combined = implode(' ' . $separator . ' ', $cleanParts);

        return $this->slugify($combined);
    }

    /**
     * Check if a slug already exists in the database.
     *
     * @param string $slug The slug to check
     * @param string $entityClass The entity class to check against
     * @param int|null $excludeId Optional entity ID to exclude from the check
     * @param string $slugField The field name that stores the slug
     *
     * @return bool True if the slug exists, false otherwise
     */
    private function slugExists(
        string $slug,
        string $entityClass,
        ?int $excludeId,
        string $slugField,
    ): bool {
        $repository = $this->entityManager->getRepository($entityClass);

        $qb = $repository->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where("e.$slugField = :slug")
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('e.id != :excludeId')
                ->setParameter('excludeId', $excludeId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}
