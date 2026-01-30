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
        /** find URL on clowdwatch, adapt the key */
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
