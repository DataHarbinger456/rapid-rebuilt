<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\Component;
use App\Models\CaseTimeline;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ComponentController extends Controller
{
    /**
     * List components for a case
     */
    public function index(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && !$user->isInspector() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $components = $case->components()->orderBy('part_type')->get();

        return response()->json([
            'success' => true,
            'data' => $components,
        ]);
    }

    /**
     * Add a component to case
     */
    public function store(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'part_type' => 'required|string',
            'condition' => 'required|in:new,used,repaired,original',
            'source' => 'nullable|string|max:255',
            'source_phone' => 'nullable|string|max:20',
            'source_address' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ]);

        $component = Component::create(array_merge(
            $request->all(),
            ['user_case_id' => $case->id]
        ));

        CaseTimeline::log(
            $case->id,
            'component_added',
            'Component added',
            "Added {$request->input('part_type')} ({$request->input('condition')})"
        );

        return response()->json([
            'success' => true,
            'message' => 'Component added successfully',
            'data' => $component,
        ], 201);
    }

    /**
     * Update a component
     */
    public function update(Request $request, UserCase $case, Component $component): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($component->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Component not found',
            ], 404);
        }

        $request->validate([
            'part_type' => 'sometimes|string',
            'condition' => 'sometimes|in:new,used,repaired,original',
            'source' => 'nullable|string|max:255',
            'source_phone' => 'nullable|string|max:20',
            'source_address' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
        ]);

        $component->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Component updated successfully',
            'data' => $component->fresh(),
        ]);
    }

    /**
     * Delete a component
     */
    public function destroy(Request $request, UserCase $case, Component $component): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($component->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Component not found',
            ], 404);
        }

        // Don't allow deletion if already verified
        if ($component->verified) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete verified component',
            ], 422);
        }

        $partType = $component->part_type;
        $component->delete();

        CaseTimeline::log(
            $case->id,
            'component_removed',
            'Component removed',
            "Removed {$partType}"
        );

        return response()->json([
            'success' => true,
            'message' => 'Component deleted successfully',
        ]);
    }

    /**
     * Get available component types
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Component::PART_TYPES,
        ]);
    }

    /**
     * Get component conditions
     */
    public function conditions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Component::CONDITIONS,
        ]);
    }
}
