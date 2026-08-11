<?php

namespace App\Support;

use App\Models\Vehicle;

/**
 * Which of Section 2's fuel boxes on the HSMV 82040 a vehicle ticks.
 *
 * The form has a box for each alternative fuel and none for petrol or diesel,
 * so an ordinary car correctly ticks nothing here.
 *
 * The awkward part is hybrids. NHTSA reports a hybrid's primary fuel as
 * Gasoline, so vehicles.fuel_type alone can never identify one: a 2018 Prius
 * decodes as Gasoline / Electric / 'Strong HEV (Hybrid Electric Vehicle)'.
 * The answer lives in two other variables, captured by VINDecoderService into
 * vehicles.vin_decoded_data, which is where this reads them from.
 *
 * Values are matched loosely because they are free text from a federal API
 * that we do not control, and because staff may type the column by hand.
 */
final class FuelType
{
    public static function isElectric(?Vehicle $vehicle): bool
    {
        // A hybrid also carries Electric as a fuel, but it has its own box, so
        // the pure-electric box must not also be ticked for one.
        if ($vehicle === null || self::isHybrid($vehicle)) {
            return false;
        }

        return self::matches(self::primary($vehicle), ['electric'])
            || self::matches(self::level($vehicle), ['bev', 'battery electric']);
    }

    /**
     * Compressed natural gas. The form separates compressed from liquefied.
     */
    public static function isCompressedNaturalGas(?Vehicle $vehicle): bool
    {
        return self::matches(self::anyFuel($vehicle), ['cng', 'compressed natural gas']);
    }

    /**
     * Liquefied natural gas. Propane and LPG are a different fuel again and
     * have no box on this form, so they deliberately match nothing.
     */
    public static function isLiquefiedNaturalGas(?Vehicle $vehicle): bool
    {
        return self::matches(self::anyFuel($vehicle), ['lng', 'liquefied natural gas']);
    }

    public static function isGasElectricHybrid(?Vehicle $vehicle): bool
    {
        return $vehicle !== null
            && self::isHybrid($vehicle)
            && !self::matches(self::primary($vehicle), ['diesel']);
    }

    public static function isDieselElectricHybrid(?Vehicle $vehicle): bool
    {
        return $vehicle !== null
            && self::isHybrid($vehicle)
            && self::matches(self::primary($vehicle), ['diesel']);
    }

    /**
     * A hybrid burns fuel and carries a motor. NHTSA says so in one of two
     * ways: an electrification level of HEV, PHEV or mild hybrid, or a
     * secondary fuel of Electric alongside a combustion primary.
     *
     * A battery electric vehicle is not a hybrid: its level names BEV or FCEV,
     * both excluded here, and it has no combustion primary to pair with.
     */
    private static function isHybrid(Vehicle $vehicle): bool
    {
        $level = self::level($vehicle);

        if (self::matches($level, ['bev', 'battery electric', 'fcev', 'fuel cell'])) {
            return false;
        }

        if (self::matches($level, ['hev', 'phev', 'hybrid', 'mild'])) {
            return true;
        }

        return self::matches(self::secondary($vehicle), ['electric'])
            && self::matches(self::primary($vehicle), ['gasoline', 'petrol', 'diesel', 'flex']);
    }

    private static function primary(Vehicle $vehicle): ?string
    {
        // The column is the authority: it is what staff can correct by hand.
        return $vehicle->fuel_type ?: self::decoded($vehicle, 'fuel_type');
    }

    private static function secondary(Vehicle $vehicle): ?string
    {
        return self::decoded($vehicle, 'fuel_type_secondary');
    }

    private static function level(Vehicle $vehicle): ?string
    {
        return self::decoded($vehicle, 'electrification_level');
    }

    /**
     * Both fuel values as one string, for the tests that do not care which
     * slot the fuel arrived in.
     */
    private static function anyFuel(?Vehicle $vehicle): ?string
    {
        if ($vehicle === null) {
            return null;
        }

        return trim(self::primary($vehicle) . ' ' . self::secondary($vehicle)) ?: null;
    }

    /**
     * vin_decoded_data is a JSON column and older rows predate these keys, so
     * every read has to tolerate a missing one.
     */
    private static function decoded(Vehicle $vehicle, string $key): ?string
    {
        $decoded = $vehicle->vin_decoded_data;

        if (!is_array($decoded)) {
            return null;
        }

        $value = $decoded[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @param array<int, string> $needles lowercase fragments to look for
     */
    private static function matches(?string $value, array $needles): bool
    {
        if ($value === null) {
            return false;
        }

        $haystack = strtolower($value);

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
