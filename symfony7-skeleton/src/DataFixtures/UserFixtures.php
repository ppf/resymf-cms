<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * User Fixtures
 *
 * Loads initial users for development and testing
 * - Admin user with ROLE_ADMIN
 * - Regular user with ROLE_USER
 */
class UserFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@resymf.local');
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setIsActive(true);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin123' // Default password - CHANGE IN PRODUCTION!
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
            'user123' // Default password - CHANGE IN PRODUCTION!
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
            'inactive123'
        );
        $inactiveUser->setPassword($hashedPassword);

        $manager->persist($inactiveUser);

        // Flush all users to database
        $manager->flush();

        // Output confirmation (visible when running fixtures)
        echo "✅ Created 3 users:\n";
        echo "   - admin (admin@resymf.local) - ROLE_ADMIN - Password: admin123\n";
        echo "   - testuser (user@resymf.local) - ROLE_USER - Password: user123\n";
        echo "   - inactive (inactive@resymf.local) - ROLE_USER - Password: inactive123 [DISABLED]\n";
        echo "\n⚠️  Remember to change default passwords in production!\n";
    }
}
