<?php

namespace App\Controller\Admin;

use App\Repository\BookingRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\ServiceRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class DashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        BookingRepository $bookings,
        ContactMessageRepository $inquiries,
        ServiceRepository $services,
        UserRepository $users,
    ): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'booking_count' => $bookings->countAll(),
            'user_count' => $users->count([]),
            'staff_count' => $users->countStaff(),
            'pending_count' => $bookings->countByStatus('pending'),
            'unpaid_count' => $bookings->countByPaymentStatus('unpaid'),
            'service_count' => $services->count([]),
            'unread_inquiry_count' => $inquiries->countUnread(),
            'recent_bookings' => $bookings->findRecent(),
        ]);
    }
}
