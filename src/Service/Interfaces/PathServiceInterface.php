<?php

namespace App\Service\Interfaces;

use App\Entity\Submission;
use App\Entity\User;

interface PathServiceInterface
{
    public function getUserDirectory(User $user): string;

    public function getSubmissionDirectory(Submission $submission): string;
}
