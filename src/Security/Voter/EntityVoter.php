<?php

declare(strict_types=1);

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Generic voter for common entity operations.
 *
 * Provides basic CRUD authorization for entities based on roles.
 * Can be used as a fallback for entities without specific voters.
 */
class EntityVoter extends Voter
{
    public const VIEW = 'ENTITY_VIEW';
    public const EDIT = 'ENTITY_EDIT';
    public const DELETE = 'ENTITY_DELETE';
    public const CREATE = 'ENTITY_CREATE';

    /**
     * Map of entity classes to required roles for different operations.
     *
     * @var array<string, array<string, array<string>>>
     */
    private const ENTITY_PERMISSIONS = [
        'App\Entity\Category' => [
            'view' => ['ROLE_USER'],
            'edit' => ['ROLE_USER'],
            'delete' => ['ROLE_ADMIN'],
            'create' => ['ROLE_USER'],
        ],
        'App\Entity\Theme' => [
            'view' => ['ROLE_USER'],
            'edit' => ['ROLE_ADMIN'],
            'delete' => ['ROLE_ADMIN'],
            'create' => ['ROLE_ADMIN'],
        ],
        'App\Entity\Settings' => [
            'view' => ['ROLE_ADMIN'],
            'edit' => ['ROLE_ADMIN'],
            'delete' => ['ROLE_ADMIN'], // Should never be deleted
            'create' => ['ROLE_ADMIN'],
        ],
    ];

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE], true)) {
            return false;
        }

        // For CREATE, subject might be a class name string
        if ($attribute === self::CREATE) {
            if (is_string($subject)) {
                return isset(self::ENTITY_PERMISSIONS[$subject]);
            }

            return false;
        }

        // For other operations, check if we have permissions defined for this entity class
        if (is_object($subject)) {
            $className = get_class($subject);

            return isset(self::ENTITY_PERMISSIONS[$className]);
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        $className = is_object($subject) ? get_class($subject) : $subject;
        $permissions = self::ENTITY_PERMISSIONS[$className] ?? null;

        if ($permissions === null) {
            return false;
        }

        $operation = match ($attribute) {
            self::VIEW => 'view',
            self::EDIT => 'edit',
            self::DELETE => 'delete',
            self::CREATE => 'create',
            default => null,
        };

        if ($operation === null) {
            return false;
        }

        $requiredRoles = $permissions[$operation] ?? [];

        // Check if user has any of the required roles
        foreach ($requiredRoles as $role) {
            if (in_array($role, $user->getRoles(), true)) {
                return true;
            }
        }

        return false;
    }
}
