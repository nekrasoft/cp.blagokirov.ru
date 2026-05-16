<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EmailList implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim((string) $value);

        if ($value === '') {
            return;
        }

        $emails = array_filter(
            array_map('trim', preg_split('/[;,\n]+/u', $value) ?: []),
            fn (string $email): bool => $email !== '',
        );

        if ($emails === []) {
            return;
        }

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                continue;
            }

            $fail('Поле Email должно содержать корректный email или список email через запятую или точку с запятой.');

            return;
        }
    }
}
