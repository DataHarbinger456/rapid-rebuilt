<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\VINDecoderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VehicleController extends Controller
{
    public function __construct(
        protected VINDecoderService $vinDecoderService
    ) {}

    /**
     * Get vehicle for a case
     */
    public function show(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $vehicle = $case->vehicle;

        if (!$vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'Vehicle not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $vehicle,
        ]);
    }

    /**
     * Update vehicle information
     */
    public function update(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        // Only owner can update draft cases, staff can update any
        if (!$user->isStaff() && ($case->user_id !== $user->id || $case->status !== 'draft')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or case cannot be modified',
            ], 403);
        }

        $request->validate([
            'vin' => 'sometimes|string|size:17',
            'year' => 'sometimes|integer|min:1900|max:' . (date('Y') + 1),
            'make' => 'sometimes|string|max:100',
            'model' => 'sometimes|string|max:100',
            'body_type' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'title_number' => 'nullable|string|max:50',
            'title_state' => 'nullable|string|size:2',
            'title_brand' => 'nullable|string',
            'odometer_reading' => 'nullable|integer|min:0',
            'odometer_status' => 'nullable|in:actual,exceeds_limits,not_actual',
        ]);

        $vehicle = $case->vehicle;

        if (!$vehicle) {
            // Create vehicle if not exists
            $vehicle = Vehicle::create(array_merge(
                $request->all(),
                ['user_case_id' => $case->id]
            ));
        } else {
            $vehicle->update($request->all());
        }

        return response()->json([
            'success' => true,
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle->fresh(),
        ]);
    }

    /**
     * Decode VIN and update vehicle
     */
    public function decodeVin(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate(['vin' => 'required|string|size:17']);

        $vin = strtoupper($request->input('vin'));

        if (!$this->vinDecoderService->isValidFormat($vin)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid VIN format',
            ], 422);
        }

        $decoded = $this->vinDecoderService->decodeForVehicle($vin);

        if (empty($decoded['year'])) {
            return response()->json([
                'success' => false,
                'message' => 'Could not decode VIN',
            ], 422);
        }

        $vehicle = $case->vehicle;

        if (!$vehicle) {
            $vehicle = Vehicle::create(array_merge(
                $decoded,
                ['user_case_id' => $case->id]
            ));
        } else {
            $vehicle->update($decoded);
        }

        return response()->json([
            'success' => true,
            'message' => 'VIN decoded and vehicle updated',
            'data' => $vehicle->fresh(),
        ]);
    }

    /**
     * Decode VIN (public, for case creation wizard)
     */
    public function decodeVinPublic(Request $request): JsonResponse
    {
        $request->validate(['vin' => 'required|string|size:17']);

        $vin = strtoupper($request->input('vin'));

        if (!$this->vinDecoderService->isValidFormat($vin)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid VIN format',
            ], 422);
        }

        $decoded = $this->vinDecoderService->decodeForVehicle($vin);

        if (empty($decoded['year'])) {
            return response()->json([
                'success' => false,
                'message' => 'Could not decode VIN. Please enter vehicle details manually.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'VIN decoded successfully',
            'data' => $decoded,
        ]);
    }

    /**
     * Validate VIN format
     */
    public function validateVin(Request $request): JsonResponse
    {
        $request->validate(['vin' => 'required|string']);

        $vin = strtoupper($request->input('vin'));

        $isValid = $this->vinDecoderService->isValidFormat($vin);
        $checksumValid = $isValid ? $this->vinDecoderService->validateChecksum($vin) : false;
        $year = $isValid ? $this->vinDecoderService->getModelYear($vin) : null;
        $manufacturer = $isValid ? $this->vinDecoderService->getManufacturer($vin) : null;

        return response()->json([
            'success' => true,
            'data' => [
                'vin' => $vin,
                'valid_format' => $isValid,
                'valid_checksum' => $checksumValid,
                'model_year' => $year,
                'manufacturer' => $manufacturer,
            ],
        ]);
    }
}
