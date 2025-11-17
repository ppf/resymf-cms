<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Voter for User entity authorization.
 *
 * Determines whether a user can perform specific actions on User entities.
 */
class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
    public const CREATE = 'USER_CREATE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support if attribute is one of our defined constants
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE], true)) {
            return false;
        }

        // For CREATE, we don't need a subject
        if ($attribute === self::CREATE) {
            return true;
        }

        // For other actions, subject must be a User instance
        return $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        // User must be logged in
        if (!$currentUser instanceof UserInterface) {
            return false;
        }

        /** @var User|null $targetUser */
        $targetUser = $subject instanceof User ? $subject : null;

        return match ($attribute) {
            self::VIEW => $this->canView($currentUser, $targetUser),
            self::EDIT => $this->canEdit($currentUser, $targetUser),
            self::DELETE => $this->canDelete($currentUser, $targetUser),
            self::CREATE => $this->canCreate($currentUser),
            default => false,
        };
    }

    private function canView(UserInterface $currentUser, ?User $targetUser): bool
    {
        // Admins can view all users
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Users can view their own profile
        return (bool) ($targetUser !== null && $currentUser->getUserIdentifier() === $targetUser->getUserIdentifier())

        ;
    }

    private function canEdit(UserInterface $currentUser, ?User $targetUser): bool
    {
        if ($targetUser === null) {
            return false;
        }

        // Admins can edit all users
        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        // Users can edit their own profile (limited fields)
        return (bool) ($currentUser->getUserIdentifier() === $targetUser->getUserIdentifier())

        ;
    }

    private function canDelete(UserInterface $currentUser, ?User $targetUser): bool
    {
        if ($targetUser === null) {
            return false;
        }

        // Only admins can delete users
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            return false;
        }

        // Cannot delete yourself
        return !($currentUser->getUserIdentifier() === $targetUser->getUserIdentifier())

        ;
    }

    private function canCreate(UserInterface $currentUser): bool
    {
        // Only admins can create new users
        return in_array('ROLE_ADMIN', $currentUser->getRoles(), true);
    }
}
