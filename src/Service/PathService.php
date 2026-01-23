<?php

namespace App\Service;

use App\Entity\Submission;
use App\Entity\User;
use App\Helper\Sanitizer;
use App\Service\Interfaces\PathServiceInterface;

readonly class PathService implements PathServiceInterface
{
    public function __construct(private string $submissionsDirectory)
    {
    }

    public function getUserDirectory(User $user): string
    {
        return $this->submissionsDirectory . '/' . Sanitizer::sanitizeFileName($user->getEmail());
    }

    public function getSubmissionDirectory(Submission $submission): string
    {
        $userFolder = $this->getUserDirectory($submission->getUser());

        return $userFolder . '/' . $submission->getIteration();
    }
}
