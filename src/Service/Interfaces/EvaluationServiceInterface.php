<?php

namespace App\Service\Interfaces;

use App\Entity\Submission;

interface EvaluationServiceInterface
{
    public function startEvaluation(Submission $submission): bool;

    /**
     * @param array{'status': string, 'error_msg': string, 's3_bucket': string, 's3_key': string} $payload
     */
    public function concludeEvaluation(Submission $submission, string $evaluationId, array $payload): bool;
}
