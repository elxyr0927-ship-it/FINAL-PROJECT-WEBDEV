<?php

namespace App\Security;

use App\Entity\Booking;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class BookingVoter extends Voter
{
    public const VIEW = 'BOOKING_VIEW';
    public const EDIT = 'BOOKING_EDIT';
    public const DELETE = 'BOOKING_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Booking
            && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if (!in_array('ROLE_STAFF', $user->getRoles(), true)) {
            return false;
        }

        if ($attribute === self::VIEW) {
            return true;
        }

        return $subject->getCreatedBy() === $user;
    }
}
