<?php

namespace App\Helper;

class Sanitizer
{
    public static function sanitizeFileName(string $value): string
    {
        $filename = strtolower($value);

        return preg_replace('/[^a-zA-Z0-9_@.-]+/', '-', $filename);
    }
}
