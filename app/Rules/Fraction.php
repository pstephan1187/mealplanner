<?php

namespace App\Rules;

use App\Support\FractionConverter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Fraction implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('The :attribute must be a number or fraction (e.g., 1/2, 1 1/4).');

            return;
        }

        if (! FractionConverter::isValid((string) $value)) {
            $fail('The :attribute must be a number or fraction (e.g., 1/2, 1 1/4).');
        }
    }
}
