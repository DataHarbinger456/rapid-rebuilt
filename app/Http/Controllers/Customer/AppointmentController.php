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

        $startDateTime = Carbon::parse($request->selected_date . ' ' . $request->selected_time);

        $locationData = [
            'name' => $request->inspection_type === 'mobile'
                ? 'Mobile Inspection'
                : 'Office Inspection',
            'address' => $request->inspection_type === 'mobile' ? $request->address : null,
            'city' => $request->inspection_type === 'mobile' ? $request->city : null,
            'state' => $request->state ?? 'FL',
            'zip' => $request->inspection_type === 'mobile' ? $request->zip : null,
            'notes' => $request->notes,
        ];

        try {
            $this->appointmentService->scheduleAppointment(
                $case,
                $startDateTime,
                null,
                $locationData
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Unable to schedule this appointment. Please choose another time.');
        }

        return redirect()
            ->route('customer.cases.show', $case)
            ->with('success', 'Inspection scheduled successfully!');
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
