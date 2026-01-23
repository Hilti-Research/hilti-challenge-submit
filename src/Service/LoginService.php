<?php

namespace App\Service;

use App\Entity\User;
use App\Service\Interfaces\LoginServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;

readonly class LoginService implements LoginServiceInterface
{
    public function __construct(private UserCheckerInterface $checker, private UserAuthenticatorInterface $userAuthenticator, private FormLoginAuthenticator $formLoginAuthenticator, private TokenStorageInterface $tokenStorage)
    {
    }

    public function login(User $user, Request $request): void
    {
        $this->checker->checkPreAuth($user);
        $this->userAuthenticator->authenticateUser($user, $this->formLoginAuthenticator, $request);
    }

    public function logout(Request $request): void
    {
        $request->getSession()->invalidate();
        $this->tokenStorage->setToken();
    }
}
