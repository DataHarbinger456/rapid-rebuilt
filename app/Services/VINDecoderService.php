<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VINDecoderService
{
    protected const NHTSA_API_URL = 'https://vpic.nhtsa.dot.gov/api/vehicles/decodevin';
    protected const CACHE_TTL = 86400; // 24 hours

    /**
     * Decode VIN using NHTSA API
     */
    public function decode(string $vin): ?array
    {
        $vin = strtoupper(trim($vin));

        if (!$this->isValidFormat($vin)) {
            return null;
        }

        $cacheKey = "vin_decode.{$vin}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($vin) {
            try {
                $response = Http::timeout(10)->get(self::NHTSA_API_URL . "/{$vin}", [
                    'format' => 'json',
                ]);

                if ($response->successful()) {
                    return $this->parseNHTSAResponse($response->json());
                }

                Log::warning('VIN decode API failed', [
                    'vin' => $vin,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('VIN decode exception', [
                    'vin' => $vin,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Validate VIN format
     */
    public function isValidFormat(string $vin): bool
    {
        // VIN must be exactly 17 characters
        if (strlen($vin) !== 17) {
            return false;
        }

        // VIN can only contain A-H, J-N, P, R-Z, and 0-9 (no I, O, Q)
        if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $vin)) {
            return false;
        }

        return true;
    }

    /**
     * Validate VIN with checksum (position 9)
     */
    public function validateChecksum(string $vin): bool
    {
        if (!$this->isValidFormat($vin)) {
            return false;
        }

        $transliterations = [
            'A' => 1, 'B' => 2, 'C' => 3, 'D' => 4, 'E' => 5, 'F' => 6, 'G' => 7, 'H' => 8,
            'J' => 1, 'K' => 2, 'L' => 3, 'M' => 4, 'N' => 5, 'P' => 7, 'R' => 9,
            'S' => 2, 'T' => 3, 'U' => 4, 'V' => 5, 'W' => 6, 'X' => 7, 'Y' => 8, 'Z' => 9,
        ];

        $weights = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 17; $i++) {
            $char = $vin[$i];
            $value = is_numeric($char) ? (int) $char : ($transliterations[$char] ?? 0);
            $sum += $value * $weights[$i];
        }

        $checkDigit = $sum % 11;
        $expectedCheck = $checkDigit === 10 ? 'X' : (string) $checkDigit;

        return $vin[8] === $expectedCheck;
    }

    /**
     * Get model year from VIN (position 10)
     */
    public function getModelYear(string $vin): ?int
    {
        if (!$this->isValidFormat($vin)) {
            return null;
        }

        $yearCodes = [
            'A' => 2010, 'B' => 2011, 'C' => 2012, 'D' => 2013, 'E' => 2014,
            'F' => 2015, 'G' => 2016, 'H' => 2017, 'J' => 2018, 'K' => 2019,
            'L' => 2020, 'M' => 2021, 'N' => 2022, 'P' => 2023, 'R' => 2024,
            'S' => 2025, 'T' => 2026, 'V' => 2027, 'W' => 2028, 'X' => 2029,
            'Y' => 2030,
            // Earlier years (1980-2009)
            '1' => 2001, '2' => 2002, '3' => 2003, '4' => 2004, '5' => 2005,
            '6' => 2006, '7' => 2007, '8' => 2008, '9' => 2009,
        ];

        $yearCode = $vin[9];

        return $yearCodes[$yearCode] ?? null;
    }

    /**
     * Get manufacturer from VIN (positions 1-3 = WMI)
     */
    public function getManufacturer(string $vin): ?string
    {
        if (!$this->isValidFormat($vin)) {
            return null;
        }

        $wmi = substr($vin, 0, 3);

        // Common WMI codes
        $manufacturers = [
            '1G1' => 'Chevrolet', '1G2' => 'Pontiac', '1GC' => 'Chevrolet Truck',
            '1FA' => 'Ford', '1FB' => 'Ford', '1FC' => 'Ford', '1FD' => 'Ford',
            '1FM' => 'Ford', '1FT' => 'Ford Truck',
            '1HG' => 'Honda', '1J4' => 'Jeep', '1N4' => 'Nissan',
            '2HG' => 'Honda (Canada)', '2T1' => 'Toyota (Canada)',
            '3FA' => 'Ford (Mexico)', '3VW' => 'Volkswagen (Mexico)',
            '4T1' => 'Toyota', '4US' => 'BMW',
            '5FN' => 'Honda', '5TD' => 'Toyota', '5YJ' => 'Tesla',
            'JHM' => 'Honda', 'JN1' => 'Nissan', 'JT2' => 'Toyota', 'JTD' => 'Toyota',
            'KM8' => 'Hyundai', 'KNA' => 'Kia',
            'WAU' => 'Audi', 'WBA' => 'BMW', 'WDB' => 'Mercedes-Benz', 'WVW' => 'Volkswagen',
            'YV1' => 'Volvo', 'ZFF' => 'Ferrari',
        ];

        return $manufacturers[$wmi] ?? null;
    }

    /**
     * Parse NHTSA API response
     */
    protected function parseNHTSAResponse(array $data): array
    {
        if (!isset($data['Results'])) {
            return [];
        }

        $results = collect($data['Results']);

        $getValue = function ($variable) use ($results) {
            $item = $results->firstWhere('Variable', $variable);
            return $item && !empty($item['Value']) ? trim($item['Value']) : null;
        };

        return [
            'year' => (int) $getValue('Model Year') ?: null,
            'make' => $getValue('Make'),
            'model' => $getValue('Model'),
            'trim' => $getValue('Trim'),
            'body_type' => $getValue('Body Class'),
            'vehicle_type' => $getValue('Vehicle Type'),
            'doors' => $getValue('Doors'),
            'engine_type' => $getValue('Engine Model'),
            'engine_displacement' => $getValue('Displacement (L)'),
            'engine_cylinders' => $getValue('Engine Number of Cylinders'),
            'fuel_type' => $getValue('Fuel Type - Primary'),
            // A hybrid reports Gasoline as its primary fuel, so the primary
            // value alone cannot identify one: a 2018 Prius decodes as
            // Gasoline / Electric / 'Strong HEV (Hybrid Electric Vehicle)'.
            // Section 2 of the HSMV 82040 has its own hybrid boxes, so the two
            // variables that do reveal it are captured here and stored in
            // vehicles.vin_decoded_data.
            'fuel_type_secondary' => $getValue('Fuel Type - Secondary'),
            'electrification_level' => $getValue('Electrification Level'),
            'transmission' => $getValue('Transmission Style'),
            'drive_type' => $getValue('Drive Type'),
            'plant_city' => $getValue('Plant City'),
            'plant_country' => $getValue('Plant Country'),
            'manufacturer' => $getValue('Manufacturer Name'),
            'gvwr' => $getValue('GVWR'),
            'error_code' => $getValue('Error Code'),
            'error_text' => $getValue('Error Text'),
        ];
    }

    /**
     * Decode VIN and return formatted data for vehicle creation
     */
    public function decodeForVehicle(string $vin): array
    {
        $decoded = $this->decode($vin);

        if (!$decoded) {
            return [
                'vin' => strtoupper($vin),
                'vin_decoded_data' => null,
            ];
        }

        return [
            'vin' => strtoupper($vin),
            'year' => $decoded['year'],
            'make' => $decoded['make'],
            'model' => $decoded['model'],
            'body_type' => $decoded['body_type'],
            'engine_type' => $decoded['engine_type'],
            'fuel_type' => $decoded['fuel_type'],
            'vin_decoded_data' => $decoded,
        ];
    }
}
