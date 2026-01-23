<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Entity\User;
use App\Enum\ChallengeType;
use App\Enum\EvaluationType;
use App\Enum\LeaderboardVersion;
use App\Service\Interfaces\PathServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/leaderboard')]
class LeaderboardController extends AbstractController
{
    #[Route('', name: 'leaderboard', methods: 'GET')]
    public function leaderboard(Request $request, ManagerRegistry $managerRegistry, string $currentChallengeDeadline, string $leaderboardAuthentication): Response
    {
        $content = $this->getLeaderboardContent($request, $managerRegistry, $currentChallengeDeadline, $leaderboardAuthentication);

        $response = $this->render('leaderboard/leaderboard.html.twig', $content);

        // allow iframe integration
        $response->headers->remove('X-Frame-Options');

        return $response;
    }

    #[Route('/participation', name: 'participation', methods: 'GET')]
    public function participation(Request $request, ManagerRegistry $managerRegistry): Response
    {
        $rawChallengeType = $request->query->get('challengeType', ChallengeType::getActiveChallenge()->value);
        $challengeType = ChallengeType::tryFrom($rawChallengeType) ?? ChallengeType::getActiveChallenge();

        $submissionRepository = $managerRegistry->getRepository(Submission::class);
        $totals = $submissionRepository->findParticipationTotals($challengeType);

        $response = $this->json($totals);

        // set appropriate CORS headers
        $origin = $request->headers->get('Origin');
        if (in_array($origin, ['https://hilti-challenge.com', 'https://www.hilti-challenge.com'], true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Headers', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET');
        }

        return $response;
    }

    #[Route('/submission/{submission}/report', name: 'leaderboard_report')]
    public function report(Submission $submission, Request $request, ManagerRegistry $managerRegistry, string $currentChallengeDeadline, string $leaderboardAuthentication, PathServiceInterface $pathService): Response
    {
        if (!$this->isSubmissionOnLeaderboard($submission, $request, $managerRegistry, $currentChallengeDeadline, $leaderboardAuthentication)) {
            throw new NotFoundHttpException();
        }

        if (!$submission->getReportFilename() || $submission->getUser()->getPreferAnonymity()) {
            throw new NotFoundHttpException();
        }

        $submissionDirectory = $pathService->getSubmissionDirectory($submission);
        $path = $submissionDirectory . '/' . $submission->getReportFilename();

        $team = $pathService->getUserDirectory($submission->getUser());
        return $this->file($path, $team . "_report.pdf");
    }

    #[Route('/submission/{submission}/evaluation_report/{evaluationId}', name: 'leaderboard_evaluation_report')]
    public function evaluationReport(Submission $submission, Request $request, ManagerRegistry $managerRegistry, string $currentChallengeDeadline, string $leaderboardAuthentication, string $evaluationId, PathServiceInterface $pathService): Response
    {
        if (!$this->isSubmissionOnLeaderboard($submission, $request, $managerRegistry, $currentChallengeDeadline, $leaderboardAuthentication)) {
            throw new NotFoundHttpException();
        }

        if ($submission->getEvaluationId() !== $evaluationId) {
            throw new NotFoundHttpException();
        }

        $submissionDirectory = $pathService->getSubmissionDirectory($submission);
        $path = $submissionDirectory . '/' . $submission->getEvaluationFolder() . "/" . $submission->getEvaluationReportFilename();

        $team = $pathService->getUserDirectory($submission->getUser());
        return $this->file($path, $team . "_evaluation." . pathinfo($submission->getEvaluationReportFilename(), PATHINFO_EXTENSION));
    }

    private function getLeaderboardContent(Request $request, ManagerRegistry $managerRegistry, string $currentChallengeDeadline, string $leaderboardAuthentication): array
    {
        $rawChallengeType = $request->query->get('challengeType', ChallengeType::getActiveChallenge()->value);
        $challengeType = ChallengeType::tryFrom($rawChallengeType) ?? ChallengeType::getActiveChallenge();

        $rawEvaluationType = $request->query->get('evaluationType', EvaluationType::SLAM->value);
        $evaluationType = EvaluationType::tryFrom($rawEvaluationType) ?? EvaluationType::SLAM;

        $rawLeaderboardVersion = $request->query->get('leaderboardVersion', LeaderboardVersion::ACTIVE_CHALLENGE->value);
        $leaderboardVersion = LeaderboardVersion::tryFrom($rawLeaderboardVersion) ?? LeaderboardVersion::ACTIVE_CHALLENGE;

        $deadline = null;
        if ($challengeType === ChallengeType::getActiveChallenge()) {
            $deadline = new \DateTime($currentChallengeDeadline);

            // deny access to leaderboard of active challenge before the deadline, unless authentication is provided
            if ($deadline > new \DateTime() && $request->query->get('authentication') !== $leaderboardAuthentication) {
                throw new BadRequestException();
            }

            if (LeaderboardVersion::ACTIVE_CHALLENGE !== $leaderboardVersion) {
                $deadline = null;
            }
        }

        $users = $managerRegistry->getRepository(User::class)->findBy(['hideFromLeaderboard' => false]);
        $submissions = $this->getBestSubmissions($users, $challengeType, $evaluationType, $deadline);

        return [
            'challengeType' => $challengeType,
            'evaluationType' => $evaluationType,
            'leaderboardVersion' => $leaderboardVersion,
            'submissions' => $submissions,
        ];
    }

    /**
     * @param User[] $users
     */
    private function getBestSubmissions(array $users, ChallengeType $challenge, EvaluationType $evaluationType, ?\DateTime $deadline): array
    {
        $bestSubmissions = [];
        foreach ($users as $user) {
            $submissions = $user->getFilteredSubmissions($challenge, $evaluationType, $deadline);
            $bestSubmissions[] = $user->getBestSubmission($submissions);
        }

        $bestSubmissions = array_filter($bestSubmissions);

        usort($bestSubmissions, function (Submission $a, Submission $b): int {
            return $b->getEvaluationScore() <=> $a->getEvaluationScore();
        });

        return $bestSubmissions;
    }

    private function isSubmissionOnLeaderboard(Submission $submission, Request $request, ManagerRegistry $managerRegistry, string $currentChallengeDeadline, string $leaderboardAuthentication): bool
    {
        $leaderboardContent = $this->getLeaderboardContent($request, $managerRegistry, $currentChallengeDeadline, $leaderboardAuthentication);

        return in_array($submission, $leaderboardContent['submissions'], true);
    }
}
