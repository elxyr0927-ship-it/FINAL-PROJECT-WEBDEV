<?php

namespace App\Controller\Admin;

use App\Entity\ContactMessage;
use App\Http\QueryParam;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/inquiries', name: 'admin_inquiries_')]
#[IsGranted('ROLE_ADMIN')]
final class InquiryController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, ContactMessageRepository $inquiries): Response
    {
        $filter = QueryParam::string($request, 'filter', 'all');
        if (!in_array($filter, ['all', 'read', 'unread', 'archived'], true)) {
            $filter = 'all';
        }

        $search = QueryParam::string($request, 'q');

        return $this->render('admin/inquiries/index.html.twig', [
            'inquiries' => $inquiries->findForAdmin($filter, $search),
            'filter' => $filter,
            'search' => $search,
            'total_count' => $inquiries->countActive(),
            'unread_count' => $inquiries->countUnread(),
            'read_count' => max(0, $inquiries->countActive() - $inquiries->countUnread()),
            'archived_count' => $inquiries->countArchived(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(ContactMessage $inquiry): Response
    {
        return $this->render('admin/inquiries/show.html.twig', ['inquiry' => $inquiry]);
    }

    #[Route('/{id}/{action}', name: 'action', requirements: ['id' => '\d+', 'action' => 'read|unread|archive|restore|delete'], methods: ['POST'])]
    public function action(
        Request $request,
        ContactMessage $inquiry,
        string $action,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('inquiry_'.$action.$inquiry->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($action === 'delete') {
            $entityManager->remove($inquiry);
            $entityManager->flush();
            $this->addFlash('success', 'Inquiry deleted permanently.');

            return $this->redirectToRoute('admin_inquiries_index', ['filter' => 'archived']);
        }

        if ($action === 'archive') {
            $inquiry->setDeletedAt(new \DateTimeImmutable());
            $this->addFlash('success', 'Inquiry archived. You can find it under Inquiries > Archived.');
        } elseif ($action === 'restore') {
            $inquiry->setDeletedAt(null);
            $this->addFlash('success', 'Inquiry restored.');
        } else {
            $inquiry->setIsRead($action === 'read');
            $this->addFlash('success', $action === 'read' ? 'Inquiry marked as read.' : 'Inquiry marked as unread.');
        }

        $entityManager->flush();

        return $this->redirectToRoute('admin_inquiries_index', $action === 'archive' ? ['filter' => 'archived'] : []);
    }
}
