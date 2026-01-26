<?php

namespace App\Service;

use App\Entity\Submission;
use App\Enum\ChallengeType;
use App\Enum\EvaluationStatus;
use App\Enum\EvaluationType;
use App\Helper\DoctrineHelper;
use App\Service\Evaluation\AwsService;
use App\Service\Interfaces\EmailServiceInterface;
use App\Service\Interfaces\EvaluationServiceInterface;
use App\Service\Interfaces\PathServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\RouterInterface;

readonly class EvaluationService implements EvaluationServiceInterface
{
    public function __construct(private ManagerRegistry $registry, private AwsService $awsService, private EmailServiceInterface $emailService, private readonly PathServiceInterface $pathService, private RouterInterface $router, private string $awsCallbackSecret)
    {
    }

    public function startEvaluation(Submission $submission): bool
    {
        $submissionDirectory = $this->pathService->getSubmissionDirectory($submission);
        $solutionPath = $submissionDirectory . '/' . $submission->getSolutionFilename();

        $evaluationId = $submission->getId() . '_' . uniqid();
        $returnUrl = $this->router->generate('submission_evaluation_callback', ['secret' => $this->awsCallbackSecret], RouterInterface::ABSOLUTE_URL);

        if (!$this->awsService->startEvaluation($solutionPath, $returnUrl, $evaluationId, $submission->getChallengeType()->value, $submission->getEvaluationType()->value)) {
            return false;
        }

        $submission->setEvaluationId($evaluationId);
        $submission->setEvaluationStatus(EvaluationStatus::WAITING);
        $submission->setEvaluationVersion(EvaluationStatus::getMostRecentEvaluationVersion($submission->getChallengeType()));
        DoctrineHelper::persistAndFlush($this->registry, $submission);

        return true;
    }

    /**
     * @param array{'status': string, 'error_msg': string, 's3_bucket': string, 's3_key': string} $payload
     */
    public function concludeEvaluation(Submission $submission, string $evaluationId, array $payload): bool
    {
        $successful = 'success' === $payload['status'];

        $errorMessage = $payload['error_msg'];
        $submission->setEvaluationError($errorMessage);

        if ($successful) {
            if ($this->resolveEvaluation($submission, $payload)) {
                $submission->setEvaluationStatus(EvaluationStatus::SUCCESS);
            } else {
                $submission->setEvaluationStatus(EvaluationStatus::ERROR);
                $submission->setEvaluationErrorLog("Cannot parse evaluation report.");
            }
        } else {
            $submission->setEvaluationStatus(EvaluationStatus::ERROR);
        }

        DoctrineHelper::persistAndFlush($this->registry, $submission);

        if ($successful) {
            return $this->emailService->notifySubmissionEvaluated($submission);
        } else {
            return $this->emailService->notifySubmissionFailed($submission);
        }
    }

    /**
     * @param array{'status': string, 'error_msg': string, 's3_bucket': string, 's3_key': string} $payload
     */
    public function resolveEvaluation(Submission $submission, array $payload): bool
    {
        $bucket = $payload['s3_bucket'];
        $key = $payload['s3_key'];
        $submissionDirectory = $this->pathService->getSubmissionDirectory($submission);
        $targetPath = $submissionDirectory . '/' . $key;
        if (!$this->awsService->downloadFile($bucket, $key, $targetPath)) {
            return false;
        }

        $extractFolder = basename($key, '.zip');
        $extractPath = $submissionDirectory . '/' . $extractFolder;
        if (!$this->extract($targetPath, $extractPath)) {
            return false;
        }
        $submission->setEvaluationFolder($extractFolder);

        $score = $this->resolveEvaluationScore($extractPath, $submission->getChallengeType(), $submission->getEvaluationType());
        $submission->setEvaluationScore($score);

        $evaluationReportFilename = $this->resolveEvaluationReportFilename($extractPath, $submission->getChallengeType(), $submission->getEvaluationType());
        $submission->setEvaluationReportFilename($evaluationReportFilename);

        $errorDetailsFile = $extractPath . '/errors.txt';
        if (file_exists($errorDetailsFile)) {
            $errorDetails = file_get_contents($errorDetailsFile);
            $submission->setEvaluationErrorLog($errorDetails);
        }

        return true;
    }

    private function resolveEvaluationReportFilename(string $extractPath, ChallengeType $challengeType, EvaluationType $evaluationType): ?string
    {
        $filename = null;
        if ($challengeType === ChallengeType::CHALLENGE_2026) {
            if ($evaluationType === EvaluationType::SLAM) {
                $filename = 'slam.png';
            } else {
                $filename = 'localization.png';
            }
        }

        if (!$filename) {
            return null;
        }

        if (!is_file($extractPath . DIRECTORY_SEPARATOR . $filename)) {
            return null;
        }

        return $filename;
    }

    private function resolveEvaluationScore(string $extractPath, ChallengeType $challengeType, EvaluationType $evaluationType): ?float
    {
        if ($challengeType !== ChallengeType::CHALLENGE_2026 || !in_array($evaluationType, [EvaluationType::SLAM, EvaluationType::LOCATION], true)) {
            return null;
        }

        $csvPath = $extractPath . '/results.csv';
        if (!is_file($csvPath)) {
            return null;
        }

        $file = fopen($csvPath, 'r');
        $lastNoneEmptyCellInColumnM = null;
        while (($line = fgetcsv($file)) !== false) {
            if (!empty($line[9])) {
                $lastNoneEmptyCellInColumnM = $line[9];
            }
        }

        if (!$lastNoneEmptyCellInColumnM) {
            return null;
        }

        return floatval($lastNoneEmptyCellInColumnM);
    }

    private function extract(string $filePath, string $targetFolder): bool
    {
        $zip = new \ZipArchive();
        if (!$zip->open($filePath)) {
            return false;
        }

        $zip->extractTo($targetFolder);
        $zip->close();

        return true;
    }
}
