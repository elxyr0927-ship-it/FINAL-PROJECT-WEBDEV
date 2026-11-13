<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\StatusHistory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class BookingWorkflow
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(Booking $booking, User $actor): void
    {
        $booking->setTotalCost($booking->getService()->calculateTotalCost((string) $booking->getWeight()));

        $history = (new StatusHistory())
            ->setBooking($booking)
            ->setChangedBy($actor)
            ->setStatuses('pending', 'pending')
            ->setNotes('Shipment record created.');

        $this->entityManager->persist($booking);
        $this->entityManager->persist($history);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, string> field => error message
     */
    public function update(Booking $booking, User $actor, string $previousStatus, string $previousPayment): array
    {
        $errors = [];

        $statusError = Booking::statusTransitionError(
            $previousStatus,
            (string) $booking->getStatus(),
            (string) $booking->getPaymentStatus(),
        );
        if ($statusError !== null) {
            $errors['status'] = $statusError;
        }

        $paymentError = Booking::paymentTransitionError(
            $previousPayment,
            (string) $booking->getPaymentStatus(),
            $previousStatus,
        );
        if ($paymentError !== null) {
            $errors['payment'] = $paymentError;
        }

        if ($errors !== []) {
            return $errors;
        }

        $booking->setTotalCost($booking->getService()->calculateTotalCost((string) $booking->getWeight()));

        if ($previousStatus !== $booking->getStatus()) {
            $history = (new StatusHistory())
                ->setBooking($booking)
                ->setChangedBy($actor)
                ->setStatuses($previousStatus, (string) $booking->getStatus())
                ->setNotes('Shipment status updated.');
            $this->entityManager->persist($history);
        }

        if ($previousPayment !== $booking->getPaymentStatus()) {
            $history = (new StatusHistory())
                ->setBooking($booking)
                ->setChangedBy($actor)
                ->setStatuses((string) $booking->getStatus(), (string) $booking->getStatus())
                ->setCheckpoint('Payment updated')
                ->setNotes(sprintf('Payment status: %s - %s.', $previousPayment, (string) $booking->getPaymentStatus()));
            $this->entityManager->persist($history);
        }

        $this->entityManager->flush();

        return [];
    }

    public function delete(Booking $booking): void
    {
        $this->entityManager->remove($booking);
        $this->entityManager->flush();
    }
}
