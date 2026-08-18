<?php

namespace App\Http\Controllers\Inspector;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\UserFormScheduling;
use App\Services\InspectionService;
use App\Services\PDFGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function __construct(
        protected InspectionService $inspectionService,
        protected PDFGeneratorService $pdfService
    ) {}

    /**
     * Show inspection checklist view
     */
    public function checklist(Request $request, UserFormScheduling $appointment): View|JsonResponse
    {
        $user = $request->user();

        if ($appointment->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $case = $appointment->userCase;
        $inspection = $case->inspection;

        // Start inspection if not started
        if (!$inspection) {
            $inspection = $this->inspectionService->startInspection($appointment, $user);
        }

        $inspection->load(['photos', 'userCase.vehicle', 'userCase.components']);

        $data = [
            'appointment' => $appointment,
            'inspection' => $inspection,
            'case' => $case,
            'vehicle' => $case->vehicle,
            'components' => $case->components,
            'checklist_template' => $this->inspectionService->getChecklistTemplate(),
            'required_photos' => $this->inspectionService->getRequiredPhotoTypes(),
            'missing_photos' => $this->inspectionService->getMissingPhotos($inspection),
            'can_pass' => $this->inspectionService->canPass($inspection),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.inspection.checklist', $data);
    }

    /**
     * Show inspection photos management
     */
    public function photos(Request $request, Inspection $inspection): View|JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $inspection->load('photos');

        $data = [
            'inspection' => $inspection,
            'photos' => $inspection->photos,
            'required_photos' => $this->inspectionService->getRequiredPhotoTypes(),
            'missing_photos' => $this->inspectionService->getMissingPhotos($inspection),
            'has_all_required' => $this->inspectionService->hasAllRequiredPhotos($inspection),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.inspection.photos', $data);
    }

    /**
     * Show component verification
     */
    public function components(Request $request, Inspection $inspection): View|JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $case = $inspection->userCase;
        $components = $case->components;

        $verifiedCount = $components->where('verified', true)->count();
        $totalCount = $components->count();

        $data = [
            'inspection' => $inspection,
            'components' => $components,
            'verified_count' => $verifiedCount,
            'total_count' => $totalCount,
            'all_verified' => $verifiedCount === $totalCount,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.inspection.components', $data);
    }

    /**
     * Show inspection summary
     */
    public function summary(Request $request, Inspection $inspection): View|JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $inspection->load([
            'photos',
            'userCase.vehicle',
            'userCase.components',
            'userCase.user',
            'scheduling',
        ]);

        $canPass = $this->inspectionService->canPass($inspection);

        $data = [
            'inspection' => $inspection,
            'case' => $inspection->userCase,
            'vehicle' => $inspection->userCase->vehicle,
            'components' => $inspection->userCase->components,
            'photos' => $inspection->photos,
            'can_pass' => $canPass,
            'missing_photos' => $this->inspectionService->getMissingPhotos($inspection),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.inspection.summary', $data);
    }

    /**
     * Generate documents view
     */
    public function documents(Request $request, Inspection $inspection): View|JsonResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        if ($inspection->result !== 'passed') {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Documents can only be generated for passed inspections',
                ], 422);
            }
            return back()->withErrors(['error' => 'Documents can only be generated for passed inspections']);
        }

        $case = $inspection->userCase;
        $canGenerate = $this->pdfService->canGenerateForms($case);
        $generatedDocuments = $this->pdfService->getGeneratedDocuments($case);

        $data = [
            'inspection' => $inspection,
            'case' => $case,
            'can_generate' => $canGenerate,
            'generated_documents' => $generatedDocuments,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.inspection.documents', $data);
    }

    /**
     * Generate the document package, for the browser form on the documents
     * screen.
     *
     * The /api/.../generate-documents endpoint answers JSON behind
     * auth:sanctum, which a session-cookie form POST cannot satisfy while
     * EnsureFrontendRequestsAreStateful is off — and JSON is the wrong shape
     * for a form submit anyway. This twin runs the same service call and
     * redirects back to the screen the inspector is already on, with a flash
     * message carrying either the result or the reason nothing was generated.
     */
    public function generateDocuments(Request $request, Inspection $inspection): RedirectResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            abort(403);
        }

        if ($inspection->result !== 'passed') {
            return back()->withErrors(['error' => 'Documents can only be generated for passed inspections']);
        }

        $case = $inspection->userCase;

        try {
            $packagePath = $this->pdfService->generateFullPackage($case);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        $generated = $this->pdfService->getGeneratedDocuments($case);

        return back()->with('success',
            'Document package generated: ' . count($generated)
            . ' document' . (count($generated) === 1 ? '' : 's')
            . '. See MANIFEST.txt inside for what still needs completing by hand.'
        );
    }

    /**
     * Print package view
     *
     * RedirectResponse joins the return union: the no-documents path calls
     * back()->withErrors(), which is a redirect, and PHP enforces the declared
     * return type at runtime — so an inspector visiting Print before
     * generating anything hit a TypeError instead of the intended flash
     * redirect.
     */
    public function print(Request $request, Inspection $inspection): View|JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($inspection->inspector_id !== $user->id && !$user->isStaff()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $case = $inspection->userCase;
        $generatedDocuments = $this->pdfService->getGeneratedDocuments($case);

        if (empty($generatedDocuments)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents generated yet',
                ], 422);
            }
            return back()->withErrors(['error' => 'No documents generated yet']);
        }

        $data = [
            'inspection' => $inspection,
            'case' => $case,
            'documents' => $generatedDocuments,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        }

        return view('inspector.inspection.print', $data);
    }

    /**
     * Inspector's completed inspections history
     */
    public function history(Request $request): View|JsonResponse
    {
        $user = $request->user();

        if (!$user->isInspector()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $inspections = Inspection::byInspector($user->id)
            ->whereIn('result', ['passed', 'failed'])
            ->with(['userCase.vehicle', 'userCase.user'])
            ->orderBy('completed_at', 'desc')
            ->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $inspections,
            ]);
        }

        return view('inspector.history', ['inspections' => $inspections]);
    }
}
