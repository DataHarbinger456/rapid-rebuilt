<?php

namespace App\Services\Preflight;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Checks the things this app needs from its host that composer cannot install.
 *
 * Two external binaries, an API key, a schema state, a writable disk and one
 * third-party API. All of them work on a developer Mac and none of them are
 * guaranteed on a stock PHP container, which is exactly the gap that turns a
 * working build into a broken deployment.
 *
 * One service, two front ends: the artisan command for deploys and the admin
 * page for whoever is about to run a demo. Duplicating the logic would let the
 * two disagree, and the whole point is a single trustworthy answer.
 *
 * Never returns a secret. The API key check reports presence and provider only.
 */
class PreflightService
{
    /**
     * Deliberately short. This runs synchronously on an admin page load, and a
     * slow answer is a failing answer for the operator's purposes.
     */
    private const NETWORK_TIMEOUT_SECONDS = 5;

    private const PROCESS_TIMEOUT_SECONDS = 10;

    /**
     * A real VIN, so NHTSA exercises an actual decode rather than an error
     * path that might succeed while the service is degraded.
     */
    private const PROBE_VIN = '1HGCY2F82SA070581';

    private const NHTSA_URL = 'https://vpic.nhtsa.dot.gov/api/vehicles/decodevin';

    /**
     * Timeline events the extraction flow writes. MySQL truncates an unknown
     * enum value and raises a warning that surfaces as a 500, so a half-applied
     * migration set breaks extraction in a way the table check alone misses.
     */
    private const REQUIRED_TIMELINE_EVENTS = [
        'extraction_completed',
        'extraction_failed',
        'extraction_confirmed',
    ];

    /**
     * @return array<int, PreflightCheck>
     */
    public function run(): array
    {
        return [
            $this->checkPdftk(),
            $this->checkPdftoppm(),
            $this->checkExtractionKey(),
            $this->checkExtractionTables(),
            $this->checkStorageWritable(),
            $this->checkNhtsaReachable(),
        ];
    }

    /**
     * @param  array<int, PreflightCheck>  $checks
     */
    public function hasFailures(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check->failed()) {
                return true;
            }
        }

        return false;
    }

    private function checkPdftk(): PreflightCheck
    {
        $result = $this->runBinary('pdftk', ['pdftk', '--version']);

        if ($result === null) {
            return PreflightCheck::fail(
                'pdftk',
                'Not found or not executable',
                'No DMV form can be generated. The HSMV 82040 button will fail with an error.',
                'Install pdftk (or pdftk-java) on the host and make sure it is on the PATH.'
            );
        }

        return PreflightCheck::pass('pdftk', $result);
    }

    private function checkPdftoppm(): PreflightCheck
    {
        // Version goes to stderr on some poppler builds, so both streams are
        // read before deciding.
        $result = $this->runBinary('pdftoppm', ['pdftoppm', '-v']);

        if ($result === null) {
            return PreflightCheck::warn(
                'pdftoppm',
                'Not found or not executable',
                'Documents uploaded as PDF cannot be read by the AI. Photo and image uploads are unaffected.',
                'Install poppler-utils on the host.'
            );
        }

        return PreflightCheck::pass('pdftoppm', $result);
    }

    /**
     * Presence only. The value is never read into the return, never logged and
     * never rendered.
     */
    private function checkExtractionKey(): PreflightCheck
    {
        $provider = (string) config('extraction.default');
        $configured = config("prism.providers.{$provider}.api_key");

        // A provider with no api_key entry at all is either local (ollama) or
        // a typo in EXTRACTION_PROVIDER. Both need a human to look.
        if (! config()->has("prism.providers.{$provider}")) {
            return PreflightCheck::fail(
                'Extraction API key',
                "Unknown provider '{$provider}'",
                'Document extraction will fail for every upload.',
                'Set EXTRACTION_PROVIDER to a provider configured in config/prism.php.'
            );
        }

        if (is_string($configured) && trim($configured) !== '') {
            return PreflightCheck::pass(
                'Extraction API key',
                "Configured for '{$provider}' (value not shown)"
            );
        }

        return PreflightCheck::fail(
            'Extraction API key',
            "Not set for provider '{$provider}'",
            'The AI cannot read any document. "Read with AI" will fail.',
            "Set the {$this->envKeyNameFor($provider)} environment variable on the host."
        );
    }

    private function checkExtractionTables(): PreflightCheck
    {
        $missing = [];

        foreach (['document_extractions', 'document_extraction_fields'] as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if ($missing !== []) {
            return PreflightCheck::fail(
                'Extraction tables',
                'Missing: ' . implode(', ', $missing),
                'Reading a document will error immediately.',
                'Run php artisan migrate on the host.'
            );
        }

        $missingEvents = $this->missingTimelineEvents();

        if ($missingEvents !== []) {
            return PreflightCheck::fail(
                'Extraction tables',
                'Tables present, but case_timeline is missing: ' . implode(', ', $missingEvents),
                'Extraction runs, then errors when it writes its timeline entry.',
                'Run php artisan migrate on the host to apply the timeline enum migration.'
            );
        }

        return PreflightCheck::pass('Extraction tables', 'Present, with timeline events registered');
    }

    /**
     * @return array<int, string>
     */
    private function missingTimelineEvents(): array
    {
        // The enum is MySQL-specific, as is the migration that extends it.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return [];
        }

        try {
            $column = DB::selectOne(
                'SELECT COLUMN_TYPE AS type FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['case_timeline', 'event_type']
            );
        } catch (Throwable) {
            return [];
        }

        if ($column === null) {
            return [];
        }

        return array_values(array_filter(
            self::REQUIRED_TIMELINE_EVENTS,
            fn (string $event): bool => ! str_contains((string) $column->type, "'{$event}'")
        ));
    }

    /**
     * A real write, not is_writable(). The private disk is S3 in some
     * deployments, where the local filesystem says nothing useful.
     */
    private function checkStorageWritable(): PreflightCheck
    {
        $disk = 'private';
        $path = 'preflight/' . Str::uuid() . '.txt';
        $canary = 'preflight';

        try {
            Storage::disk($disk)->put($path, $canary);
            $readBack = Storage::disk($disk)->get($path);
            Storage::disk($disk)->delete($path);
        } catch (Throwable $e) {
            return PreflightCheck::fail(
                'Storage writable',
                "Write to the '{$disk}' disk failed: " . $e->getMessage(),
                'Uploads and generated forms cannot be saved.',
                'Check the filesystem permissions or the S3 credentials for the private disk.'
            );
        }

        if ($readBack !== $canary) {
            return PreflightCheck::fail(
                'Storage writable',
                "Wrote to the '{$disk}' disk but read back different content",
                'Uploads may be silently corrupted.',
                'Check the private disk configuration in config/filesystems.php.'
            );
        }

        return PreflightCheck::pass('Storage writable', "Wrote, read and deleted on the '{$disk}' disk");
    }

    private function checkNhtsaReachable(): PreflightCheck
    {
        try {
            $response = Http::timeout(self::NETWORK_TIMEOUT_SECONDS)
                ->get(self::NHTSA_URL . '/' . self::PROBE_VIN, ['format' => 'json']);
        } catch (Throwable $e) {
            return PreflightCheck::warn(
                'NHTSA VIN decoder',
                'Unreachable: ' . $e->getMessage(),
                'The VIN cross-check on the review screen shows its "could not confirm" notice. Extraction still works.',
                'Check outbound HTTPS from the host. No action needed if this is a temporary outage.'
            );
        }

        if (! $response->successful()) {
            return PreflightCheck::warn(
                'NHTSA VIN decoder',
                'Responded HTTP ' . $response->status(),
                'The VIN cross-check shows its "could not confirm" notice. Extraction still works.',
                'Usually a temporary NHTSA outage; no action needed unless it persists.'
            );
        }

        return PreflightCheck::pass('NHTSA VIN decoder', 'Reachable, decoded the probe VIN');
    }

    /**
     * The version string on success, or null when the binary is missing or
     * cannot run.
     *
     * @param  array<int, string>  $command
     */
    private function runBinary(string $name, array $command): ?string
    {
        try {
            $result = Process::timeout(self::PROCESS_TIMEOUT_SECONDS)->run($command);
        } catch (Throwable) {
            // Thrown when the binary does not exist at all.
            return null;
        }

        if (! $result->successful()) {
            return null;
        }

        $output = trim($result->output()) !== '' ? $result->output() : $result->errorOutput();
        $firstLine = trim(strtok(trim($output), "\n") ?: '');

        return $firstLine !== '' ? $firstLine : "{$name} present";
    }

    /**
     * Best-effort env var name for the operator's benefit. Prism reads these
     * itself; this only builds the hint text.
     */
    private function envKeyNameFor(string $provider): string
    {
        return strtoupper(str_replace('-', '_', $provider)) . '_API_KEY';
    }
}
