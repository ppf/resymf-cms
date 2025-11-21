<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Page;
use App\Entity\User;
use App\Security\Voter\PageVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Simplified unit tests for PageVoter.
 *
 * Tests authorization rules for page access using real Page entities.
 */
class PageVoterSimplifiedTest extends TestCase
{
    private PageVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new PageVoter();
    }

    // Test VIEW permission

    public function testPublicCanViewPublishedPage(): void
    {
        $page = $this->createPublishedPage();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $page, [PageVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testPublicCannotViewDraftPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, $page, [PageVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testAdminCanViewDraftPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $admin = $this->createUser(2, ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($token, $page, [PageVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAuthorCanViewOwnDraftPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($author);

        $result = $this->voter->vote($token, $page, [PageVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAuthorCannotViewOthersDraftPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $otherUser = $this->createUser(2);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $page, [PageVoter::VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // Test EDIT permission

    public function testAdminCanEditAnyPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $admin = $this->createUser(2, ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($token, $page, [PageVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAuthorCanEditOwnPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($author);

        $result = $this->voter->vote($token, $page, [PageVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAuthorCannotEditOthersPage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $otherUser = $this->createUser(2);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        $result = $this->voter->vote($token, $page, [PageVoter::EDIT]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // Test DELETE permission

    public function testAdminCanDeleteNonHomepage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);
        $page->setIsHomepage(false);

        $admin = $this->createUser(2, ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($token, $page, [PageVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testAdminCannotDeleteHomepage(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);
        $page->setIsHomepage(true);

        $admin = $this->createUser(2, ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($token, $page, [PageVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonAdminCannotDelete(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($author);

        $result = $this->voter->vote($token, $page, [PageVoter::DELETE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // Test CREATE permission

    public function testAuthenticatedUserCanCreate(): void
    {
        $user = $this->createUser(1);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($token, null, [PageVoter::CREATE]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testPublicCannotCreate(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($token, null, [PageVoter::CREATE]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    // Test PUBLISH permission

    public function testAdminCanPublish(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $admin = $this->createUser(2, ['ROLE_ADMIN']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($token, $page, [PageVoter::PUBLISH]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonAdminCannotPublish(): void
    {
        $author = $this->createUser(1);
        $page = $this->createDraftPage($author);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($author);

        $result = $this->voter->vote($token, $page, [PageVoter::PUBLISH]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    private function createPublishedPage(): Page
    {
        $page = new Page();
        $page->setTitle('Test Page');
        $page->setSlug('test-page');
        $page->setContent('Content');
        $page->setIsPublished(true);

        $author = new User();
        $author->setUsername('author');
        $author->setEmail('author@test.com');
        $page->setAuthor($author);

        return $page;
    }

    private function createDraftPage(User $author): Page
    {
        $page = new Page();
        $page->setTitle('Draft Page');
        $page->setSlug('draft-page');
        $page->setContent('Draft content');
        $page->setIsPublished(false);
        $page->setAuthor($author);

        return $page;
    }

    private function createUser(int $id, array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setUsername('user' . $id);
        $user->setEmail("user{$id}@test.com");
        $user->setRoles($roles);

        // Set ID using reflection
        $reflection = new \ReflectionClass($user);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, $id);

        return $user;
    }
}
