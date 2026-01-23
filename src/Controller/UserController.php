<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\FlashType;
use App\Form\User\DeleteUserType;
use App\Form\User\UserTeamType;
use App\Helper\DoctrineHelper;
use App\Service\Interfaces\LoginServiceInterface;
use App\Service\Interfaces\PathServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends AbstractController
{
    #[Route('/user/setup', name: 'user_setup')]
    public function setup(Request $request, ManagerRegistry $managerRegistry, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->setContactEmail($user->getEmail());
        $form = $this->createForm(UserTeamType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'user', 'label' => 'setup.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            DoctrineHelper::persistAndFlush($managerRegistry, $user);

            $message = $translator->trans('setup.success.stored', [], 'user');
            $this->addFlash(FlashType::SUCCESS->value, $message);

            return $this->redirectToRoute('submission_new');
        }

        return $this->render('user/setup.html.twig', ['form' => $form]);
    }

    #[Route('/user/toggle_prefer_anonymity', name: 'user_toggle_prefer_anonymity')]
    public function togglePreferAnonymity(Request $request, ManagerRegistry $managerRegistry): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $returnUrl = $request->query->get('return_url');

        $user->setPreferAnonymity(!$user->getPreferAnonymity());
        DoctrineHelper::persistAndFlush($managerRegistry, $user);

        return $this->redirect($returnUrl);
    }

    #[Route('/user/edit', name: 'user_edit')]
    public function edit(Request $request, ManagerRegistry $managerRegistry, TranslatorInterface $translator): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserTeamType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'user', 'label' => 'edit.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $managerRegistry->getManager();
            $manager->persist($user);
            $manager->flush();

            $message = $translator->trans('edit.success.saved', [], 'user');
            $this->addFlash(FlashType::SUCCESS->value, $message);

            return $this->redirectToRoute('submission_mine');
        }

        return $this->render('user/edit.html.twig', ['form' => $form]);
    }

    #[Route('/user/remove', name: 'user_remove')]
    public function remove(Request $request, ManagerRegistry $managerRegistry, TranslatorInterface $translator, PathServiceInterface $pathService, LoginServiceInterface $loginService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(DeleteUserType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'user', 'label' => 'remove.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userSubmissionDirectory = $pathService->getUserDirectory($user);

            $filesystem = new Filesystem();
            $filesystem->remove($userSubmissionDirectory);

            DoctrineHelper::removeAndFlush($managerRegistry, $user, ...$user->getSubmissions()->toArray(), ...$user->getEmails()->toArray());

            $message = $translator->trans('remove.success.removed', [], 'user');
            $this->addFlash(FlashType::SUCCESS->value, $message);

            $loginService->logout($request);

            return $this->redirectToRoute('index');
        }

        return $this->render('user/remove.html.twig', ['form' => $form]);
    }
}
