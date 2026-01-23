<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum EvaluationStatus: string implements TranslatableInterface
{
    case WAITING = 'WAITING';
    case SUCCESS = 'SUCCESS';
    case ERROR = 'ERROR';

    public static function getMostRecentEvaluationVersion(ChallengeType $challengeType): int
    {
        return match ($challengeType) {
            ChallengeType::CHALLENGE_2026 => 1,
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->value, [], 'enum_evaluation_status');
    }
}
