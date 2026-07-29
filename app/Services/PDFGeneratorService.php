<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\Inspection;
use App\Models\UploadedFile;
use App\Models\Setting;
use App\Models\CaseTimeline;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class PDFGeneratorService
{
    protected string $storagePath = 'cases/{case_id}/generated';

    /**
     * Generate HSMV 82040 - Application for Certificate of Title
     */
    public function generateHSMV82040(UserCase $case): string
    {
        $data = $this->prepareFormData($case);

        $pdf = Pdf::loadView('pdf.hsmv-82040', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'HSMV_82040');
    }

    /**
     * Generate HSMV 84490 - Rebuilt Motor Vehicle Inspection
     */
    public function generateHSMV84490(UserCase $case, Inspection $inspection): string
    {
        $data = $this->prepareFormData($case);
        $data['inspection'] = $inspection;
        $data['inspector'] = $inspection->inspector;

        $pdf = Pdf::loadView('pdf.hsmv-84490', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'HSMV_84490');
    }

    /**
     * Generate HSMV 84491 - Major Component Parts Form
     */
    public function generateHSMV84491(UserCase $case): string
    {
        $data = $this->prepareFormData($case);
        $data['components'] = $case->components;

        $pdf = Pdf::loadView('pdf.hsmv-84491', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'HSMV_84491');
    }

    /**
     * Generate HSMV 82050 - Statement of Builder / Bill of Sale
     */
    public function generateHSMV82050(UserCase $case): string
    {
        $data = $this->prepareFormData($case);

        $pdf = Pdf::loadView('pdf.hsmv-82050', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'HSMV_82050');
    }

    /**
     * Generate HSMV 82053 - Odometer Disclosure Statement
     */
    public function generateHSMV82053(UserCase $case): string
    {
        $data = $this->prepareFormData($case);

        $pdf = Pdf::loadView('pdf.hsmv-82053', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'HSMV_82053');
    }

    /**
     * Generate document checklist
     */
    public function generateDocumentChecklist(UserCase $case): string
    {
        $data = $this->prepareFormData($case);
        $data['documents'] = UploadedFile::where('user_case_id', $case->id)
            ->where('status', 'approved')
            ->get();

        $pdf = Pdf::loadView('pdf.document-checklist', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'Document_Checklist');
    }

    /**
     * Generate cover letter for Tax Collector
     */
    public function generateCoverLetter(UserCase $case): string
    {
        $data = $this->prepareFormData($case);
        $data['company'] = [
            'name' => Setting::companyName(),
            'address' => Setting::get('company_address'),
            'city' => Setting::get('company_city'),
            'state' => Setting::get('company_state', 'FL'),
            'zip' => Setting::get('company_zip'),
            'phone' => Setting::get('company_phone'),
            'email' => Setting::companyEmail(),
            'license' => Setting::get('prvip_license_number'),
        ];

        $pdf = Pdf::loadView('pdf.cover-letter', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'Cover_Letter');
    }

    /**
     * Generate complete document package
     */
    public function generateFullPackage(UserCase $case): string
    {
        $inspection = $case->inspection;

        if (!$inspection || $inspection->result !== 'passed') {
            throw new \Exception('Case must have a passed inspection to generate package');
        }

        // Generate all documents
        $documents = [];
        $documents[] = $this->generateCoverLetter($case);
        $documents[] = $this->generateDocumentChecklist($case);
        $documents[] = $this->generateHSMV82040($case);
        $documents[] = $this->generateHSMV84490($case, $inspection);
        $documents[] = $this->generateHSMV84491($case);
        $documents[] = $this->generateHSMV82050($case);
        $documents[] = $this->generateHSMV82053($case);

        // Create ZIP file
        $zipPath = $this->createZipPackage($case, $documents);

        // Update case status
        $case->updateStatus('ready_for_dmv');

        CaseTimeline::log(
            $case->id,
            'documents_generated',
            'Document package generated',
            'All HSMV forms have been generated and are ready for submission',
            ['documents' => count($documents)]
        );

        return $zipPath;
    }

    /**
     * Generate inspection report
     */
    public function generateInspectionReport(Inspection $inspection): string
    {
        $case = $inspection->userCase;
        $data = $this->prepareFormData($case);
        $data['inspection'] = $inspection;
        $data['inspector'] = $inspection->inspector;
        $data['photos'] = $inspection->photos;

        $pdf = Pdf::loadView('pdf.inspection-report', $data);
        $pdf->setPaper('letter', 'portrait');

        return $this->savePDF($case, $pdf, 'Inspection_Report');
    }

    /**
     * Prepare common form data
     */
    protected function prepareFormData(UserCase $case): array
    {
        $vehicle = $case->vehicle;
        $owner = $case->user;
        $inspection = $case->inspection;

        return [
            'case' => $case,
            'case_number' => $case->case_number,
            'vehicle' => $vehicle,
            'owner' => $owner,
            'inspection' => $inspection,
            'generated_at' => now(),
            'company_name' => Setting::companyName(),
            'company_license' => Setting::get('prvip_license_number'),
            // Vehicle info
            'vin' => $vehicle?->vin,
            'year' => $vehicle?->year,
            'make' => $vehicle?->make,
            'model' => $vehicle?->model,
            'body_type' => $vehicle?->body_type,
            'color' => $vehicle?->color,
            'title_number' => $vehicle?->title_number,
            'title_state' => $vehicle?->title_state,
            'odometer' => $vehicle?->odometer_reading,
            'odometer_status' => $vehicle?->odometer_status,
            // Owner info
            'owner_name' => $owner?->full_name,
            'owner_first_name' => $owner?->first_name,
            'owner_last_name' => $owner?->last_name,
            'owner_address' => $owner?->address_1,
            'owner_address_2' => $owner?->address_2,
            'owner_city' => $owner?->city,
            'owner_state' => $owner?->states,
            'owner_zip' => $owner?->zip,
            'owner_county' => $owner?->county,
            'owner_phone' => $owner?->phone,
            'owner_email' => $owner?->email,
        ];
    }

    /**
     * Save PDF to storage and create document record
     */
    protected function savePDF(UserCase $case, $pdf, string $formName): string
    {
        $filename = "{$formName}_{$case->case_number}.pdf";
        $path = str_replace('{case_id}', $case->id, $this->storagePath) . "/{$filename}";

        Storage::disk('private')->put($path, $pdf->output());

        // Create document record
        UploadedFile::updateOrCreate(
            [
                'user_case_id' => $case->id,
                'document_type' => strtolower($formName),
            ],
            [
                'uploaded_by' => auth()->id() ?? $case->assigned_inspector_id ?? 1,
                'original_name' => $filename,
                'file_path' => $path,
                'file_size' => Storage::disk('private')->size($path),
                'mime_type' => 'application/pdf',
                'status' => 'approved',
                'reviewed_at' => now(),
            ]
        );

        return $path;
    }

    /**
     * Create ZIP package of all documents
     */
    protected function createZipPackage(UserCase $case, array $documentPaths): string
    {
        $zipFilename = "RapidRebuilt_{$case->case_number}_Package.zip";
        $basePath = str_replace('{case_id}', $case->id, $this->storagePath);
        $zipPath = "{$basePath}/{$zipFilename}";

        $tempZipPath = storage_path('app/temp/' . Str::uuid() . '.zip');

        // Create temp directory if not exists
        if (!file_exists(dirname($tempZipPath))) {
            mkdir(dirname($tempZipPath), 0755, true);
        }

        $zip = new ZipArchive();

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Cannot create ZIP file');
        }

        // Add all generated documents
        foreach ($documentPaths as $path) {
            $content = Storage::disk('private')->get($path);
            $filename = basename($path);
            $zip->addFromString("Generated_Forms/{$filename}", $content);
        }

        // Add uploaded documents (approved only)
        $uploadedDocs = UploadedFile::where('user_case_id', $case->id)
            ->where('status', 'approved')
            ->whereNotIn('document_type', ['hsmv_82040', 'hsmv_84490', 'hsmv_84491', 'hsmv_82050', 'hsmv_82053'])
            ->get();

        foreach ($uploadedDocs as $doc) {
            if (Storage::disk('private')->exists($doc->file_path)) {
                $content = Storage::disk('private')->get($doc->file_path);
                $folder = ucwords(str_replace('_', ' ', $doc->document_type));
                $zip->addFromString("Supporting_Documents/{$folder}/{$doc->original_name}", $content);
            }
        }

        // Add inspection photos
        if ($case->inspection) {
            foreach ($case->inspection->photos as $photo) {
                if (Storage::disk('public')->exists($photo->file_path)) {
                    $content = Storage::disk('public')->get($photo->file_path);
                    $photoType = ucwords(str_replace('_', ' ', $photo->photo_type));
                    $zip->addFromString("Inspection_Photos/{$photoType}/{$photo->file_name}", $content);
                }
            }
        }

        $zip->close();

        // Move to private storage
        Storage::disk('private')->put($zipPath, file_get_contents($tempZipPath));

        // Clean up temp file
        unlink($tempZipPath);

        return $zipPath;
    }

    /**
     * Get download URL for generated document
     */
    public function getDownloadUrl(string $path): string
    {
        return Storage::disk('private')->temporaryUrl($path, now()->addHours(2));
    }

    /**
     * Check if all forms can be generated
     */
    public function canGenerateForms(UserCase $case): array
    {
        $issues = [];

        if (!$case->vehicle) {
            $issues[] = 'Vehicle information is missing';
        }

        if (!$case->user) {
            $issues[] = 'Owner information is missing';
        }

        if (!$case->inspection || $case->inspection->result !== 'passed') {
            $issues[] = 'Case requires a passed inspection';
        }

        if ($case->components->isEmpty()) {
            $issues[] = 'No components have been documented';
        }

        return [
            'can_generate' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Regenerate a specific form
     */
    public function regenerateForm(UserCase $case, string $formType): string
    {
        return match ($formType) {
            'hsmv_82040' => $this->generateHSMV82040($case),
            'hsmv_84490' => $this->generateHSMV84490($case, $case->inspection),
            'hsmv_84491' => $this->generateHSMV84491($case),
            'hsmv_82050' => $this->generateHSMV82050($case),
            'hsmv_82053' => $this->generateHSMV82053($case),
            'document_checklist' => $this->generateDocumentChecklist($case),
            'cover_letter' => $this->generateCoverLetter($case),
            'inspection_report' => $this->generateInspectionReport($case->inspection),
            default => throw new \Exception("Unknown form type: {$formType}"),
        };
    }

    /**
     * Get list of generated documents for a case
     */
    public function getGeneratedDocuments(UserCase $case): array
    {
        $basePath = str_replace('{case_id}', $case->id, $this->storagePath);

        $files = Storage::disk('private')->files($basePath);

        return collect($files)->map(function ($file) {
            return [
                'path' => $file,
                'name' => basename($file),
                'size' => Storage::disk('private')->size($file),
                'modified' => Storage::disk('private')->lastModified($file),
            ];
        })->toArray();
    }
}
