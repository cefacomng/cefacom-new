<?php

namespace App\Support;

/**
 * Normalises Nigerian mobile numbers to canonical E.164 (+234XXXXXXXXXX).
 * Accepts 0803..., 803..., 234803..., +234 803 ... with spaces, dashes or brackets.
 * Mobile numbers only (prefixes 70x, 71x, 80x, 81x, 90x, 91x). Landlines are rejected.
 * Batch 3 (contacts) must re-check these prefixes against current NCC numbering before relying on them for bulk imports.
 */
class NigerianPhone
{
    public static function normalize(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $input) ?? '';

        $national = match (true) {
            strlen($digits) === 13 && str_starts_with($digits, '234') => substr($digits, 3),
            strlen($digits) === 11 && str_starts_with($digits, '0') => substr($digits, 1),
            strlen($digits) === 10 => $digits,
            default => null,
        };

        if ($national === null || ! preg_match('/^[789][01]\d{8}$/', $national)) {
            return null;
        }

        return '+234'.$national;
    }
}
