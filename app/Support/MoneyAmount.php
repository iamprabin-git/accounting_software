<?php

namespace App\Support;

/**
 * Convert validated numeric money input to integer cents without float drift (e.g. 19.99).
 */
class MoneyAmount
{
    /**
     * @param  int|float|string|null  $value  Laravel "numeric" validated input
     */
    public static function numericInputToCents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            if ($value < 0) {
                return 0;
            }

            return $value * 100;
        }

        $s = trim((string) $value);
        $s = str_replace(',', '', $s);

        if ($s === '' || ! is_numeric($s)) {
            return 0;
        }

        if (function_exists('bcadd')) {
            $normalized = bcadd($s, '0', 2);

            return (int) bcmul($normalized, '100', 0);
        }

        return (int) round((float) $s * 100);
    }
}
