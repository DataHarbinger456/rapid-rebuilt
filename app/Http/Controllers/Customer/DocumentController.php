<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\UserCase;
use App\Models\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * List all customer documents
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get all customer cases for filter dropdown
        $cases = UserCase::where('user_id', $user->id)
            ->select('id', 'case_number')
            ->get();

        // Build documents query
        $query = UploadedFile::with('userCase')
            ->whereHas('userCase', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        if ($request->filled('user_case_id')) {
            $query->where('user_case_id', $request->user_case_id);
        }

        if ($request->filled('type')) {
            $query->where('document_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $documents = $query->latest()->paginate(12);

        // Get pending documents that need attention
        $pendingDocuments = UploadedFile::with('userCase')
            ->whereHas('userCase', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'rejected')
            ->get();

        return view('customer.documents.index', compact('documents', 'cases', 'pendingDocuments'));
    }

    /**
     * Show re-upload form for rejected document
     */
    public function reupload(UploadedFile $document)
    {
        $this->authorize('update', $document);

        if ($document->status !== 'rejected') {
            return redirect()
                ->route('customer.documents.index')
                ->with('error', 'This document does not need to be re-uploaded.');
        }

        return view('customer.documents.reupload', compact('document'));
    }

    /**
     * Process re-uploaded document
     */
    public function processReupload(Request $request, UploadedFile $document)
    {
        $this->authorize('update', $document);

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        // Delete old file
        if ($document->file_path) {
            Storage::disk('private')->delete($document->file_path);
        }

        // Store new file
        $file = $request->file('file');
        $path = $file->store("cases/{$document->user_case_id}/documents", 'private');

        // Update document record
        $document->update([
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        // Add timeline entry
        $document->userCase->timeline()->create([
            'title' => 'Document Re-uploaded',
            'description' => "{$document->document_type_label} was re-uploaded",
            'type' => 'document',
            'metadata' => ['document_id' => $document->id],
        ]);

        return redirect()
            ->route('customer.cases.show', $document->userCase)
            ->with('success', 'Document re-uploaded successfully and is pending review.');
    }

    /**
     * Upload additional documents
     */
    public function upload(Request $request, UserCase $case)
    {
        $this->authorize('update', $case);

        $request->validate([
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->store("cases/{$case->id}/documents", 'private');

        $document = UploadedFile::create([
            'user_case_id' => $case->id,
            'user_id' => Auth::id(),
            'document_type' => $request->document_type,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'status' => 'pending',
        ]);

        // Add timeline entry
        $case->timeline()->create([
            'title' => 'Document Uploaded',
            'description' => "{$document->document_type_label} was uploaded",
            'type' => 'document',
            'metadata' => ['document_id' => $document->id],
        ]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document' => $document,
            ]);
        }

        return back()->with('success', 'Document uploaded successfully.');
    }
}
