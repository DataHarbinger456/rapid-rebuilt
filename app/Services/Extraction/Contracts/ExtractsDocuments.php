<?php

namespace App\Services\Extraction\Contracts;

use App\Models\UploadedFile;
use App\Services\Extraction\ExtractionResult;
use Prism\Prism\Schema\ObjectSchema;

/**
 * One implementation per document type. Bill of sale and driver's licence will
 * implement this without DocumentExtractionService changing.
 */
interface ExtractsDocuments
{
    /**
     * The uploaded_files.document_type this extractor handles.
     */
    public function documentType(): string;

    /**
     * The structure the provider is asked to return. Also the source of truth
     * for which field rows an extraction creates, so orchestration never has
     * to hardcode a field list.
     */
    public function schema(): ObjectSchema;

    /**
     * Human labels for each field key, for the staff confirmation screen.
     *
     * @return array<string, string>
     */
    public function fieldLabels(): array;

    /**
     * Read the document. Throws on provider or file errors; the caller records
     * the failure.
     */
    public function extract(UploadedFile $file): ExtractionResult;
}
