<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Form\ServiceType;
use App\Http\QueryParam;
use App\Repository\BookingRepository;
use App\Repository\ServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/services', name: 'admin_services_')]
#[IsGranted('ROLE_ADMIN')]
final class ServiceController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(Request $request, ServiceRepository $serviceRepository, EntityManagerInterface $entityManager): Response
    {
        $search = QueryParam::string($request, 'q');
        $status = QueryParam::string($request, 'status');
        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $service = new Service();
        $createForm = $this->createForm(ServiceType::class, $service);
        $createForm->handleRequest($request);

        if ($createForm->isSubmitted() && $createForm->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();
            $this->addFlash('success', 'Service created.');

            return $this->redirectToRoute('admin_services_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/services/index.html.twig', [
            'services' => $serviceRepository->findFiltered($search, $status),
            'search' => $search,
            'status' => $status,
            'create_form' => $createForm->createView(),
            'open_modal' => $createForm->isSubmitted(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $service = new Service();
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();
            $this->addFlash('success', 'Service created.');

            return $this->redirectToRoute('admin_services_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/services/new.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Service $service): Response
    {
        return $this->render('admin/services/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Service updated.');

            return $this->redirectToRoute('admin_services_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/services/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Service $service,
        BookingRepository $bookings,
        EntityManagerInterface $entityManager,
    ): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$service->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($bookings->countForService($service) > 0) {
            $this->addFlash('error', 'This service is used by existing shipments. Deactivate it instead.');
        } else {
            $entityManager->remove($service);
            $entityManager->flush();
            $this->addFlash('success', 'Service deleted.');
        }

        return $this->redirectToRoute('admin_services_index', [], Response::HTTP_SEE_OTHER);
    }
}
