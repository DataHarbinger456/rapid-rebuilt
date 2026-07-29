<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Models\Component;
use App\Models\UploadedFile;
use App\Models\Setting;
use App\Services\CaseService;
use App\Services\VINDecoderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CaseController extends Controller
{
    protected CaseService $caseService;
    protected VINDecoderService $vinDecoder;

    public function __construct(CaseService $caseService, VINDecoderService $vinDecoder)
    {
        $this->caseService = $caseService;
        $this->vinDecoder = $vinDecoder;
    }

    /**
     * List all customer cases
     */
    public function index(Request $request)
    {
        $query = UserCase::with(['vehicle', 'documents', 'scheduling'])
            ->where('user_id', Auth::id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('case_number', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('vin', 'like', "%{$search}%");
                    });
            });
        }

        $cases = $query->latest()->paginate(10);

        return view('customer.cases.index', compact('cases'));
    }

    /**
     * Show case creation wizard
     */
    public function create()
    {
        $pricing = $this->getPricing();

        return view('customer.cases.create', compact('pricing'));
    }

    /**
     * Store a new case
     */
    public function store(Request $request)
    {
        $request->validate([
            'vin' => 'required|string|size:17',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'make' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'color' => 'required|string|max:50',
            'odometer' => 'required|integer|min:0',
            'title_state' => 'required|string|size:2',
            'components' => 'required|array|min:1',
            'components.*.type' => 'required|string',
            'components.*.source' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            // Create case
            $case = UserCase::create([
                'user_id' => Auth::id(),
                'status' => $request->has('save_draft') ? 'draft' : 'submitted',
                'total_amount' => $this->calculateEstimate($request),
            ]);

            // Create vehicle
            $vehicle = Vehicle::create([
                'user_case_id' => $case->id,
                'vin' => strtoupper($request->vin),
                'year' => $request->year,
                'make' => $request->make,
                'model' => $request->model,
                'body_type' => $request->body_style,
                'color' => $request->color,
                'engine_type' => $request->engine,
                'odometer_reading' => $request->odometer,
                'title_state' => $request->title_state,
            ]);

            // Create components
            foreach ($request->components as $componentData) {
                if (empty($componentData['type'])) continue;

                Component::create([
                    'user_case_id' => $case->id,
                    'vehicle_id' => $vehicle->id,
                    'part_type' => $componentData['type'],
                    'source' => $componentData['source'],
                    'source_vin' => $componentData['source_vin'] ?? null,
                    'notes' => $componentData['description'] ?? null,
                ]);
            }

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $type => $files) {
                    $fileList = is_array($files) ? $files : [$files];
                    foreach ($fileList as $file) {
                        if ($file && $file->isValid()) {
                            $path = $file->store("cases/{$case->id}/documents", 'private');

                            UploadedFile::create([
                                'user_case_id' => $case->id,
                                'user_id' => Auth::id(),
                                'uploaded_by' => Auth::id(),
                                'document_type' => $type,
                                'original_name' => $file->getClientOriginalName(),
                                'file_path' => $path,
                                'mime_type' => $file->getMimeType(),
                                'file_size' => $file->getSize(),
                                'status' => 'pending',
                            ]);
                        }
                    }
                }
            }

            // Add timeline entry
            $case->timeline()->create([
                'event_type' => 'status_changed',
                'title' => $request->has('save_draft') ? 'Case Draft Created' : 'Case Submitted',
                'description' => $request->has('save_draft')
                    ? 'Case draft was created and saved'
                    : 'Case was submitted for processing',
            ]);

            DB::commit();

            if ($request->has('save_draft')) {
                return redirect()
                    ->route('customer.cases.edit', $case)
                    ->with('success', 'Draft saved successfully.');
            }

            return redirect()
                ->route('customer.cases.show', $case)
                ->with('success', 'Case submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withInput()
                ->with('error', 'Failed to create case. Please try again.');
        }
    }

    /**
     * Show case details
     */
    public function show(UserCase $case)
    {
        $this->authorize('view', $case);

        $case->load([
            'vehicle',
            'components',
            'documents',
            'timeline' => fn($q) => $q->latest(),
            'scheduling.inspector',
            'inspection',
        ]);

        return view('customer.cases.show', compact('case'));
    }

    /**
     * Edit case (for drafts)
     */
    public function edit(UserCase $case)
    {
        $this->authorize('update', $case);

        if ($case->status !== 'draft') {
            return redirect()
                ->route('customer.cases.show', $case)
                ->with('error', 'Only draft cases can be edited.');
        }

        $case->load(['vehicle', 'components', 'documents']);
        $pricing = $this->getPricing();

        return view('customer.cases.edit', compact('case', 'pricing'));
    }

    /**
     * Delete draft case
     */
    public function destroy(UserCase $case)
    {
        $this->authorize('delete', $case);

        if ($case->status !== 'draft') {
            return back()->with('error', 'Only draft cases can be deleted.');
        }

        // Delete associated files
        foreach ($case->documents as $doc) {
            Storage::disk('private')->delete($doc->file_path);
        }

        $case->delete();

        return redirect()
            ->route('customer.cases.index')
            ->with('success', 'Draft deleted successfully.');
    }

    /**
     * Show document upload page for a case
     */
    public function documents(UserCase $case)
    {
        $this->authorize('update', $case);

        $case->load('documents');

        return view('customer.cases.documents', compact('case'));
    }

    /**
     * Show scheduling page for a case
     */
    public function schedule(UserCase $case)
    {
        $this->authorize('update', $case);

        if (!in_array($case->status, ['paid', 'documents_complete', 'pending_scheduling'])) {
            return redirect()
                ->route('customer.cases.show', $case)
                ->with('error', 'This case is not ready for scheduling.');
        }

        $case->load(['vehicle', 'components']);
        $pricing = $this->getPricing();

        return view('customer.appointments.schedule', compact('case', 'pricing'));
    }

    /**
     * Show payment page for a case
     */
    public function pay(UserCase $case)
    {
        $this->authorize('update', $case);

        if ($case->status !== 'pending_payment') {
            return redirect()
                ->route('customer.cases.show', $case)
                ->with('error', 'This case does not require payment.');
        }

        $case->load(['vehicle', 'components']);

        return view('customer.cases.pay', compact('case'));
    }

    /**
     * Get pricing settings
     */
    private function getPricing(): array
    {
        $defaults = [
            'base_price' => 150,
            'rush_fee' => 50,
            'component_price' => 10,
            'mobile_inspection_fee' => 75,
        ];

        $settings = Setting::whereIn('key', array_keys($defaults))
            ->pluck('value', 'key')
            ->toArray();

        return array_merge($defaults, $settings);
    }

    /**
     * Calculate estimated amount
     */
    private function calculateEstimate(Request $request): float
    {
        $pricing = $this->getPricing();
        $total = floatval($pricing['base_price']);

        $componentCount = count(array_filter($request->components ?? [], fn($c) => !empty($c['type'])));
        $total += $componentCount * floatval($pricing['component_price']);

        return $total;
    }
}
