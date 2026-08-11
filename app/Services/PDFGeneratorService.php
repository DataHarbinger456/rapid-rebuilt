<?php

namespace App\Services;

use App\Models\UserCase;
use App\Models\Inspection;
use App\Models\UploadedFile;
use App\Models\Setting;
use App\Models\CaseTimeline;
use App\Support\PdftkBinary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use mikehaertl\pdftk\Pdf;
use RuntimeException;
use ZipArchive;

/**
 * Generates the Florida HSMV packet for a case.
 *
 * Forms are produced by stamping data into the official fillable HSMV PDFs in
 * docs/forms/ with pdftk, never by re-typesetting lookalike HTML — the tax
 * collector rejects reproductions.
 *
 * Field maps live in config/hsmv_forms.php. A form without an entry there
 * throws rather than silently producing an empty document; only HSMV 82040 is
 * mapped so far.
 */
class PDFGeneratorService
{
    /**
     * Model year from which a vehicle's odometer is assumed to have six
     * digits. See the Section 6 notes in prepareFormData().
     */
    private const SIX_DIGIT_ODOMETER_FROM_YEAR = 1995;

    protected string $storagePath = 'cases/{case_id}/generated';

    /**
     * Generate HSMV 82040 - Application for Certificate of Title
     */
    public function generateHSMV82040(UserCase $case): string
    {
        $data = $this->prepareFormData($case);

        return $this->renderPdf($case, 'HSMV_82040', $data);
    }

    /**
     * Generate HSMV 84490 - Statement of Builder
     */
    public function generateHSMV84490(UserCase $case, Inspection $inspection): string
    {
        $data = $this->prepareFormData($case);
        $data['inspection'] = $inspection;
        $data['inspector'] = $inspection->inspector;

        return $this->renderPdf($case, 'HSMV_84490', $data);
    }

    /**
     * Generate HSMV 84491 - Statement of Builder, Additional Sheet
     * (component continuation pages for 84490)
     */
    public function generateHSMV84491(UserCase $case): string
    {
        $data = $this->prepareFormData($case);
        $data['components'] = $case->components;

        return $this->renderPdf($case, 'HSMV_84491', $data);
    }

    /**
     * Generate HSMV 82050 - Notice of Sale and/or Bill of Sale
     */
    public function generateHSMV82050(UserCase $case): string
    {
        $data = $this->prepareFormData($case);

        return $this->renderPdf($case, 'HSMV_82050', $data);
    }

    /**
     * Generate HSMV 82053 - Power of Attorney for a Motor Vehicle
     */
    public function generateHSMV82053(UserCase $case): string
    {
        $data = $this->prepareFormData($case);

        return $this->renderPdf($case, 'HSMV_82053', $data);
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

        return $this->renderPdf($case, 'Document_Checklist', $data);
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

        return $this->renderPdf($case, 'Cover_Letter', $data);
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

        return $this->renderPdf($case, 'Inspection_Report', $data);
    }

    /**
     * Fill a form with prepared data, then store the result.
     */
    protected function renderPdf(UserCase $case, string $formName, array $data): string
    {
        return $this->savePDF($case, $this->fillForm($formName, $data), $formName);
    }

    /**
     * Produce the raw bytes of a filled, flattened form.
     *
     * @throws RuntimeException if the form has no field map yet, its template
     *                          is missing, or pdftk fails.
     */
    protected function fillForm(string $formName, array $data): string
    {
        $label = str_replace('_', ' ', $formName);
        $config = config('hsmv_forms.' . strtolower($formName));

        if (!$config) {
            throw new RuntimeException(
                "{$label} cannot be generated yet. Automatic form filling is still "
                . 'in development — please complete this form manually for now.'
            );
        }

        $template = base_path($config['template']);

        if (!is_file($template)) {
            throw new RuntimeException("Missing form template for {$label}: {$config['template']}");
        }

        // Production has no system pdftk, so the binary is resolved rather
        // than assumed. See config/pdftk.php.
        $pdf = new Pdf($template, ['command' => PdftkBinary::path()]);

        // flatten() bakes the values into the page so the printed copy shows
        // them and nothing can be edited afterwards.
        $output = $pdf
            ->fillForm($this->mapFields($config['fields'], $data))
            ->flatten()
            ->toString();

        if ($output === false) {
            throw new RuntimeException("Could not fill {$label}: " . $pdf->getError());
        }

        return $output;
    }

    /**
     * Turn a field map from config/hsmv_forms.php into pdftk's
     * [pdf field name => value] array.
     *
     * A map entry may be:
     *   - a data key: 'vin'
     *   - several data keys: ['owner_first_name', 'owner_last_name'], joined
     *     with a space, for HSMV boxes that hold what we store as separate
     *     columns
     *   - a checkbox: ['checkbox' => 'is_rebuilt', 'on' => 'On'], where 'on'
     *     is the button's exact export value from the PDF
     *
     * Blank text values are omitted so the box stays empty rather than
     * printing "null". Checkboxes are always written, as an unticked box has
     * to be explicitly set to Off.
     */
    protected function mapFields(array $fieldMap, array $data): array
    {
        $values = [];

        foreach ($fieldMap as $pdfField => $mapping) {
            if (is_array($mapping) && isset($mapping['checkbox'])) {
                $values[$pdfField] = $this->checkboxValue($mapping, $data);

                continue;
            }

            $parts = [];

            foreach ((array) $mapping as $key) {
                $value = $data[$key] ?? null;

                if ($value !== null && trim((string) $value) !== '') {
                    $parts[] = trim((string) $value);
                }
            }

            if ($parts !== []) {
                $values[$pdfField] = implode(' ', $parts);
            }
        }

        return $values;
    }

    /**
     * Resolve a checkbox mapping to the button's on or off export value.
     *
     * Export values are per-field and case sensitive; read them from the
     * FieldStateOption lines of pdftk dump_data_fields_utf8. 'Off' is the
     * PDF standard for an unticked button and is the same on every HSMV form
     * we have seen, so only the on value is configurable.
     */
    protected function checkboxValue(array $mapping, array $data): string
    {
        $checked = (bool) ($data[$mapping['checkbox']] ?? false);

        return $checked ? ($mapping['on'] ?? 'On') : 'Off';
    }

    /**
     * Split an odometer reading into the 82040's six per-digit boxes.
     *
     * Returned most significant first, right-aligned with leading nulls, so
     * 4527 fills only the last four boxes. mapFields() skips nulls, leaving
     * those boxes empty on the page.
     *
     * A reading that will not fit in six digits returns all nulls rather than
     * a truncation: a wrong mileage on a declaration sworn under penalty of
     * perjury is worse than a blank staff must fill in.
     *
     * @return array<int, ?string>
     */
    protected function odometerDigits(?int $reading): array
    {
        $blank = array_fill(0, 6, null);

        if ($reading === null || $reading < 0 || $reading > 999999) {
            return $blank;
        }

        $digits = str_split(str_pad((string) $reading, 6, '0', STR_PAD_LEFT));
        $seenSignificant = false;

        foreach ($digits as $i => $digit) {
            // Keep every digit from the first non-zero onwards, so a reading of
            // 4027 keeps its internal zero but drops the leading padding.
            $seenSignificant = $seenSignificant || $digit !== '0';

            // A genuine reading of 0 still needs one printed digit.
            if ($seenSignificant || $i === 5) {
                $blank[$i] = $digit;
            }
        }

        return $blank;
    }

    /**
     * Prepare common form data
     */
    protected function prepareFormData(UserCase $case): array
    {
        $vehicle = $case->vehicle;
        $owner = $case->user;
        $inspection = $case->inspection;

        // Application Type, top of the 82040. Client confirmed 5 Aug: a
        // Florida title is an Original application, an out-of-state title is a
        // Transfer. The title number is irrelevant to this choice.
        //
        // title_state is a two-letter code, so anything that is not FL is out
        // of state. A case with no vehicle ticks neither box rather than
        // guessing on a legal document.
        $titleState = $vehicle?->title_state;
        $isOriginal = $titleState === 'FL';
        $isTransfer = filled($titleState) && $titleState !== 'FL';

        // Section 3 of the 82040 is "BRANDS, USAGE AND TYPE". The TYPE boxes
        // describe how the vehicle was built and come from
        // vehicles.vehicle_type; the BRAND boxes describe history carried by
        // the title and come from vehicles.title_brand. The two are not
        // interchangeable: ASPT is the state's abbreviation for Assembled From
        // Parts, a build type, while flood is a brand on the title.
        //
        // vehicle_type is nullable and older cases predate it. Every case this
        // business files is a rebuilt-title application, so a missing type on a
        // rebuilt_title case falls back to rebuilt rather than leaving
        // Section 3 entirely blank.
        $vehicleType = $vehicle?->vehicle_type
            ?? ($case->service_type === 'rebuilt_title' ? 'rebuilt' : null);

        // Section 6 ODOMETER DECLARATION. The form has no single mileage box:
        // it is six per-digit boxes, a three-part date, and a 1/2/3
        // declaration. All of it comes off the title through extraction, and
        // only lands here once staff have confirmed it.
        $odometerDigits = $this->odometerDigits($vehicle?->odometer_reading);
        $odometerDate = $vehicle?->odometer_date;
        $odometerStatus = $vehicle?->odometer_status;

        // "5 or 6-digit odometer" asks how many digits the instrument cluster
        // physically displays, which the title never states and the reading
        // cannot imply: 62,712 miles looks the same on either. The model year
        // does answer it, though. Five-digit odometers disappeared from US
        // production well before the mid 1990s, so anything from 1995 on is
        // six-digit beyond realistic doubt.
        //
        // Below that cutoff, and when the year is unknown, both boxes stay
        // blank for a human who has seen the car. This is a declaration sworn
        // under penalty of perjury; a blank is a question, a wrong tick is a
        // false statement.
        $modelYear = $vehicle?->year;
        $isSixDigitOdometer = $modelYear !== null && (int) $modelYear >= self::SIX_DIGIT_ODOMETER_FROM_YEAR;

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
            // Section 2's box is labelled "Florida Title Number", so it is only
            // answerable when the surrendered title is a Florida one. An
            // out-of-state title has no Florida number until this application
            // is granted, and printing its home-state number in a box that asks
            // for a Florida one would be a false statement on a sworn
            // application. Same rule the client confirmed for Original vs
            // Transfer on 5 Aug, and it fails safe: a blank box is one staff
            // can complete by hand.
            'florida_title_number' => $titleState === 'FL' ? $vehicle?->title_number : null,
            // Weight is whatever the title says, not the VIN decoder's GVWR.
            // Client confirmed 5 Aug: every Florida title carries its own
            // weight, out-of-state titles may not, and a blank box is
            // acceptable for staff to complete by hand. Blank values are
            // skipped by mapFields(), so a null here leaves the box empty.
            'weight' => $vehicle?->weight,
            'odometer' => $vehicle?->odometer_reading,
            'odometer_status' => $odometerStatus,
            // Section 2 "Previous State of Issue": the jurisdiction that issued
            // the title being surrendered. previous_state is only populated
            // when the title itself names an earlier state distinct from its
            // issuer, in which case it is the more precise answer; otherwise
            // the issuing state is what the box is asking for.
            'previous_state_of_issue' => $vehicle?->previous_state ?: $vehicle?->title_state,
            // Section 6 mileage, one box per decimal place. Leading boxes stay
            // blank rather than zero-padded, matching how the form is filled by
            // hand.
            'odometer_digit_100000' => $odometerDigits[0],
            'odometer_digit_10000' => $odometerDigits[1],
            'odometer_digit_1000' => $odometerDigits[2],
            'odometer_digit_100' => $odometerDigits[3],
            'odometer_digit_10' => $odometerDigits[4],
            'odometer_digit_1' => $odometerDigits[5],
            // Date the odometer was read, as three separate boxes.
            'odometer_date_mm' => $odometerDate?->format('m'),
            'odometer_date_dd' => $odometerDate?->format('d'),
            'odometer_date_yyyy' => $odometerDate?->format('Y'),
            // The three declaration boxes are mutually exclusive. An unknown or
            // missing status ticks none of them: this declaration is sworn
            // under penalty of perjury, so a blank for staff to complete beats
            // a guess.
            // Never both, and never the 5-digit box: a pre-1995 vehicle leaves
            // the pair blank rather than asserting the smaller cluster.
            'is_six_digit_odometer' => $isSixDigitOdometer,
            'is_odometer_actual' => $odometerStatus === 'actual',
            'is_odometer_not_actual' => $odometerStatus === 'not_actual',
            'is_odometer_exceeds_limits' => $odometerStatus === 'exceeds_limits',
            'vehicle_type' => $vehicleType,
            // Section 3 TYPE boxes. At most one is ever true; mobile_home and
            // other have no box of their own on this form.
            'is_rebuilt' => $vehicleType === 'rebuilt',
            'is_assembled_from_parts' => $vehicleType === 'aspt',
            'is_kit_car' => $vehicleType === 'kit_car',
            'is_custom' => $vehicleType === 'custom_vehicle',
            'is_street_rod' => $vehicleType === 'street_rod',
            'is_replica' => $vehicleType === 'replica',
            // Section 3 BRAND box.
            'is_flood' => $vehicle?->title_brand === 'flood',
            'is_application_original' => $isOriginal,
            'is_application_transfer' => $isTransfer,
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
    protected function savePDF(UserCase $case, string $contents, string $formName): string
    {
        $filename = "{$formName}_{$case->case_number}.pdf";
        $path = str_replace('{case_id}', $case->id, $this->storagePath) . "/{$filename}";

        Storage::disk('private')->put($path, $contents);

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
