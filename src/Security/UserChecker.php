<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public const DISABLED_MESSAGE = 'Your account has been disabled by the administrator. Please contact support if you believe this is a mistake.';

    public function checkPreAuth(UserInterface $user): void
    {
        $this->denyInactiveUser($user);
    }

    public function checkPostAuth(UserInterface $user): void
    {
        $this->denyInactiveUser($user);
    }

    private function denyInactiveUser(UserInterface $user): void
    {
        if ($user instanceof User && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(self::DISABLED_MESSAGE);
        }
    }
}
