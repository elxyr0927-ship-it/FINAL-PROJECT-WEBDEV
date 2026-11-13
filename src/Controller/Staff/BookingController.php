<?php

namespace App\Controller\Staff;

use App\Entity\Booking;
use App\Entity\User;
use App\Form\BookingType;
use App\Http\QueryParam;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;
use App\Security\BookingVoter;
use App\Service\BookingWorkflow;
use App\Service\TrackingCodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/staff/bookings', name: 'staff_bookings_')]
#[IsGranted('ROLE_STAFF')]
final class BookingController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        BookingRepository $bookings,
        ServiceRepository $services,
        TrackingCodeGenerator $trackingCodes,
        BookingWorkflow $workflow,
    ): Response {
        $search = QueryParam::string($request, 'q');
        $status = QueryParam::string($request, 'status');
        $paymentStatus = QueryParam::string($request, 'payment');
        $serviceId = QueryParam::int($request, 'service') ?: null;

        $status = in_array($status, Booking::STATUSES, true) ? $status : '';
        $paymentStatus = in_array($paymentStatus, Booking::PAYMENT_STATUSES, true) ? $paymentStatus : '';

        $booking = $this->buildBooking($trackingCodes);
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $workflow->create($booking, $this->currentUser());
            $this->addFlash('success', 'Shipment record created.');

            return $this->redirectToRoute('staff_bookings_show', ['id' => $booking->getId()]);
        }

        return $this->render('bookings/index.html.twig', [
            'bookings' => $bookings->findFiltered($search, $status ?: null, $paymentStatus ?: null, null, $serviceId),
            'search' => $search,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'staff_id' => null,
            'service_id' => $serviceId,
            'service_options' => $services->findBy([], ['name' => 'ASC']),
            'status_choices' => Booking::statusLabels(),
            'payment_choices' => Booking::paymentStatusLabels(),
            'create_form' => $form->createView(),
            'open_modal' => $form->isSubmitted(),
            'show_route' => 'staff_bookings_show',
            'edit_route' => 'staff_bookings_edit',
            'new_route' => 'staff_bookings_new',
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TrackingCodeGenerator $trackingCodes,
        BookingWorkflow $workflow,
    ): Response {
        $booking = $this->buildBooking($trackingCodes);
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $workflow->create($booking, $this->currentUser());
            $this->addFlash('success', 'Shipment record created.');

            return $this->redirectToRoute('staff_bookings_show', ['id' => $booking->getId()]);
        }

        return $this->render('bookings/form.html.twig', [
            'form' => $form,
            'heading' => 'Create shipment record',
            'back_route' => 'staff_bookings_index',
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Booking $booking): Response
    {
        $this->denyAccessUnlessGranted(BookingVoter::VIEW, $booking);

        return $this->render('bookings/show.html.twig', [
            'booking' => $booking,
            'edit_route' => 'staff_bookings_edit',
            'delete_route' => 'staff_bookings_delete',
            'back_route' => 'staff_bookings_index',
            'timeline_route' => null,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, BookingWorkflow $workflow): Response
    {
        $this->denyAccessUnlessGranted(BookingVoter::EDIT, $booking);
        $previousStatus = (string) $booking->getStatus();
        $previousPayment = (string) $booking->getPaymentStatus();
        $form = $this->createForm(BookingType::class, $booking, ['include_admin_fields' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $errors = $workflow->update($booking, $this->currentUser(), $previousStatus, $previousPayment);

            if ($errors === []) {
                $this->addFlash('success', 'Shipment record updated.');

                return $this->redirectToRoute('staff_bookings_show', ['id' => $booking->getId()]);
            }

            $this->addTransitionErrors($form, $errors);
        }

        return $this->render('bookings/form.html.twig', [
            'form' => $form,
            'heading' => 'Edit shipment record',
            'back_route' => 'staff_bookings_show',
            'back_parameters' => ['id' => $booking->getId()],
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Booking $booking, BookingWorkflow $workflow): Response
    {
        $this->denyAccessUnlessGranted(BookingVoter::DELETE, $booking);
        if (!$this->isCsrfTokenValid('delete'.$booking->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $workflow->delete($booking);
        $this->addFlash('success', 'Shipment record deleted.');

        return $this->redirectToRoute('staff_bookings_index');
    }

    private function buildBooking(TrackingCodeGenerator $trackingCodes): Booking
    {
        return (new Booking())
            ->setTrackingCode($trackingCodes->generate())
            ->setCreatedBy($this->currentUser());
    }

    /**
     * @param array<string, string> $errors
     */
    private function addTransitionErrors(FormInterface $form, array $errors): void
    {
        if (isset($errors['status'])) {
            $form->get('status')->addError(new FormError($errors['status']));
        }

        if (isset($errors['payment'])) {
            $form->get('paymentStatus')->addError(new FormError($errors['payment']));
        }
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
