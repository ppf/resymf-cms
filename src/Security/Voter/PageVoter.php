<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Page;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Voter for Page entity authorization.
 *
 * Determines whether a user can perform specific actions on Page entities.
 */
class PageVoter extends Voter
{
    public const VIEW = 'PAGE_VIEW';
    public const EDIT = 'PAGE_EDIT';
    public const DELETE = 'PAGE_DELETE';
    public const CREATE = 'PAGE_CREATE';
    public const PUBLISH = 'PAGE_PUBLISH';

    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportedAttributes = [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::CREATE,
            self::PUBLISH,
        ];

        if (!in_array($attribute, $supportedAttributes, true)) {
            return false;
        }

        // For CREATE, we don't need a subject
        if ($attribute === self::CREATE) {
            return true;
        }

        // For other actions, subject must be a Page instance
        return $subject instanceof Page;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // User must be logged in for admin actions
        if (!$user instanceof UserInterface) {
            // Public can view published pages
            if ($attribute === self::VIEW && $subject instanceof Page) {
                return $subject->isPublished();
            }

            return false;
        }

        /** @var Page|null $page */
        $page = $subject instanceof Page ? $subject : null;

        return match ($attribute) {
            self::VIEW => $this->canView($user, $page),
            self::EDIT => $this->canEdit($user, $page),
            self::DELETE => $this->canDelete($user, $page),
            self::CREATE => $this->canCreate($user),
            self::PUBLISH => $this->canPublish($user, $page),
            default => false,
        };
    }

    private function canView(UserInterface $user, ?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        // Admins can view all pages
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Authors can view their own pages (published or not)
        if ($page->getAuthor() !== null && $user instanceof User) {
            if ($page->getAuthor()->getId() === $user->getId()) {
                return true;
            }
        }

        // Everyone can view published pages
        return $page->isPublished();
    }

    private function canEdit(UserInterface $user, ?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        // Admins can edit all pages
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Authors can edit their own pages
        if ($page->getAuthor() !== null && $user instanceof User) {
            return $page->getAuthor()->getId() === $user->getId();
        }

        return false;
    }

    private function canDelete(UserInterface $user, ?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        // Only admins can delete pages
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false;
        }

        // Cannot delete homepage (additional business rule)
        return !$page->isHomepage();
    }

    private function canCreate(UserInterface $user): bool
    {
        // All authenticated users can create pages
        return in_array('ROLE_USER', $user->getRoles(), true);
    }

    private function canPublish(UserInterface $user, ?Page $page): bool
    {
        if ($page === null) {
            return false;
        }

        // Only admins can publish/unpublish pages
        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
