<?php

namespace App\Support;

/**
 * A dealer's sales tax registration number.
 *
 * Florida issues a Certificate of Registration (form DR-11) carrying a
 * thirteen-digit number, printed in a 2-10-1 grouping: 78-8012345678-4. That
 * shape is checkable, so a Florida dealer's entry is validated and a typo is
 * caught at signup rather than on a filed form.
 *
 * Every other state prints its own thing, and we are not going to encode fifty
 * formats we cannot verify, so an out-of-state dealer's number is kept as free
 * text exactly as they typed it.
 *
 * Digits are what gets stored, because the dashes are presentation: two dealers
 * typing 78-8012345678-4 and 7880123456784 have entered the same number and
 * should be stored identically. The dashes go back on at print time so the box
 * on the 82040 matches the certificate the dealer is holding.
 */
final class SalesTaxNumber
{
    /**
     * Digits in a Florida DR-11 certificate number.
     */
    public const FLORIDA_LENGTH = 13;

    /**
     * Everything that is not a digit, removed.
     *
     * Dealers type these with dashes, spaces or neither, and all three mean the
     * same number.
     */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    /**
     * Is this dealer a Florida one?
     *
     * users.states is free text and holds whatever registration accepted, so
     * this tolerates case, padding and the state's full name rather than
     * assuming a clean two-letter code.
     */
    public static function isFlorida(?string $state): bool
    {
        $normalised = strtoupper(trim((string) $state));

        return $normalised === 'FL' || $normalised === 'FLORIDA';
    }

    /**
     * A valid Florida number is thirteen digits, however it was punctuated.
     */
    public static function isValidFlorida(?string $value): bool
    {
        return strlen(self::digits($value)) === self::FLORIDA_LENGTH;
    }

    /**
     * What goes in the database.
     *
     * Florida numbers are reduced to their digits. Anything else is kept
     * verbatim, minus surrounding whitespace, because we cannot know which
     * characters carry meaning in another state's format.
     */
    public static function forStorage(?string $value, ?string $state): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return self::isFlorida($state) ? self::digits($value) : $value;
    }

    /**
     * What gets printed in Section 7 of the HSMV 82040.
     *
     * A Florida number is grouped 2-10-1 to match the certificate. Anything
     * else is printed as stored: an out-of-state number, or a Florida one that
     * predates this validation and is not thirteen digits, is passed through
     * untouched rather than forced into a shape it does not have.
     */
    public static function format(?string $value, ?string $state): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (!self::isFlorida($state) || !self::isValidFlorida($value)) {
            return $value;
        }

        $digits = self::digits($value);

        return substr($digits, 0, 2)
            . '-' . substr($digits, 2, 10)
            . '-' . substr($digits, 12, 1);
    }
}
