<?php

namespace App\Tests\Unit\Command;

use App\Command\CreateAdminCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateAdminCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private UserRepository $userRepository;
    private CreateAdminCommand $command;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->command = new CreateAdminCommand(
            $this->entityManager,
            $this->passwordHasher,
            $this->validator,
            $this->userRepository
        );
    }

    public function testExecuteCreatesAdminUser(): void
    {
        // Arrange
        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return $user->getUsername() === 'testadmin'
                    && $user->getEmail() === 'test@example.com'
                    && in_array('ROLE_ADMIN', $user->getRoles())
                    && $user->isIsActive();
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:create-admin');
        $commandTester = new CommandTester($command);

        // Act
        $commandTester->execute([
            'username' => 'testadmin',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertStringContainsString('Admin user "testadmin" created successfully', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testExecuteFailsWhenUsernameExists(): void
    {
        // Arrange
        $existingUser = new User();
        $existingUser->setUsername('testadmin');

        $this->userRepository
            ->method('findOneBy')
            ->with(['username' => 'testadmin'])
            ->willReturn($existingUser);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:create-admin');
        $commandTester = new CommandTester($command);

        // Act
        $commandTester->execute([
            'username' => 'testadmin',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertStringContainsString('already exists', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteFailsWhenEmailExists(): void
    {
        // Arrange
        $this->userRepository
            ->method('findOneBy')
            ->willReturnCallback(function ($criteria) {
                if (isset($criteria['username'])) {
                    return null;
                }
                if (isset($criteria['email'])) {
                    $user = new User();
                    $user->setEmail('test@example.com');
                    return $user;
                }
                return null;
            });

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:create-admin');
        $commandTester = new CommandTester($command);

        // Act
        $commandTester->execute([
            'username' => 'testadmin',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertStringContainsString('already exists', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testExecuteCreatesInactiveUser(): void
    {
        // Arrange
        $this->userRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (User $user) {
                return !$user->isIsActive();
            }));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('app:create-admin');
        $commandTester = new CommandTester($command);

        // Act
        $commandTester->execute([
            'username' => 'testadmin',
            'email' => 'test@example.com',
            'password' => 'password123',
            '--inactive' => true,
        ]);

        // Assert
        $this->assertStringContainsString('Inactive', $commandTester->getDisplay());
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCommandConfiguration(): void
    {
        // Assert
        $this->assertEquals('app:create-admin', $this->command->getName());
        $this->assertEquals('Create a new admin user for the CMS', $this->command->getDescription());

        $definition = $this->command->getDefinition();
        $this->assertTrue($definition->hasArgument('username'));
        $this->assertTrue($definition->hasArgument('email'));
        $this->assertTrue($definition->hasArgument('password'));
        $this->assertTrue($definition->hasOption('inactive'));
    }
}
