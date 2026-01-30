<?php

/*
 * This file is part of the thealternativezurich/triage project.
 *
 * (c) Florian Moser <git@famoser.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Extension;

use App\Helper\DateTimeFormatter;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MyTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatDateTime', $this->formatDateTimeFilter(...)),
        ];
    }

    public function formatDateTimeFilter(?\DateTime $date): string
    {
        if ($date instanceof \DateTime) {
            return $date->format(DateTimeFormatter::DATE_TIME_FORMAT);
        }

        return '-';
    }
}
