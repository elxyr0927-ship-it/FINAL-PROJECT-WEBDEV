<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:reset-admin-password', description: 'Set a new password for an administrator account')]
final class ResetAdminPasswordCommand extends Command
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
        $username = trim((string) $io->ask('Administrator username'));
        $user = $this->users->findOneBy(['username' => $username]);

        if (!$user instanceof User || !$user->isActive() || !in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            $io->error('No active administrator account was found with that username.');

            return Command::FAILURE;
        }

        $password = (string) $io->askHidden('New password (minimum 12 characters)');
        $confirmation = (string) $io->askHidden('Confirm new password');

        if (strlen($password) < 12 || !hash_equals($password, $confirmation)) {
            $io->error('Passwords must match and be at least 12 characters long.');

            return Command::FAILURE;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->flush();
        $io->success(sprintf('Password updated for administrator "%s".', $username));

        return Command::SUCCESS;
    }
}
