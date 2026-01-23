<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(string $currentChallengeDeadline): Response
    {
        $challengeFinished = new \DateTime($currentChallengeDeadline) < new \DateTime();
        $content = ['challengeFinished' => $challengeFinished];

        return $this->render('index.html.twig', $content);
    }
}
