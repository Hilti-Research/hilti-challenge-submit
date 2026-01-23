<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\User;
use App\Enum\FlashType;
use App\Form\User\RegisterType;
use App\Form\UserTrait\LoginType;
use App\Form\UserTrait\OnlyEmailType;
use App\Form\UserTrait\SetPasswordType;
use App\Helper\DoctrineHelper;
use App\Service\Interfaces\EmailServiceInterface;
use App\Service\Interfaces\LoginServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\DisabledException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils, LoggerInterface $logger, TranslatorInterface $translator, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('index');
        }

        // show last auth error
        // TODO check works with new authentication layer
        // TODO add isEnabled?
        $error = $authenticationUtils->getLastAuthenticationError();
        if (null !== $error) {
            if ($error instanceof DisabledException) {
                $message = $translator->trans('login.errors.account_disabled', [], 'security');
            } elseif ($error instanceof BadCredentialsException) {
                $message = $translator->trans('login.errors.password_wrong', [], 'security');
            } else {
                $message = $translator->trans('login.errors.login_failed', [], 'security');
                $logger->error('login failed', ['exception' => $error]);
            }

            $this->addFlash(FlashType::DANGER->value, $message);
        }

        $user = new User();
        $user->setEmail($authenticationUtils->getLastUsername());

        $form = $this->createForm(LoginType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'login.submit']);

        return $this->render('security/login.html.twig', ['form' => $form]);
    }

    #[Route('/register', name: 'register')]
    public function register(Request $request, ManagerRegistry $managerRegistry, UserPasswordHasherInterface $passwordHasher, LoginServiceInterface $loginService, TranslatorInterface $translator): Response
    {
        $user = new User();
        $form = $this->createForm(RegisterType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'register.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->setPassword($form->get('password'), $user, $passwordHasher, $translator)) {
            $existingUser = $managerRegistry->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if (null !== $existingUser) {
                $message = $translator->trans('register.error.email_already_used', [], 'security');
                $this->addFlash(FlashType::DANGER->value, $message);
            } else {
                $user->generateAuthenticationHash();
                DoctrineHelper::persistAndFlush($managerRegistry, $user);

                $loginService->login($user, $request);

                $message = $translator->trans('register.success.welcome', [], 'security');
                $this->addFlash(FlashType::SUCCESS->value, $message);

                return $this->redirectToRoute('submission_mine');
            }
        }

        return $this->render('security/register.html.twig', ['form' => $form]);
    }

    #[Route('/recover', name: 'recover')]
    public function recover(Request $request, ManagerRegistry $managerRegistry, EmailServiceInterface $emailService, TranslatorInterface $translator, LoggerInterface $logger): Response
    {
        $user = new User();
        $form = $this->createForm(OnlyEmailType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'recover.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $existingUser */
            $existingUser = $managerRegistry->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if (null === $existingUser) {
                $message = $translator->trans('recover.error.email_not_found', [], 'security');
                $this->addFlash(FlashType::DANGER->value, $message);
            } else {
                $existingUser->generateAuthenticationHash();
                DoctrineHelper::persistAndFlush($managerRegistry, $existingUser);

                if ($emailService->sendRecoverConfirmLink($existingUser)) {
                    $logger->info('sent password reset email to ' . $existingUser->getEmail());
                    $this->addFlash(FlashType::SUCCESS->value, $translator->trans('recover.success.email_sent', [], 'security'));
                } else {
                    $logger->error('could not send password reset email ' . $existingUser->getEmail());
                    $this->addFlash(FlashType::DANGER->value, $translator->trans('recover.fail.email_not_sent', [], 'security'));
                }
            }
        }

        return $this->render('security/recover.html.twig', ['form' => $form]);
    }

    #[Route('/recover/confirm/{authenticationHash}', name: 'recover_confirm')]
    public function recoverConfirm(Request $request, string $authenticationHash, ManagerRegistry $managerRegistry, TranslatorInterface $translator, LoginServiceInterface $loginService, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $managerRegistry->getRepository(User::class)->findOneBy(['authenticationHash' => $authenticationHash]);
        if (null === $user) {
            $message = $translator->trans('recover_confirm.error.invalid_hash', [], 'security');
            $this->addFlash(FlashType::DANGER->value, $message);

            return $this->redirectToRoute('login');
        }

        $form = $this->createForm(SetPasswordType::class, $user);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'security', 'label' => 'recover_confirm.submit']);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $this->setPassword($form, $user, $passwordHasher, $translator)) {
            $user->generateAuthenticationHash();
            DoctrineHelper::persistAndFlush($managerRegistry, $user);

            $message = $translator->trans('recover_confirm.success.password_set', [], 'security');
            $this->addFlash(FlashType::SUCCESS->value, $message);

            $loginService->login($user, $request);

            return $this->redirectToRoute('index');
        }

        return $this->render('security/recover_confirm.html.twig', ['form' => $form]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    private function setPassword(FormInterface $form, User $user, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator): bool
    {
        $plainPassword = $form->get('plainPassword')->getData();
        $repeatPlainPassword = $form->get('repeatPlainPassword')->getData();

        if (strlen($plainPassword) < 8) {
            $message = $translator->trans('_messages.error.password_too_short', [], 'security');
            $this->addFlash(FlashType::DANGER->value, $message);

            return false;
        }

        if ($plainPassword !== $repeatPlainPassword) {
            $message = $translator->trans('_messages.error.passwords_do_not_match', [], 'security');
            $this->addFlash(FlashType::DANGER->value, $message);

            return false;
        }

        $password = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($password);

        return true;
    }
}
