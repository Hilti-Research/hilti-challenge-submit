<?php

namespace App\Entity;

use App\Entity\Submission\EvaluationTrait;
use App\Entity\Submission\UserSubmissionTrait;
use App\Entity\Traits\IdTrait;
use App\Entity\Traits\TimeTrait;
use App\Enum\ChallengeType;
use App\Enum\EvaluationType;
use App\Repository\SubmissionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubmissionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Submission
{
    use IdTrait;
    use TimeTrait;
    use UserSubmissionTrait;
    use EvaluationTrait;

    public static function createFrom(User $user, ?Submission $lastSubmission, string $firstTimeDescription): self
    {
        $submission = new self();
        $submission->setUser($user);
        $submission->setChallengeType(ChallengeType::getActiveChallenge());
        $submission->setEvaluationType(EvaluationType::SLAM);

        if (!$lastSubmission) {
            $submission->setDescription($firstTimeDescription);
            $submission->setIteration(1);

            return $submission;
        }

        $submission->setIteration($lastSubmission->getIteration() + 1);
        if ($lastSubmission->getChallengeType() === ChallengeType::getActiveChallenge()) {
            $submission->setEvaluationType($lastSubmission->getEvaluationType());
        }

        return $submission;
    }
}
