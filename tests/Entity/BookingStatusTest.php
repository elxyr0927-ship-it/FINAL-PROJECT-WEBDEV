<?php

namespace App\Tests\Entity;

use App\Entity\Booking;
use PHPUnit\Framework\TestCase;

final class BookingStatusTest extends TestCase
{
    public function testUnpaidShipmentCannotBeDispatched(): void
    {
        self::assertSame(
            'Payment must be marked paid before dispatch or delivery.',
            Booking::statusTransitionError('pending', 'dispatched', 'unpaid'),
        );
    }

    public function testPaidShipmentCanProgressAndCannotReturnToPending(): void
    {
        self::assertNull(Booking::statusTransitionError('pending', 'dispatched', 'paid'));
        self::assertSame(
            'A shipment cannot be reverted to pending once it has left the pending stage.',
            Booking::statusTransitionError('dispatched', 'pending', 'paid'),
        );
    }

    public function testShipmentMustBeDispatchedBeforeDelivery(): void
    {
        self::assertSame(
            'A shipment must be dispatched before it can be marked as delivered.',
            Booking::statusTransitionError('pending', 'delivered', 'paid'),
        );
        self::assertNull(Booking::statusTransitionError('dispatched', 'delivered', 'paid'));
    }

    public function testClosedShipmentStatusIsLocked(): void
    {
        self::assertStringContainsString(
            'locked',
            (string) Booking::statusTransitionError('delivered', 'cancelled', 'paid'),
        );
    }

    public function testPaymentCannotBeDowngradedOncePaid(): void
    {
        self::assertSame(
            'Payment cannot be changed once it has been marked as paid.',
            Booking::paymentTransitionError('paid', 'unpaid', 'pending'),
        );
        self::assertSame(
            'Payment cannot be changed once it has been marked as paid.',
            Booking::paymentTransitionError('paid', 'under_review', 'pending'),
        );
    }

    public function testUnderReviewPaymentCanBeRejectedBackToUnpaid(): void
    {
        self::assertNull(Booking::paymentTransitionError('under_review', 'unpaid', 'pending'));
        self::assertNull(Booking::paymentTransitionError('unpaid', 'paid', 'pending'));
    }

    public function testDispatchedShipmentKeepsPaidPayment(): void
    {
        self::assertSame(
            'Dispatched or delivered shipments must remain marked as paid.',
            Booking::paymentTransitionError('unpaid', 'under_review', 'dispatched'),
        );
    }

    public function testStatusChoicesHideDispatchUntilPaid(): void
    {
        self::assertSame(
            ['Pending' => 'pending', 'Cancelled' => 'cancelled'],
            Booking::statusChoices('pending', 'unpaid'),
        );
        self::assertSame(
            ['Pending' => 'pending', 'Dispatched' => 'dispatched', 'Cancelled' => 'cancelled'],
            Booking::statusChoices('pending', 'paid'),
        );
    }

    public function testStatusChoicesHidePendingAfterDispatch(): void
    {
        $choices = Booking::statusChoices('dispatched', 'paid');

        self::assertArrayNotHasKey('Pending', $choices);
        self::assertSame(['Dispatched' => 'dispatched', 'Delivered' => 'delivered', 'Cancelled' => 'cancelled'], $choices);
    }

    public function testClosedShipmentsOnlyOfferTheirCurrentStatus(): void
    {
        self::assertSame(['Delivered' => 'delivered'], Booking::statusChoices('delivered', 'paid'));
        self::assertSame(['Cancelled' => 'cancelled'], Booking::statusChoices('cancelled', 'unpaid'));
        self::assertSame(['Paid' => 'paid'], Booking::paymentChoices('paid', 'pending'));
        self::assertSame(['Paid' => 'paid'], Booking::paymentChoices('unpaid', 'dispatched'));
    }
}
