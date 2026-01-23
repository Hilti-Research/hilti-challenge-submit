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

enum EmailType: string
{
    case RECOVER_CONFIRM = 'RECOVERY_CONFIRM';
    case SUBMISSION_EVALUATED_ADMIN_NOTIFICATION = 'SUBMISSION_EVALUATED_ADMIN_NOTIFICATION';
    case SUBMISSION_EVALUATED = 'SUBMISSION_EVALUATED';
    case SUBMISSION_EVALUATION_FAILED = 'SUBMISSION_EVALUATION_FAILED';
}
