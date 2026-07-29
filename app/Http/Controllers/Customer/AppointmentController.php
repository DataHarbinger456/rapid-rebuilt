<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    protected AppointmentService $appointmentService;

    public function __construct(AppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    /**
     * List all customer appointments
     */
    public function index()
    {
        $user = Auth::user();

        $upcomingAppointments = UserFormScheduling::with(['userCase.vehicle', 'inspector'])
            ->whereHas('userCase', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('start', '>', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start')
            ->get();

        $pastAppointments = UserFormScheduling::with(['userCase.vehicle', 'inspection'])
            ->whereHas('userCase', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('start', '<=', now())
            ->orderByDesc('start')
            ->take(10)
            ->get();

        $nextAppointment = $upcomingAppointments->first();

        return view('customer.appointments.index', compact(
            'upcomingAppointments',
            'pastAppointments',
            'nextAppointment'
        ));
    }

    /**
     * Show appointment details
     */
    public function show(UserFormScheduling $appointment)
    {
        $this->authorize('view', $appointment);

        $appointment->load(['userCase.vehicle', 'inspector', 'inspection']);

        return view('customer.appointments.show', compact('appointment'));
    }

    /**
     * Store a new appointment
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_case_id' => 'required|exists:user_cases,id',
            'selected_date' => 'required|date|after:today',
            'selected_time' => 'required|string',
            'inspection_type' => 'required|in:office,mobile',
            'address' => 'required_if:inspection_type,mobile',
            'city' => 'required_if:inspection_type,mobile',
            'zip' => 'required_if:inspection_type,mobile',
        ]);

        $case = UserCase::findOrFail($request->user_case_id);
        $this->authorize('update', $case);

        // Check if case is ready for scheduling
        if (!in_array($case->status, ['paid', 'documents_complete', 'pending_scheduling'])) {
            return back()->with('error', 'This case is not ready for scheduling.');
        }

        // Parse date and time
        $startDateTime = Carbon::parse($request->selected_date . ' ' . $request->selected_time);
        $endDateTime = $startDateTime->copy()->addHour();

        // Check slot availability
        if (!$this->appointmentService->isSlotAvailable($startDateTime)) {
            return back()->with('error', 'This time slot is no longer available. Please select another time.');
        }

        // Build location string
        $location = null;
        if ($request->inspection_type === 'mobile') {
            $location = "{$request->address}, {$request->city}, {$request->state} {$request->zip}";
        }

        // Create appointment
        $appointment = UserFormScheduling::create([
            'user_case_id' => $case->id,
            'user_id' => Auth::id(),
            'start' => $startDateTime,
            'end' => $endDateTime,
            'status' => 'confirmed',
            'inspection_type' => $request->inspection_type,
            'location' => $location,
            'notes' => $request->notes,
        ]);

        // Update case status
        $case->update(['status' => 'scheduled']);

        // Add timeline entry
        $case->timeline()->create([
            'title' => 'Inspection Scheduled',
            'description' => "Inspection scheduled for {$startDateTime->format('F j, Y \\a\\t g:i A')}",
            'type' => 'appointment',
            'metadata' => [
                'appointment_id' => $appointment->id,
                'inspection_type' => $request->inspection_type,
            ],
        ]);

        // Send confirmation (handled by AppointmentService)
        $this->appointmentService->sendConfirmation($appointment);

        return redirect()
            ->route('customer.cases.show', $case)
            ->with('success', 'Inspection scheduled successfully! A confirmation email has been sent.');
    }

    /**
     * Show reschedule form
     */
    public function reschedule(UserFormScheduling $appointment)
    {
        $this->authorize('update', $appointment);

        if (!$appointment->canReschedule()) {
            return redirect()
                ->route('customer.appointments.show', $appointment)
                ->with('error', 'This appointment cannot be rescheduled. Please contact support.');
        }

        $appointment->load('userCase.vehicle');

        return view('customer.appointments.reschedule', compact('appointment'));
    }

    /**
     * Process reschedule
     */
    public function processReschedule(Request $request, UserFormScheduling $appointment)
    {
        $this->authorize('update', $appointment);

        if (!$appointment->canReschedule()) {
            return back()->with('error', 'This appointment cannot be rescheduled.');
        }

        $request->validate([
            'selected_date' => 'required|date|after:today',
            'selected_time' => 'required|string',
        ]);

        $oldDateTime = $appointment->start;
        $startDateTime = Carbon::parse($request->selected_date . ' ' . $request->selected_time);
        $endDateTime = $startDateTime->copy()->addHour();

        // Check slot availability
        if (!$this->appointmentService->isSlotAvailable($startDateTime, $appointment->id)) {
            return back()->with('error', 'This time slot is no longer available.');
        }

        $appointment->update([
            'start' => $startDateTime,
            'end' => $endDateTime,
        ]);

        // Add timeline entry
        $appointment->userCase->timeline()->create([
            'title' => 'Inspection Rescheduled',
            'description' => "Rescheduled from {$oldDateTime->format('M j, Y g:i A')} to {$startDateTime->format('M j, Y g:i A')}",
            'type' => 'appointment',
            'metadata' => ['appointment_id' => $appointment->id],
        ]);

        // Send updated confirmation
        $this->appointmentService->sendRescheduleNotification($appointment, $oldDateTime);

        return redirect()
            ->route('customer.appointments.show', $appointment)
            ->with('success', 'Appointment rescheduled successfully!');
    }

    /**
     * Cancel appointment
     */
    public function cancel(Request $request, UserFormScheduling $appointment)
    {
        $this->authorize('update', $appointment);

        if (!$appointment->canCancel()) {
            return back()->with('error', 'This appointment cannot be cancelled. Please contact support.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_at' => now(),
        ]);

        // Update case status
        $appointment->userCase->update(['status' => 'pending_scheduling']);

        // Add timeline entry
        $appointment->userCase->timeline()->create([
            'title' => 'Inspection Cancelled',
            'description' => $request->reason,
            'type' => 'appointment',
            'metadata' => ['appointment_id' => $appointment->id],
        ]);

        return redirect()
            ->route('customer.cases.show', $appointment->userCase)
            ->with('success', 'Appointment cancelled. You can reschedule when ready.');
    }
}
