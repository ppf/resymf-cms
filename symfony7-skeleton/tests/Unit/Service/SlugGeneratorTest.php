<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Page;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Unit tests for SlugGenerator service.
 */
class SlugGeneratorTest extends TestCase
{
    private SlugGenerator $slugGenerator;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $slugger = new AsciiSlugger();

        $this->slugGenerator = new SlugGenerator($slugger, $this->entityManager);
    }

    public function testSlugify(): void
    {
        $result = $this->slugGenerator->slugify('Hello World!');
        $this->assertSame('hello-world', $result);
    }

    public function testSlugifyWithSpecialCharacters(): void
    {
        $result = $this->slugGenerator->slugify('Héllo Wörld & Friends');
        $this->assertSame('hello-world-friends', $result);
    }

    public function testSlugifyTruncatesLongText(): void
    {
        $longText = str_repeat('a', 300);
        $result = $this->slugGenerator->slugify($longText, 50);
        $this->assertLessThanOrEqual(50, strlen($result));
    }

    public function testIsValidSlug(): void
    {
        $this->assertTrue($this->slugGenerator->isValidSlug('valid-slug-123'));
        $this->assertTrue($this->slugGenerator->isValidSlug('hello'));
        $this->assertTrue($this->slugGenerator->isValidSlug('hello-world'));
        $this->assertTrue($this->slugGenerator->isValidSlug('test-123'));
    }

    public function testIsInvalidSlug(): void
    {
        $this->assertFalse($this->slugGenerator->isValidSlug('Invalid Slug'));
        $this->assertFalse($this->slugGenerator->isValidSlug('slug_with_underscore'));
        $this->assertFalse($this->slugGenerator->isValidSlug('-starts-with-hyphen'));
        $this->assertFalse($this->slugGenerator->isValidSlug('ends-with-hyphen-'));
        $this->assertFalse($this->slugGenerator->isValidSlug('has--double--hyphen'));
        $this->assertFalse($this->slugGenerator->isValidSlug(''));
    }

    public function testGenerateFromParts(): void
    {
        $parts = ['Category', 'SubCategory', 'Page Title'];
        $result = $this->slugGenerator->generateFromParts($parts);
        $this->assertSame('category-subcategory-page-title', $result);
    }

    public function testGenerateFromPartsWithCustomSeparator(): void
    {
        $parts = ['Part 1', 'Part 2'];
        $result = $this->slugGenerator->generateFromParts($parts, '_');
        $this->assertSame('part-1-part-2', $result); // Still slugifies to hyphens
    }

    public function testGenerateFromPartsIgnoresEmptyParts(): void
    {
        $parts = ['Hello', '', 'World', null];
        $result = $this->slugGenerator->generateFromParts($parts);
        $this->assertSame('hello-world', $result);
    }
}
