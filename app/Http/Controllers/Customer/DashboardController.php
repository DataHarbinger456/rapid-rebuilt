<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\UploadedFile;
use App\Models\UserFormScheduling;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Customer dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Get case statistics
        $activeCases = UserCase::where('user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled', 'draft'])
            ->count();

        $completedCases = UserCase::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Get pending documents
        $pendingDocuments = UploadedFile::whereHas('userCase', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->whereIn('status', ['pending', 'rejected'])
            ->count();

        // Get upcoming appointments
        $upcomingAppointments = UserFormScheduling::whereHas('userCase', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('start', '>', now())
            ->where('status', 'confirmed')
            ->count();

        // Get recent cases
        $recentCases = UserCase::with(['vehicle', 'documents'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Get next appointment
        $nextAppointment = UserFormScheduling::whereHas('userCase', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('start', '>', now())
            ->where('status', 'confirmed')
            ->orderBy('start')
            ->first();

        // Get recent notifications
        $recentNotifications = Notification::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // Get required actions
        $requiredActions = $this->getRequiredActions($user);

        return view('customer.dashboard', compact(
            'activeCases',
            'completedCases',
            'pendingDocuments',
            'upcomingAppointments',
            'recentCases',
            'nextAppointment',
            'recentNotifications',
            'requiredActions'
        ));
    }

    /**
     * Get list of required actions for the user
     */
    private function getRequiredActions($user): array
    {
        $actions = [];

        // Check for cases pending documents
        $pendingDocsCases = UserCase::where('user_id', $user->id)
            ->where('status', 'pending_documents')
            ->get();

        foreach ($pendingDocsCases as $case) {
            $actions[] = [
                'title' => "Upload documents for Case #{$case->case_number}",
                'description' => 'Required documents are missing',
                'url' => route('customer.cases.documents', $case),
                'button_text' => 'Upload Documents',
            ];
        }

        // Check for rejected documents
        $rejectedDocs = UploadedFile::whereHas('userCase', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('status', 'rejected')
            ->get();

        foreach ($rejectedDocs as $doc) {
            $actions[] = [
                'title' => "Re-upload {$doc->document_type_label}",
                'description' => $doc->rejection_reason ?? 'Document was rejected',
                'url' => route('customer.documents.reupload', $doc),
                'button_text' => 'Re-upload',
            ];
        }

        // Check for cases pending payment
        $pendingPayment = UserCase::where('user_id', $user->id)
            ->where('status', 'pending_payment')
            ->get();

        foreach ($pendingPayment as $case) {
            $actions[] = [
                'title' => "Payment required for Case #{$case->case_number}",
                'description' => 'Complete payment to proceed',
                'url' => route('customer.cases.pay', $case),
                'button_text' => 'Pay Now',
            ];
        }

        // Check for cases pending scheduling
        $pendingScheduling = UserCase::where('user_id', $user->id)
            ->where('status', 'pending_scheduling')
            ->get();

        foreach ($pendingScheduling as $case) {
            $actions[] = [
                'title' => "Schedule inspection for Case #{$case->case_number}",
                'description' => 'Ready to schedule your inspection',
                'url' => route('customer.cases.schedule', $case),
                'button_text' => 'Schedule Now',
            ];
        }

        return $actions;
    }
}
