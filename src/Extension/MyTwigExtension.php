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
            new TwigFilter('truncate', $this->truncateFilter(...), ['needs_environment' => true]),
        ];
    }

    public function formatDateTimeFilter(?\DateTime $date): string
    {
        if ($date instanceof \DateTime) {
            return $date->format(DateTimeFormatter::DATE_TIME_FORMAT);
        }

        return '-';
    }

    /**
     * @source https://github.com/twigphp/Twig-extensions/blob/master/src/TextExtension.php
     */
    public function truncateFilter(Environment $env, $value, $length = 30, $preserve = false, string $separator = '...'): string
    {
        if (mb_strlen($value, $env->getCharset()) > $length) {
            if ($preserve) {
                // If breakpoint is on the last word, return the value without separator.
                if (false === ($breakpoint = mb_strpos($value, ' ', $length, $env->getCharset()))) {
                    return $value;
                }

                $length = $breakpoint;
            }

            return rtrim(mb_substr($value, 0, $length, $env->getCharset())) . $separator;
        }

        return $value;
    }
}
