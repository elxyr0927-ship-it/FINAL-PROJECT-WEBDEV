<?php

namespace App\Tests\Security;

use App\Entity\Booking;
use App\Entity\User;
use App\Security\BookingVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class BookingVoterTest extends TestCase
{
    private function vote(User $user, Booking $booking, string $attribute): int
    {
        return (new BookingVoter())->vote(
            new UsernamePasswordToken($user, 'main', $user->getRoles()),
            $booking,
            [$attribute],
        );
    }

    public function testStaffCanViewTheirOwnShipment(): void
    {
        $staff = (new User())->setRoles(['ROLE_STAFF']);
        $booking = (new Booking())->setCreatedBy($staff);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($staff, $booking, BookingVoter::VIEW));
    }

    public function testStaffCanViewShipmentsCreatedByOthers(): void
    {
        $staff = (new User())->setRoles(['ROLE_STAFF']);
        $colleague = (new User())->setRoles(['ROLE_STAFF']);
        $admin = (new User())->setRoles(['ROLE_ADMIN']);

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote($staff, (new Booking())->setCreatedBy($colleague), BookingVoter::VIEW),
        );
        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->vote($staff, (new Booking())->setCreatedBy($admin), BookingVoter::VIEW),
        );
    }

    public function testStaffCanEditAndDeleteTheirOwnShipment(): void
    {
        $staff = (new User())->setRoles(['ROLE_STAFF']);
        $booking = (new Booking())->setCreatedBy($staff);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($staff, $booking, BookingVoter::EDIT));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($staff, $booking, BookingVoter::DELETE));
    }

    public function testStaffCannotEditOrDeleteShipmentsCreatedByOthers(): void
    {
        $staff = (new User())->setRoles(['ROLE_STAFF']);
        $colleague = (new User())->setRoles(['ROLE_STAFF']);
        $admin = (new User())->setRoles(['ROLE_ADMIN']);

        foreach ([$colleague, $admin] as $creator) {
            $booking = (new Booking())->setCreatedBy($creator);

            self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($staff, $booking, BookingVoter::EDIT));
            self::assertSame(VoterInterface::ACCESS_DENIED, $this->vote($staff, $booking, BookingVoter::DELETE));
        }
    }

    public function testAdminCanAccessAnyShipment(): void
    {
        $admin = (new User())->setRoles(['ROLE_ADMIN']);
        $booking = (new Booking())->setCreatedBy((new User())->setRoles(['ROLE_STAFF']));

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $booking, BookingVoter::VIEW));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $booking, BookingVoter::EDIT));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->vote($admin, $booking, BookingVoter::DELETE));
    }
}
