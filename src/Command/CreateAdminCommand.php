<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:create-admin', description: 'Create the first active administrator account')]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($this->users->countActiveAdmins() > 0) {
            $io->error('An active administrator already exists.');

            return Command::FAILURE;
        }

        $username = trim((string) $io->ask('Username'));
        $name = trim((string) $io->ask('Full name'));
        $email = trim((string) $io->ask('Email address'));
        $password = (string) $io->askHidden('Password (minimum 12 characters)');

        if (
            $username === ''
            || strlen($username) > 180
            || $name === ''
            || strlen($name) > 120
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 180
            || strlen($password) < 12
        ) {
            $io->error('Enter a username, name, valid email, and a password of at least 12 characters.');

            return Command::FAILURE;
        }

        if ($this->users->findOneBy(['username' => $username]) || $this->users->findOneBy(['email' => $email])) {
            $io->error('That username or email is already in use.');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setUsername($username)
            ->setName($name)
            ->setEmail($email)
            ->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $io->success(sprintf('Administrator "%s" created.', $username));

        return Command::SUCCESS;
    }
}
