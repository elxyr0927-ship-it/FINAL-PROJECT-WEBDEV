<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Http\QueryParam;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/users', name: 'admin_users_')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, UserRepository $users): Response
    {
        return $this->renderIndex($request, $users);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);
        $isModal = $request->request->getBoolean('_modal');

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles([$form->get('role')->getData()]);
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Account created.');

            return $this->redirectToRoute('admin_users_index');
        }

        if ($isModal) {
            return $this->renderIndex($request, $users, $form, true);
        }

        return $this->render('admin/users/form.html.twig', [
            'form' => $form->createView(),
            'heading' => 'Create account',
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): Response {
        $wasActiveAdmin = $user->isActive() && in_array('ROLE_ADMIN', $user->getRoles(), true);
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) ($form->get('plainPassword')->getData() ?? '');
            $passwordErrors = $plainPassword === ''
                ? []
                : $validator->validate($plainPassword, [new Length(min: 12, max: 4096)]);

            if (count($passwordErrors) > 0) {
                foreach ($passwordErrors as $passwordError) {
                    $form->get('plainPassword')->addError(new \Symfony\Component\Form\FormError($passwordError->getMessage()));
                }
            } else {
                $newRole = (string) $form->get('role')->getData();
                $willBeActiveAdmin = $user->isActive() && $newRole === 'ROLE_ADMIN';

                if ($user === $this->getUser() && !$willBeActiveAdmin) {
                    $form->addError(new \Symfony\Component\Form\FormError('You cannot disable or demote your own account.'));
                } elseif ($wasActiveAdmin && !$willBeActiveAdmin && $users->countActiveAdmins() <= 1) {
                    $form->addError(new \Symfony\Component\Form\FormError('The last active administrator cannot be demoted or disabled.'));
                } else {
                    $user->setRoles([$newRole]);
                    if ($plainPassword !== '') {
                        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                    }
                    $entityManager->flush();
                    $this->addFlash('success', 'Account updated.');

                    return $this->redirectToRoute('admin_users_index');
                }
            }
        }

        return $this->render('admin/users/form.html.twig', [
            'form' => $form->createView(),
            'heading' => 'Edit account',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        User $user,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('delete-user-'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'You cannot delete your own account while signed in.');
        } elseif (
            $user->isActive()
            && in_array('ROLE_ADMIN', $user->getRoles(), true)
            && $users->countActiveAdmins() <= 1
        ) {
            $this->addFlash('error', 'The last active administrator cannot be deleted.');
        } else {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Account deleted.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/toggle-active', name: 'toggle_active', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleActive(
        User $user,
        Request $request,
        UserRepository $users,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('toggle-user-'.$user->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($user === $this->getUser() && $user->isActive()) {
            $this->addFlash('error', 'You cannot disable your own account while signed in.');
        } elseif (
            $user->isActive()
            && in_array('ROLE_ADMIN', $user->getRoles(), true)
            && $users->countActiveAdmins() <= 1
        ) {
            $this->addFlash('error', 'The last active administrator cannot be disabled.');
        } else {
            $user->setIsActive(!$user->isActive());
            $entityManager->flush();
            $this->addFlash('success', $user->isActive() ? 'Account enabled.' : 'Account disabled.');
        }

        return $this->redirectToRoute('admin_users_index');
    }

    private function renderIndex(
        Request $request,
        UserRepository $users,
        ?FormInterface $createForm = null,
        bool $openModal = false,
    ): Response {
        $search = QueryParam::string($request, 'q');
        $role = QueryParam::string($request, 'role');
        if (!in_array($role, ['ROLE_ADMIN', 'ROLE_STAFF'], true)) {
            $role = '';
        }

        $form = $createForm ?? $this->createForm(UserType::class, new User(), ['is_new' => true]);

        return $this->render('admin/users/index.html.twig', [
            'users' => $users->findForAdmin($search, $role),
            'search' => $search,
            'role' => $role,
            'create_form' => $form->createView(),
            'open_modal' => $openModal,
        ]);
    }
}
