<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\UserCase;
use App\Models\UploadedFile;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService
    ) {}

    /**
     * List documents for a case
     */
    public function index(Request $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $documents = UploadedFile::where('user_case_id', $case->id)
            ->orderBy('document_type')
            ->orderBy('created_at', 'desc')
            ->get();

        $requiredDocuments = $this->documentService->getRequiredDocuments();
        $missingDocuments = $this->documentService->getMissingDocuments($case);

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => $documents,
                'required' => $requiredDocuments,
                'missing' => $missingDocuments,
                'all_uploaded' => empty($missingDocuments),
                'all_approved' => $this->documentService->allDocumentsApproved($case),
            ],
        ]);
    }

    /**
     * Upload a document
     */
    public function store(UploadDocumentRequest $request, UserCase $case): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $file = $request->file('file');
        $documentType = $request->input('document_type');

        try {
            $document = $this->documentService->uploadDocument(
                $case,
                $file,
                $documentType,
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get document details
     */
    public function show(Request $request, UserCase $case, UploadedFile $document): JsonResponse
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($document->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $document->load(['uploader', 'reviewer']),
        ]);
    }

    /**
     * Delete a document
     */
    public function destroy(Request $request, UserCase $case, UploadedFile $document): JsonResponse
    {
        $user = $request->user();

        // Only owner can delete pending documents, staff can delete any
        if (!$user->isStaff()) {
            if ($case->user_id !== $user->id || $document->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized or document cannot be deleted',
                ], 403);
            }
        }

        if ($document->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        try {
            $this->documentService->deleteDocument($document);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download a document
     */
    public function download(Request $request, UserCase $case, UploadedFile $document)
    {
        $user = $request->user();

        if (!$user->isStaff() && $case->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($document->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }

        if (!$document->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return $document->download();
    }

    /**
     * Approve a document (staff only)
     */
    public function approve(Request $request, UserCase $case, UploadedFile $document): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($document->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document does not belong to this case',
            ], 404);
        }

        $notes = $request->input('notes');

        try {
            $this->documentService->approveDocument($document, $request->user(), $notes);

            return response()->json([
                'success' => true,
                'message' => 'Document approved successfully',
                'data' => $document->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a document (staff only)
     */
    public function reject(Request $request, UserCase $case, UploadedFile $document): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($document->user_case_id !== $case->id) {
            return response()->json([
                'success' => false,
                'message' => 'Document does not belong to this case',
            ], 404);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->documentService->rejectDocument($document, $request->user(), $request->input('reason'));

            return response()->json([
                'success' => true,
                'message' => 'Document rejected',
                'data' => $document->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get pending documents for review (staff only)
     */
    public function pending(Request $request): JsonResponse
    {
        if (!$request->user()->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $documents = $this->documentService->getPendingReviewDocuments(50);

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Get document types and requirements
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->documentService->getRequiredDocuments(),
        ]);
    }
}
