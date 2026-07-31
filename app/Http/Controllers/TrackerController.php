<?php

namespace App\Http\Controllers;

use App\Models\UserCase;
use Illuminate\Http\Request;

class TrackerController extends Controller
{
    /**
     * Public stages shown on the tracker, mapped to the minimum internal
     * status (by STATUSES order) that marks the stage as reached.
     */
    private const STAGES = [
        ['label' => 'Documents Received', 'reached_at' => 'pending_documents', 'event_types' => ['document_uploaded', 'document_approved']],
        ['label' => 'Inspection Scheduled', 'reached_at' => 'scheduled', 'event_types' => ['appointment_scheduled', 'appointment_confirmed']],
        ['label' => 'Vehicle Inspected', 'reached_at' => 'inspection_in_progress', 'event_types' => ['inspection_completed', 'inspection_passed']],
        ['label' => 'Forms Filed', 'reached_at' => 'ready_for_dmv', 'event_types' => ['sent_to_dmv', 'documents_generated']],
        ['label' => 'Title Issued', 'reached_at' => 'title_issued', 'event_types' => ['title_issued']],
    ];

    /**
     * Public, tokenized case tracker. Route is protected by the `signed`
     * middleware — no auth, no PII, no documents, no prices.
     */
    public function show(Request $request, UserCase $case)
    {
        $case->load('vehicle');

        $statusOrder = array_keys(UserCase::STATUSES);
        $currentIndex = array_search($case->status, $statusOrder);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        $timeline = $case->timeline()
            ->where('is_system_event', true)
            ->orderBy('created_at')
            ->get(['event_type', 'created_at']);

        $stages = [];
        foreach (self::STAGES as $stage) {
            $reachedIndex = array_search($stage['reached_at'], $statusOrder);
            $reached = $currentIndex >= $reachedIndex && $case->status !== 'cancelled';

            $date = null;
            if ($reached) {
                $event = $timeline->whereIn('event_type', $stage['event_types'])->first();
                $date = $event?->created_at;
            }

            $stages[] = [
                'label' => $stage['label'],
                'reached' => $reached,
                'date' => $date,
            ];
        }

        return view('tracker.show', [
            'caseNumber' => $case->case_number,
            'status' => $case->status,
            'statusLabel' => UserCase::STATUSES[$case->status] ?? $case->status,
            'vehicle' => $case->vehicle
                ? trim("{$case->vehicle->year} {$case->vehicle->make} {$case->vehicle->model}")
                : null,
            'stages' => $stages,
            'cancelled' => $case->status === 'cancelled',
        ]);
    }
}
