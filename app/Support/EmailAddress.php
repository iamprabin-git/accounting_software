<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmailAddress
{
    /**
     * Trim, remove stray whitespace, convert IDN domains to ASCII when possible, and lowercase for storage and lookup.
     */
    public static function normalize(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $email = trim($email);
        if ($email === '') {
            return null;
        }

        $email = preg_replace('/\s+/', '', $email) ?? $email;

        if (! str_contains($email, '@')) {
            return Str::lower($email);
        }

        [$local, $domain] = explode('@', $email, 2);
        $domain = trim($domain);

        if (function_exists('idn_to_ascii')) {
            $ascii = @idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if ($ascii !== false && $ascii !== '') {
                $domain = $ascii;
            }
        }

        return Str::lower($local.'@'.$domain);
    }

    /**
     * RFC-aware validation plus PHP filter with Unicode local-parts (internationalized addresses).
     */
    public static function laravelRule(): \Illuminate\Validation\Rules\Email
    {
        return Rule::email()
            ->rfcCompliant()
            ->withNativeValidation(allowUnicode: true);
    }
}
