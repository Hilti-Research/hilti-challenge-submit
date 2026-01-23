<?php

namespace App\Entity\Submission;

use App\Entity\User;
use App\Enum\ChallengeType;
use App\Enum\EvaluationType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

trait UserSubmissionTrait
{
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'submissions')]
    private User $user;

    #[ORM\Column(type: Types::INTEGER)]
    private int $iteration;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, enumType: ChallengeType::class)]
    private ChallengeType $challengeType;
    #[ORM\Column(type: Types::STRING, enumType: EvaluationType::class)]
    private EvaluationType $evaluationType;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $reportFilename = null;
    #[ORM\Column(type: Types::STRING)]
    private string $solutionFilename;

    #[Assert\File(maxSize: '20M')]
    private ?UploadedFile $reportFile = null;
    #[Assert\File(maxSize: '20M')]
    private ?UploadedFile $solutionFile = null;

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getIteration(): int
    {
        return $this->iteration;
    }

    public function setIteration(int $iteration): void
    {
        $this->iteration = $iteration;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getChallengeType(): ChallengeType
    {
        return $this->challengeType;
    }

    public function setChallengeType(ChallengeType $challengeType): void
    {
        $this->challengeType = $challengeType;
    }

    public function getEvaluationType(): EvaluationType
    {
        return $this->evaluationType;
    }

    public function setEvaluationType(EvaluationType $evaluationType): void
    {
        $this->evaluationType = $evaluationType;
    }

    public function getReportFilename(): ?string
    {
        return $this->reportFilename;
    }

    public function setReportFilename(?string $reportFilename): void
    {
        $this->reportFilename = $reportFilename;
    }

    public function getSolutionFilename(): string
    {
        return $this->solutionFilename;
    }

    public function setSolutionFilename(string $solutionFilename): void
    {
        $this->solutionFilename = $solutionFilename;
    }

    public function getReportFile(): ?UploadedFile
    {
        return $this->reportFile;
    }

    public function setReportFile(?UploadedFile $reportFile): void
    {
        $this->reportFile = $reportFile;
    }

    public function getSolutionFile(): ?UploadedFile
    {
        return $this->solutionFile;
    }

    public function setSolutionFile(?UploadedFile $solutionFile): void
    {
        $this->solutionFile = $solutionFile;
    }
}
