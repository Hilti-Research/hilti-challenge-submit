<?php

namespace App\Entity\Submission;

use App\Enum\EvaluationStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait EvaluationTrait
{
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $evaluationId = null;
    #[ORM\Column(type: Types::STRING, enumType: EvaluationStatus::class, nullable: true)]
    private ?EvaluationStatus $evaluationStatus = null;
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $evaluationVersion = 0;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $evaluationError = null;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $evaluationErrorLog = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $evaluationFolder = null;
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $evaluationScore = null;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $evaluationReportFilename = null;

    public function getEvaluationId(): ?string
    {
        return $this->evaluationId;
    }

    public function setEvaluationId(?string $evaluationId): void
    {
        $this->evaluationId = $evaluationId;
    }

    public function getEvaluationStatus(): ?EvaluationStatus
    {
        return $this->evaluationStatus;
    }

    public function setEvaluationStatus(?EvaluationStatus $evaluationStatus): void
    {
        $this->evaluationStatus = $evaluationStatus;
    }

    public function getEvaluationVersion(): ?int
    {
        return $this->evaluationVersion;
    }

    public function setEvaluationVersion(?int $evaluationVersion): void
    {
        $this->evaluationVersion = $evaluationVersion;
    }

    public function getEvaluationError(): ?string
    {
        return $this->evaluationError;
    }

    public function setEvaluationError(?string $evaluationError): void
    {
        $this->evaluationError = $evaluationError;
    }

    public function getEvaluationErrorLog(): ?string
    {
        return $this->evaluationErrorLog;
    }

    public function setEvaluationErrorLog(?string $evaluationErrorLog): void
    {
        $this->evaluationErrorLog = $evaluationErrorLog;
    }

    public function getEvaluationFolder(): ?string
    {
        return $this->evaluationFolder;
    }

    public function setEvaluationFolder(?string $evaluationFolder): void
    {
        $this->evaluationFolder = $evaluationFolder;
    }

    public function getEvaluationScore(): ?float
    {
        return $this->evaluationScore;
    }

    public function setEvaluationScore(?float $evaluationScore): void
    {
        $this->evaluationScore = $evaluationScore;
    }

    public function getEvaluationReportFilename(): ?string
    {
        return $this->evaluationReportFilename;
    }

    public function setEvaluationReportFilename(?string $evaluationReportFilename): void
    {
        $this->evaluationReportFilename = $evaluationReportFilename;
    }

    public function isMostRecentEvaluationVersion(): bool
    {
        return EvaluationStatus::getMostRecentEvaluationVersion($this->getChallengeType()) === $this->getEvaluationVersion();
    }
}
