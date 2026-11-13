<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Entity\User;
use App\Form\ContactMessageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $inquiry = new ContactMessage();
        $form = $this->createForm(ContactMessageType::class, $inquiry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $inquiry->setUser($user);
            }

            $entityManager->persist($inquiry);
            $entityManager->flush();
            $this->addFlash('success', 'Your inquiry has been sent. Our team will review it soon.');

            return $this->redirectToRoute('contact', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('contact/index.html.twig', ['form' => $form]);
    }
}
