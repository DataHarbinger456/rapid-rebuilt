<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\UserFormScheduling;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AgendaController extends Controller
{
    public function __construct(
        protected AppointmentService $appointmentService
    ) {}

    /**
     * Show inspector's agenda
     */
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isInspector()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))
            : now();

        $appointments = $this->appointmentService->getInspectorSchedule($user, $date);

        $data = [
            'date' => $date,
            'appointments' => $appointments,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.agenda', $data);
    }

    /**
     * Get weekly view
     */
    public function week(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInspector()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->input('start')
            ? Carbon::parse($request->input('start'))
            : now()->startOfWeek();

        $weekData = [];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);
            $appointments = $this->appointmentService->getInspectorSchedule($user, $date);

            $weekData[] = [
                'date' => $date->format('Y-m-d'),
                'day_name' => $date->format('l'),
                'is_today' => $date->isToday(),
                'appointments' => $appointments,
                'count' => $appointments->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $startDate->format('Y-m-d'),
                'week_end' => $startDate->copy()->addDays(6)->format('Y-m-d'),
                'days' => $weekData,
            ],
        ]);
    }

    /**
     * Get today's appointments
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInspector()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $appointments = $this->appointmentService->getInspectorSchedule($user, now());

        return response()->json([
            'success' => true,
            'data' => [
                'date' => now()->format('Y-m-d'),
                'appointments' => $appointments,
                'total' => $appointments->count(),
                'pending' => $appointments->where('status', 'pending')->count(),
                'confirmed' => $appointments->where('status', 'confirmed')->count(),
                'in_progress' => $appointments->where('status', 'in_progress')->count(),
                'completed' => $appointments->where('status', 'completed')->count(),
            ],
        ]);
    }

    /**
     * Get upcoming appointments
     */
    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInspector()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $limit = $request->input('limit', 10);

        $appointments = UserFormScheduling::where('inspector_id', $user->id)
            ->upcoming()
            ->with(['userCase.user', 'userCase.vehicle'])
            ->orderBy('start')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $appointments,
        ]);
    }

    /**
     * Get appointment details for inspection
     */
    public function show(Request $request, UserFormScheduling $appointment): View|JsonResponse
    {
        $user = $request->user();

        if ($appointment->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $appointment->load([
            'userCase.user',
            'userCase.vehicle',
            'userCase.components',
            'userCase.inspection',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $appointment,
            ]);
        }

        return view('inspector.appointment', ['appointment' => $appointment]);
    }
}
