<?php

declare(strict_types=1);

namespace App\Support;

class FractionConverter
{
    /**
     * Pattern matching fractions, mixed numbers, and decimals.
     * Examples: "1/2", "3/4", "1 1/2", "2.5", "3"
     */
    public const PATTERN = '/^\s*(\d+\s+)?\d+\/\d+\s*$|^\s*\d+(\.\d+)?\s*$/';

    /**
     * Convert a fraction or mixed number string to a decimal float.
     *
     * Accepts: "1/2", "3/4", "1 1/2", "2.5", "3"
     */
    public static function toDecimal(string $value): float
    {
        $value = trim($value);

        if (is_numeric($value)) {
            return (float) $value;
        }

        // Mixed number: "1 1/2"
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $value, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator === 0) {
                return (float) $whole;
            }

            return $whole + ($numerator / $denominator);
        }

        // Simple fraction: "1/2"
        if (preg_match('/^(\d+)\/(\d+)$/', $value, $matches)) {
            $numerator = (int) $matches[1];
            $denominator = (int) $matches[2];

            if ($denominator === 0) {
                return 0.0;
            }

            return $numerator / $denominator;
        }

        return 0.0;
    }

    /**
     * Convert a decimal float to a fraction string for display.
     *
     * Returns the simplest readable form: "1/2", "1 1/4", "3", "2.5" (if no clean fraction).
     */
    public static function toFraction(float $value): string
    {
        if ($value == (int) $value) {
            return (string) (int) $value;
        }

        $whole = (int) floor($value);
        $decimal = $value - $whole;

        $fraction = self::decimalToFraction($decimal);

        if ($fraction === null) {
            return (string) round($value, 2);
        }

        if ($whole === 0) {
            return $fraction;
        }

        return "$whole $fraction";
    }

    /**
     * Check if a string is a valid fraction, mixed number, or numeric value.
     */
    public static function isValid(string $value): bool
    {
        $value = trim($value);

        if (is_numeric($value)) {
            return (float) $value > 0;
        }

        return (bool) preg_match(self::PATTERN, $value) && self::toDecimal($value) > 0;
    }

    /**
     * Convert a decimal portion (0.0 to 1.0) to a common fraction string.
     *
     * @return string|null Null if no clean fraction representation exists.
     */
    protected static function decimalToFraction(float $decimal): ?string
    {
        $commonFractions = [
            '1/8' => 0.125,
            '1/4' => 0.25,
            '1/3' => 1 / 3,
            '3/8' => 0.375,
            '1/2' => 0.5,
            '5/8' => 0.625,
            '2/3' => 2 / 3,
            '3/4' => 0.75,
            '7/8' => 0.875,
        ];

        foreach ($commonFractions as $fraction => $fractionValue) {
            if (abs($decimal - $fractionValue) < 0.001) {
                return $fraction;
            }
        }

        return null;
    }
}
