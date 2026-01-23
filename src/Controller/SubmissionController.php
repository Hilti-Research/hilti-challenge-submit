<?php

namespace App\Controller;

use App\Entity\Submission;
use App\Entity\User;
use App\Enum\ChallengeType;
use App\Enum\EvaluationStatus;
use App\Enum\EvaluationType;
use App\Enum\FlashType;
use App\Form\Submission\SubmissionType;
use App\Helper\DoctrineHelper;
use App\Helper\Sanitizer;
use App\Service\EvaluationService;
use App\Service\Interfaces\PathServiceInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubmissionController extends AbstractController
{
    #[Route('/submission/mine', name: 'submission_mine')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (0 === count($user->getSubmissions())) {
            return $this->render('submission/mine_empty.html.twig', ['user']);
        }

        $submissions2026Slam = $user->getFilteredSubmissions(ChallengeType::CHALLENGE_2026, EvaluationType::SLAM);
        $bestSubmission2026Slam = User::getBestSubmission($submissions2026Slam);

        $submissions2026Location = $user->getFilteredSubmissions(ChallengeType::CHALLENGE_2026, EvaluationType::LOCATION);
        $bestSubmission2026Location = User::getBestSubmission($submissions2026Location);

        $allShownSubmissions = [...$submissions2026Slam, ...$submissions2026Location];
        $lastSubmission = $allShownSubmissions[0] ?? null;
        array_all($allShownSubmissions, function (Submission $submission) use (&$lastSubmission) {
            $lastSubmission = $submission->getIteration() > $lastSubmission?->getIteration() ? $submission : $lastSubmission;
        });

        return $this->render('submission/mine.html.twig', [
            'user' => $user,
            'submissions2026Slam' => $submissions2026Slam, 'bestSubmission2026Slam' => $bestSubmission2026Slam,
            'submissions2026Location' => $submissions2026Location, 'bestSubmission2026Location' => $bestSubmission2026Location,
            'lastSubmission' => $lastSubmission
        ]);
    }

    #[Route('/submission/new', name: 'submission_new')]
    public function new(Request $request, ManagerRegistry $managerRegistry, TranslatorInterface $translator, EvaluationService $evaluationService, PathServiceInterface $pathService, int $currentChallengeSubmissionsPerDay): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user->hasTeamSetupCompleted()) {
            return $this->redirectToRoute('user_setup');
        }

        $lastSuccessfulSubmissions = $managerRegistry->getRepository(Submission::class)->findLastNotFailedSubmissions(ChallengeType::getActiveChallenge(), $currentChallengeSubmissionsPerDay);
        $oneDayAgo = (new \DateTime())->sub(new \DateInterval('PT1H'));
        $submissionsOfLastDay = array_filter($lastSuccessfulSubmissions, function (Submission $submission) use ($oneDayAgo) {
            return $submission->getCreatedAt() > $oneDayAgo;
        });
        $availableSubmissions = $user->isAdmin() ? 99 : $currentChallengeSubmissionsPerDay - count($submissionsOfLastDay);
        if ($availableSubmissions === 0) {
            $message = $translator->trans('new.error.no_new_submission_for_active_challenge', [], 'submission');
            $this->addFlash(FlashType::DANGER->value, $message);
        }

        $lastSubmission = $managerRegistry->getRepository(Submission::class)->findOneBy([], ["createdAt" => "DESC"]);
        $firstTimeDescription = $translator->trans('new.first_time_description', [], 'submission');
        $submission = Submission::createFrom($user, $lastSubmission, $firstTimeDescription);

        $form = $this->createForm(SubmissionType::class, $submission);
        $form->add('submit', SubmitType::class, ['translation_domain' => 'submission', 'label' => 'new.submit']);

        $form->handleRequest($request);
        if (
            $form->isSubmitted() && $form->isValid() &&
            ($submission->getChallengeType() !== ChallengeType::getActiveChallenge() || $availableSubmissions > 0) &&
            $this->tryAddSubmission($submission, $translator, $pathService)
        ) {
            DoctrineHelper::persistAndFlush($managerRegistry, $submission);

            if ($evaluationService->startEvaluation($submission)) {
                $message = $translator->trans('new.success.submission_is_scored', [], 'submission');
                $this->addFlash(FlashType::SUCCESS->value, $message);
            } else {
                $message = $translator->trans('_messages.error.cannot_start_evaluation', [], 'submission');
                $this->addFlash(FlashType::DANGER->value, $message);
            }

            return $this->redirectToRoute('submission_mine');
        }

        if (isset($error)) {
            $this->addFlash(FlashType::DANGER->value, $error);
        }

        return $this->render('submission/new.html.twig', ['form' => $form, 'submission' => $submission, "availableSubmissions" => $availableSubmissions]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/submission/{submission}/reevaluate', name: 'submission_reevaluate')]
    public function reevaluate(Submission $submission, EvaluationService $evaluationService, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $submission);

        if ($submission->isMostRecentEvaluationVersion()) {
            $message = $translator->trans('reevaluate.error.submission_already_most_recent', [], 'submission');
            $this->addFlash(FlashType::DANGER->value, $message);
        } else {
            if ($evaluationService->startEvaluation($submission)) {
                $message = $translator->trans('reevaluate.success.submission_is_reevaluated', [], 'submission');
                $this->addFlash(FlashType::SUCCESS->value, $message);
            } else {
                $message = $translator->trans('_messages.error.cannot_start_evaluation', [], 'submission');
                $this->addFlash(FlashType::DANGER->value, $message);
            }
        }

        return $this->redirectToRoute('submission_mine');
    }

    #[Route('/submission/{submission}/report', name: 'submission_report')]
    public function report(Submission $submission, PathServiceInterface $pathService): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $submission);

        if (!$submission->getReportFilename()) {
            throw new NotFoundHttpException();
        }

        $submissionDirectory = $pathService->getSubmissionDirectory($submission);
        $path = $submissionDirectory . '/' . $submission->getReportFilename();

        return $this->file($path);
    }

    #[Route('/submission/{submission}/solution', name: 'submission_solution')]
    public function solution(Submission $submission, PathServiceInterface $pathService): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $submission);

        $submissionDirectory = $pathService->getSubmissionDirectory($submission);
        $path = $submissionDirectory . '/' . $submission->getSolutionFilename();

        return $this->file($path);
    }

    #[Route('/submission/{submission}/evaluation_report/{evaluationId}', name: 'submission_evaluation_report')]
    public function evaluationReport(Submission $submission, string $evaluationId, PathServiceInterface $pathService): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $submission);

        if ($submission->getEvaluationId() !== $evaluationId) {
            throw new NotFoundHttpException();
        }

        $submissionDirectory = $pathService->getSubmissionDirectory($submission);
        $path = $submissionDirectory . '/' . $submission->getEvaluationFolder() . "/" . $submission->getEvaluationReportFilename();

        return $this->file($path, "evaluation_" . $submission->getIteration() . "." . pathinfo($submission->getEvaluationReportFilename(), PATHINFO_EXTENSION));
    }

    private function tryAddSubmission(Submission $submission, TranslatorInterface $translator, PathServiceInterface $pathService): bool
    {
        $submissionDirectory = $pathService->getSubmissionDirectory($submission);

        if (($solutionFile = $submission->getSolutionFile()) !== null) {
            $solutionFileInvalidMessage = $translator->trans('new.error.solution_file_invalid', [], 'submission');
            $solutionFileEndingInvalidMessage = $translator->trans('new.error.solution_file_ending_invalid', [], 'submission');
            if (!($newSolutionFileName = $this->tryUploadFile($submissionDirectory, $solutionFile, $solutionFileInvalidMessage, 'zip', $solutionFileEndingInvalidMessage))) {
                return false;
            }

            $submission->setSolutionFilename($newSolutionFileName);
        }

        if (($reportFile = $submission->getReportFile()) !== null) {
            $reportFileInvalidMessage = $translator->trans('new.error.report_file_invalid', [], 'submission');
            $reportFileEndingInvalidMessage = $translator->trans('new.error.report_file_ending_invalid', [], 'submission');
            if (!($newReportFileName = $this->tryUploadFile($submissionDirectory, $reportFile, $reportFileInvalidMessage, 'pdf', $reportFileEndingInvalidMessage))) {
                return false;
            }

            $submission->setReportFilename($newReportFileName);
        }

        return true;
    }

    private function tryUploadFile(string $directory, UploadedFile $file, string $fileInvalidMessage, string $ending, string $fileEndingInvalidMessage): false|string
    {
        // check if upload succeeded
        if (!$file->isValid()) {
            $this->addFlash(FlashType::DANGER->value, $fileInvalidMessage);

            return false;
        }

        // check if potentially the correct format
        if ($file->getClientOriginalExtension() !== $ending) {
            $this->addFlash(FlashType::DANGER->value, $fileEndingInvalidMessage);

            return false;
        }

        // create a new file name
        $newFileName = Sanitizer::sanitizeFileName($file->getClientOriginalName());

        // create directory on demand
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            $file->move($directory, $newFileName);
        } catch (FileException) {
            $this->addFlash(FlashType::DANGER->value, $fileEndingInvalidMessage);

            return false;
        }

        return $newFileName;
    }
}
