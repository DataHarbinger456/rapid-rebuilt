<?php

namespace App\Services;

use App\Jobs\ExtractDocument;
use App\Models\CaseTimeline;
use App\Models\DocumentExtraction;
use App\Models\DocumentExtractionField;
use App\Models\UploadedFile;
use App\Services\Extraction\Contracts\ExtractsDocuments;
use App\Services\Extraction\ExtractionResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Orchestrates reading an uploaded document with a vision model.
 *
 * Knows nothing about Prism: which extractor handles a document type comes
 * from config/extraction.php, so adding the bill of sale is one config line.
 * Nothing here writes to the vehicle — that is ExtractionConfirmer's job, and
 * only after a human has confirmed each field.
 */
class DocumentExtractionService
{
    /**
     * Queue an extraction for an uploaded document.
     *
     * Any prior extraction of the same file is marked superseded rather than
     * deleted: the record of what a model read must survive a re-run.
     */
    public function extract(UploadedFile $file): DocumentExtraction
    {
        $extractor = $this->extractorFor($file->document_type);

        if ($file->user_case_id === null) {
            throw new InvalidArgumentException("Uploaded file {$file->id} is not attached to a case.");
        }

        $extraction = DB::transaction(function () use ($file, $extractor): DocumentExtraction {
            $alreadyRunning = DocumentExtraction::where('uploaded_file_id', $file->id)
                ->active()
                ->lockForUpdate()
                ->exists();

            if ($alreadyRunning) {
                throw new RuntimeException("An extraction is already in progress for file {$file->id}.");
            }

            DocumentExtraction::where('uploaded_file_id', $file->id)
                ->where('status', '!=', DocumentExtraction::STATUS_SUPERSEDED)
                ->update(['status' => DocumentExtraction::STATUS_SUPERSEDED]);

            return DocumentExtraction::create([
                'uploaded_file_id' => $file->id,
                'user_case_id' => $file->user_case_id,
                'document_type' => $extractor->documentType(),
                'status' => DocumentExtraction::STATUS_PENDING,
                'provider' => $this->providerFor($file->document_type),
                'model' => $this->modelFor($file->document_type),
            ]);
        });

        ExtractDocument::dispatch($extraction->id);

        return $extraction;
    }

    /**
     * Run the extractor. Called from the job, never inline from a request.
     */
    public function perform(DocumentExtraction $extraction): void
    {
        $file = $extraction->uploadedFile;

        if ($file === null) {
            throw new RuntimeException("Extraction {$extraction->id} has no uploaded file.");
        }

        $extraction->update([
            'status' => DocumentExtraction::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $result = $this->extractorFor($extraction->document_type)->extract($file);

        $this->storeResult($extraction, $result);
    }

    /**
     * Persist what the model read: one field row per schema field, including
     * the ones it could not find, so the review screen shows every field a
     * human still has to decide on.
     */
    public function storeResult(DocumentExtraction $extraction, ExtractionResult $result): void
    {
        DB::transaction(function () use ($extraction, $result): void {
            $extraction->update([
                'status' => DocumentExtraction::STATUS_COMPLETED,
                'provider' => $result->provider !== '' ? $result->provider : $extraction->provider,
                'model' => $result->model !== '' ? $result->model : $extraction->model,
                'raw_payload' => $result->raw !== [] ? $result->raw : $result->fields,
                'prompt_tokens' => $result->promptTokens,
                'completion_tokens' => $result->completionTokens,
                'error_message' => null,
                'completed_at' => now(),
            ]);

            foreach ($result->fields as $fieldKey => $value) {
                DocumentExtractionField::updateOrCreate(
                    [
                        'document_extraction_id' => $extraction->id,
                        'field_key' => $fieldKey,
                    ],
                    [
                        'ai_value' => $value,
                        'confidence' => $result->confidenceFor($fieldKey),
                        'status' => DocumentExtractionField::STATUS_PENDING,
                    ]
                );
            }
        });

        $found = collect($result->fields)->filter(fn (?string $v): bool => $v !== null)->count();

        CaseTimeline::log(
            $extraction->user_case_id,
            'extraction_completed',
            'Document read by AI',
            "{$found} of " . count($result->fields) . ' fields read from the ' .
                str_replace('_', ' ', $extraction->document_type) . '. Awaiting staff confirmation.',
            ['extraction_id' => $extraction->id, 'model' => $extraction->model],
            null,
            true
        );
    }

    /**
     * Record a failed attempt. No field rows are written, so a failure can
     * never be mistaken for a document with nothing on it.
     */
    public function fail(DocumentExtraction $extraction, Throwable $e): void
    {
        $extraction->update([
            'status' => DocumentExtraction::STATUS_FAILED,
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ]);

        Log::error('Document extraction failed', [
            'extraction_id' => $extraction->id,
            'uploaded_file_id' => $extraction->uploaded_file_id,
            'exception' => $e,
        ]);

        CaseTimeline::log(
            $extraction->user_case_id,
            'extraction_failed',
            'Document could not be read',
            $e->getMessage(),
            ['extraction_id' => $extraction->id],
            null,
            true
        );
    }

    public function extractorFor(?string $documentType): ExtractsDocuments
    {
        $class = config("extraction.documents.{$documentType}.extractor");

        if ($class === null) {
            throw new InvalidArgumentException(
                "No extractor is configured for document type '{$documentType}'."
            );
        }

        return app($class);
    }

    public function supports(?string $documentType): bool
    {
        return $documentType !== null
            && config("extraction.documents.{$documentType}.extractor") !== null;
    }

    private function providerFor(string $documentType): string
    {
        return config("extraction.documents.{$documentType}.provider") ?? config('extraction.default');
    }

    private function modelFor(string $documentType): string
    {
        return config("extraction.documents.{$documentType}.model") ?? config('extraction.model');
    }
}
