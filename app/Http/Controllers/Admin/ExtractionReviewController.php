<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Models\UserCase;
use App\Services\DocumentExtractionService;
use App\Services\Extraction\ExtractionConfirmer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Staff review of what a vision model read from a document.
 *
 * Nothing here writes to the vehicle directly: confirm() hands off to
 * ExtractionConfirmer, which refuses to run until every field carries a human
 * decision.
 */
class ExtractionReviewController extends Controller
{
    public function __construct(
        private readonly DocumentExtractionService $extractionService,
        private readonly ExtractionConfirmer $confirmer,
    ) {}

    /**
     * Start an extraction for a document that has not been read yet.
     */
    public function store(Request $request, UserCase $case, UploadedFile $document): RedirectResponse
    {
        abort_unless($document->user_case_id === $case->id, 404);

        if (! $this->extractionService->supports($document->document_type)) {
            return back()->with('error', 'AI extraction is not available for this document type yet.');
        }

        try {
            $extraction = $this->extractionService->extract($document);
        } catch (Throwable $e) {
            return back()->with('error', 'Could not read the document: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.extractions.show', [$case->id, $extraction->id])
            ->with('success', 'Document read. Confirm each field before it is used.');
    }

    public function show(UserCase $case, DocumentExtraction $extraction): View
    {
        $this->assertBelongsToCase($case, $extraction);

        $extraction->load(['fields', 'uploadedFile', 'reviewer']);

        $extractor = $this->extractionService->extractorFor($extraction->document_type);
        $vehicle = $case->vehicle;

        // Fields where the AI disagrees with what the customer already entered
        // at intake. title_state matters most: it decides whether the 82040 is
        // filed as an Original or a Transfer.
        $conflicts = [];

        foreach ($extraction->fields as $field) {
            $column = ExtractionConfirmer::vehicleColumnFor($field->field_key);

            if ($column === null || $vehicle === null || $field->ai_value === null) {
                continue;
            }

            $existing = $vehicle->getAttribute($column);

            if ($existing === null || $existing === '') {
                continue;
            }

            $existing = $existing instanceof \DateTimeInterface
                ? $existing->format('Y-m-d')
                : (string) $existing;

            if (strcasecmp(trim($existing), trim($field->ai_value)) !== 0) {
                $conflicts[$field->field_key] = $existing;
            }
        }

        return view('admin.cases.extraction-review', [
            'case' => $case,
            'extraction' => $extraction,
            'document' => $extraction->uploadedFile,
            'labels' => $extractor->fieldLabels(),
            'conflicts' => $conflicts,
            'vehicle' => $vehicle,
        ]);
    }

    /**
     * Record the staff decision on each field and, once none are pending,
     * write the accepted values to the vehicle.
     */
    public function confirm(Request $request, UserCase $case, DocumentExtraction $extraction): RedirectResponse
    {
        $this->assertBelongsToCase($case, $extraction);

        // Checked before any field row is touched: a stale tab must not even
        // record decisions against an attempt a re-run has replaced.
        if ($extraction->status !== DocumentExtraction::STATUS_COMPLETED) {
            return back()->with(
                'error',
                'This extraction is ' . $extraction->status_label
                    . ' and can no longer be confirmed. Open the latest read of this document.'
            );
        }

        $validated = $request->validate([
            'fields' => ['required', 'array'],
            'fields.*.status' => [
                'required',
                Rule::in([
                    DocumentExtractionField::STATUS_CONFIRMED,
                    DocumentExtractionField::STATUS_CORRECTED,
                    DocumentExtractionField::STATUS_REJECTED,
                ]),
            ],
            'fields.*.value' => ['nullable', 'string', 'max:255'],
        ]);

        $staff = $request->user();
        $fields = $extraction->fields()->get()->keyBy('field_key');

        foreach ($validated['fields'] as $fieldKey => $input) {
            $field = $fields->get($fieldKey);

            if ($field === null) {
                continue;
            }

            $status = $input['status'];
            $value = $input['value'] ?? null;

            // A "confirmed" field that was in fact edited is a correction, no
            // matter which radio the reviewer left selected. The distinction is
            // what makes the correction rate meaningful.
            if ($status === DocumentExtractionField::STATUS_CONFIRMED
                && $value !== null
                && trim($value) !== trim((string) $field->ai_value)) {
                $status = DocumentExtractionField::STATUS_CORRECTED;
            }

            $field->update([
                'status' => $status,
                'confirmed_value' => $status === DocumentExtractionField::STATUS_REJECTED ? null : $value,
                'confirmed_by' => $staff->id,
                'confirmed_at' => now(),
            ]);
        }

        try {
            $this->confirmer->apply($extraction->fresh('fields'), $staff);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()
            ->route('admin.cases.show', $case->id)
            ->with('success', 'Confirmed values saved to the vehicle record.');
    }

    /**
     * Re-run a failed or unsatisfactory extraction. The previous attempt is
     * superseded, never deleted.
     */
    public function retry(UserCase $case, DocumentExtraction $extraction): RedirectResponse
    {
        $this->assertBelongsToCase($case, $extraction);

        $document = $extraction->uploadedFile;

        if ($document === null) {
            return back()->with('error', 'The source document is no longer available.');
        }

        try {
            $new = $this->extractionService->extract($document);
        } catch (Throwable $e) {
            return back()->with('error', 'Could not re-read the document: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.extractions.show', [$case->id, $new->id])
            ->with('success', 'Document read again.');
    }

    private function assertBelongsToCase(UserCase $case, DocumentExtraction $extraction): void
    {
        abort_unless($extraction->user_case_id === $case->id, 404);
    }
}
