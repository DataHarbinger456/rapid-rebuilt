<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Models\UserCase;
use App\Services\DocumentExtractionService;
use App\Services\Extraction\ExtractionConfirmer;
use App\Services\Extraction\VinDecodeCheck;
use App\Support\Vin;
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
        private readonly VinDecodeCheck $vinDecodeCheck,
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
            'vinWarning' => $this->vinWarning($extraction),
            'vinDecode' => $this->vinDecodeCheck->for($extraction),
        ]);
    }

    /**
     * Why the VIN on screen looks wrong, or null if it looks fine.
     *
     * Checks the value the reviewer is actually looking at, not the raw AI
     * value: once staff have corrected the VIN, the warning must describe
     * their correction, otherwise a fixed VIN keeps showing a stale failure.
     *
     * Advisory only — confirmation is never blocked. Pre-1981 vehicles predate
     * ISO 3779 and a handful of legitimately-issued VINs fail the checksum, so
     * staff must be able to override after checking the document.
     */
    private function vinWarning(DocumentExtraction $extraction): ?string
    {
        $field = $extraction->fields->firstWhere('field_key', 'vin');
        $vin = $field?->confirmed_value ?? $field?->ai_value;

        if ($vin === null || trim($vin) === '' || Vin::isValid($vin)) {
            return null;
        }

        if (! Vin::isWellFormed($vin)) {
            return 'This is not a well-formed 17-character VIN. Check it against the document character by character.';
        }

        return 'This VIN fails its ISO 3779 check digit, which usually means one character was misread. '
            . 'Compare it to the document character by character before confirming. '
            . 'Pre-1981 vehicles have no valid check digit, so you may confirm it anyway if the document agrees.';
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
