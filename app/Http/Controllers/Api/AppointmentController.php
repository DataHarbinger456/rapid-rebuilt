<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleAppointmentRequest;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    /**
     * Get available dates
     */
    public function availableDates(Request $request): JsonResponse
    {
        $daysAhead = $request->input('days', 30);
        $inspectorId = $request->input('inspector_id');

        $inspector = $inspectorId ? \App\Models\User::find($inspectorId) : null;

        $dates = $this->appointmentService->getAvailableDates($daysAhead, $inspector);

        return response()->json([
            'success' => true,
            'data' => $dates,
        ]);
    }

    /**
     * Get available slots for a specific date
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $date = Carbon::parse($request->input('date'));
        $inspectorId = $request->input('inspector_id');

        $inspector = $inspectorId ? \App\Models\User::find($inspectorId) : null;

        $slots = $this->appointmentService->getAvailableSlots($date, $inspector);

        return response()->json([
            'success' => true,
            'data' => $slots,
        ]);
    }

    /**
     * Schedule an appointment
     */
    public function store(ScheduleAppointmentRequest $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $data = $request->validated();
        $dateTime = Carbon::parse($data['datetime']);

        $inspector = isset($data['inspector_id'])
            ? \App\Models\User::find($data['inspector_id'])
            : null;

        $locationData = [
            'name' => $data['location_name'] ?? null,
            'address' => $data['location_address'] ?? null,
            'city' => $data['location_city'] ?? null,
            'state' => $data['location_state'] ?? 'FL',
            'zip' => $data['location_zip'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        try {
            $appointment = $this->appointmentService->scheduleAppointment(
                $case,
                $dateTime,
                $inspector,
                $locationData
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment scheduled successfully',
                'data' => $appointment->load(['userCase', 'inspector']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get appointment details
     */
    public function show(Request $request, UserFormScheduling $appointment): JsonResponse
    {
        $user = $request->user();
        $case = $appointment->userCase;

        if (!$user->isStaff() && !$user->isInspector() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $appointment->load(['userCase.user', 'userCase.vehicle', 'inspector']),
        ]);
    }

    /**
     * Reschedule appointment
     */
    public function reschedule(Request $request, UserFormScheduling $appointment): JsonResponse
    {
        $user = $request->user();
        $case = $appointment->userCase;

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'datetime' => 'required|date|after:now',
            'reason' => 'nullable|string|max:500',
        ]);

        $newDateTime = Carbon::parse($request->input('datetime'));
        $reason = $request->input('reason');

        try {
            $newAppointment = $this->appointmentService->rescheduleAppointment(
                $appointment,
                $newDateTime,
                $reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Appointment rescheduled successfully',
                'data' => $newAppointment->load(['userCase', 'inspector']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request, UserFormScheduling $appointment): JsonResponse
    {
        $user = $request->user();
        $case = $appointment->userCase;

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->appointmentService->cancelAppointment($appointment, $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => 'Appointment cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm appointment
     */
    public function confirm(Request $request, UserFormScheduling $appointment): JsonResponse
    {
        $user = $request->user();
        $case = $appointment->userCase;

        if ($case->user_id !== $user->id && !$user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->appointmentService->confirmAppointment($appointment);

        return response()->json([
            'success' => true,
            'message' => 'Appointment confirmed successfully',
            'data' => $appointment->fresh(),
        ]);
    }

    /**
     * Get today's appointments (staff/inspector)
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && !$user->isInspector()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($user->isInspector()) {
            $appointments = $this->appointmentService->getInspectorSchedule($user, now());
        } else {
            $appointments = $this->appointmentService->getTodaysAppointments();
        }

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }

    /**
     * Get user's appointments
     */
    public function myAppointments(Request $request): JsonResponse
    {
        $user = $request->user();

        $appointments = UserFormScheduling::whereHas('userCase', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereNotIn('status', ['cancelled', 'rescheduled'])
        ->with(['userCase.vehicle', 'inspector'])
        ->orderBy('start')
        ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }
}
