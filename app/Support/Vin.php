<?php

namespace App\Support;

/**
 * ISO 3779 VIN check digit validation.
 *
 * Position 9 of a North American VIN is a checksum over the other 16
 * characters. It catches the single-character misreads and transpositions that
 * a vision model makes on a photographed title — in a run over 14 real salvage
 * titles, 6 of the extracted VINs failed it, and every one spot-checked
 * against the document was indeed a misread.
 *
 * A failing check digit means "a human must compare this to the document",
 * not "this is wrong": pre-1981 VINs predate the standard and are shorter or
 * unchecksummed, and a handful of legitimately-issued VINs are known to fail.
 * Never block on it.
 *
 * Passing is not proof either. The transliteration table maps several
 * characters to the same value (A=J=1, B=K=S=2, C=L=T=3 ...), so a swap within
 * one of those groups leaves the checksum unchanged — roughly 8% of possible
 * single-character misreads are invisible to it. A wrong VIN that validates
 * cleanly did occur in the live sample run.
 *
 * Pure and dependency-free by design so it can be unit tested without booting
 * the framework.
 */
final class Vin
{
    /**
     * The VIN alphabet excludes I, O and Q so they cannot be confused with
     * 1 and 0.
     */
    private const PATTERN = '/^[A-HJ-NPR-Z0-9]{17}$/';

    /**
     * Transliteration table from ISO 3779. Digits map to themselves.
     */
    private const VALUES = [
        'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
        'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
        'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9,
    ];

    /**
     * Positional weights. Position 9 (the check digit itself) weighs 0.
     */
    private const WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

    private const CHECK_POSITION = 8;

    /**
     * True only for a well-formed 17-character VIN whose check digit agrees.
     *
     * Anything malformed is false: the caller wants one question answered —
     * "can this be trusted without a human reading it off the document?"
     */
    public static function isValid(?string $vin): bool
    {
        $vin = self::normalize($vin);

        if ($vin === null) {
            return false;
        }

        return $vin[self::CHECK_POSITION] === self::expectedCheckDigit($vin);
    }

    /**
     * Well-formed enough to check: 17 characters from the VIN alphabet.
     *
     * Distinguishes "17 characters but the checksum disagrees" (probably one
     * misread character) from "not a VIN at all" (garbage), which are
     * different problems for a reviewer.
     */
    public static function isWellFormed(?string $vin): bool
    {
        return self::normalize($vin) !== null;
    }

    /**
     * The check digit this VIN's other 16 characters imply, or null if the
     * input is not well-formed. '0'-'9' or 'X'.
     */
    public static function expectedCheckDigit(?string $vin): ?string
    {
        $vin = self::normalize($vin);

        if ($vin === null) {
            return null;
        }

        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $char = $vin[$i];
            $value = is_numeric($char) ? (int) $char : (self::VALUES[$char] ?? 0);
            $sum += $value * self::WEIGHTS[$i];
        }

        $remainder = $sum % 11;

        return $remainder === 10 ? 'X' : (string) $remainder;
    }

    /**
     * Uppercased and trimmed if it looks like a VIN, null otherwise.
     */
    private static function normalize(?string $vin): ?string
    {
        if ($vin === null) {
            return null;
        }

        $vin = strtoupper(trim($vin));

        return preg_match(self::PATTERN, $vin) === 1 ? $vin : null;
    }
}
