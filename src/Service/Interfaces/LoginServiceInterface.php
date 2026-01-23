<?php

namespace App\Service\Interfaces;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

interface LoginServiceInterface
{
    public function login(User $user, Request $request): void;
}
