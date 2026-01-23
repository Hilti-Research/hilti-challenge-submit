<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Service\Interfaces\EvaluationServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evaluation')]
class EvaluationController extends AbstractController
{
    #[Route('/callback', name: 'submission_evaluation_callback')]
    public function evaluationFinished(Request $request, EvaluationServiceInterface $evaluationService, string $awsCallbackSecret, ManagerRegistry $registry): Response
    {
        // test with https://localhost:8000/evaluation/callback?secret=callback_secret&key=019be987-737a-7e50-b776-b32aed24cb75_69731456df90d&status=success&error_log=&s3_bucket=challenge-reports-b0ceaaeb-fde7-4299-acdc-c9c0cc8b0c1a&s3_key=test_2026-challenge_2026-slam-evaluation.zip
        $secret = $request->query->get('secret');
        if ($awsCallbackSecret !== $secret) {
            if ($secret === 'invalid') {
                // give OK response to not confuse testers
                return new Response(null, Response::HTTP_NO_CONTENT);
            }

            throw new BadRequestException('Invalid callback secret');
        }

        $evaluationId = $request->query->get('key');
        $submission = $registry->getRepository(Submission::class)->findOneBy(['evaluationId' => $evaluationId]);
        if (!$submission) {
            throw new BadRequestException('Out of date evaluation: ' . $evaluationId);
        }

        $status = (string) $request->query->get('status');
        $errorLog = (string) $request->query->get('error_log');
        $s3Bucket = (string) $request->query->get('s3_bucket');
        $s3Key = (string) $request->query->get('s3_key');
        if (!$status || !$s3Bucket || !$s3Key) {
            throw new BadRequestException('Incomplete URl parameters');
        }

        $payload = ['status' => $status, 'error_msg' => $errorLog, 's3_bucket' => $s3Bucket, 's3_key' => $s3Key];
        $evaluationService->concludeEvaluation($submission, $evaluationId, $payload);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
