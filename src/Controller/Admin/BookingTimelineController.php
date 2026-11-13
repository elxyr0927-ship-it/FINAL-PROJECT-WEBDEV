<?php

namespace App\Controller\Admin;

use App\Entity\Booking;
use App\Entity\StatusHistory;
use App\Entity\User;
use App\Form\CheckpointType;
use App\Repository\StatusHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/bookings', name: 'admin_bookings_')]
#[IsGranted('ROLE_ADMIN')]
final class BookingTimelineController extends AbstractController
{
    #[Route('/{id}/timeline', name: 'timeline', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        Booking $booking,
        StatusHistoryRepository $history,
        EntityManagerInterface $entityManager,
    ): Response {
        $entry = new StatusHistory();
        $form = $this->createForm(CheckpointType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (in_array($booking->getStatus(), ['delivered', 'cancelled'], true)) {
                $form->addError(new FormError('This shipment is closed; its checkpoint history is locked.'));
            } else {
                $entry
                    ->setBooking($booking)
                    ->setChangedBy($this->currentUser())
                    ->setStatuses((string) $booking->getStatus(), (string) $booking->getStatus());
                $entityManager->persist($entry);
                $entityManager->flush();
                $this->addFlash('success', 'Checkpoint added to the shipment timeline.');

                return $this->redirectToRoute('admin_bookings_timeline', ['id' => $booking->getId()]);
            }
        }

        return $this->render('admin/bookings/timeline.html.twig', [
            'booking' => $booking,
            'form' => $form,
            'history' => $history->findForBooking($booking),
        ]);
    }

    private function currentUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
