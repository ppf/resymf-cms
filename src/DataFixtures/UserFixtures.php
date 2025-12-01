<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * User Fixtures.
 *
 * Loads initial users for development and testing
 * - Admin user with ROLE_ADMIN
 * - Regular user with ROLE_USER
 */
class UserFixtures extends Fixture
{
    public const USER_ADMIN = 'user-admin';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        if ($env !== 'dev' && $env !== 'test') {
            echo "Skipping UserFixtures outside dev/test environments.\n";

            return;
        }

        // Create admin user
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@resymf.local');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setIsActive(true);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123', // Default password - CHANGE IN PRODUCTION!
        );
        $admin->setPassword($hashedPassword);

        $manager->persist($admin);

        // Create regular test user
        $user = new User();
        $user->setUsername('testuser');
        $user->setEmail('user@resymf.local');
        $user->setRoles(['ROLE_USER']);
        $user->setIsActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'user123', // Default password - CHANGE IN PRODUCTION!
        );
        $user->setPassword($hashedPassword);

        $manager->persist($user);

        // Create inactive user for testing
        $inactiveUser = new User();
        $inactiveUser->setUsername('inactive');
        $inactiveUser->setEmail('inactive@resymf.local');
        $inactiveUser->setRoles(['ROLE_USER']);
        $inactiveUser->setIsActive(false);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $inactiveUser,
            'inactive123',
        );
        $inactiveUser->setPassword($hashedPassword);

        $manager->persist($inactiveUser);

        // Flush all users to database
        $manager->flush();

        // Add reference for other fixtures
        $this->addReference(self::USER_ADMIN, $admin);
    }
}
