<?php

namespace App\Console\Commands;

use App\Models\CaseTimeline;
use App\Models\Component;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\UploadedFile;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Vehicle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Clears every case so a demo can be re-run from a clean slate.
 *
 * Staff, inspector, and customer accounts plus pricing settings are kept,
 * so the only thing to redo is the customer-facing case walkthrough.
 */
class DemoReset extends Command
{
    protected $signature = 'demo:reset {--force : Skip the confirmation prompt}';

    protected $description = 'Delete all cases, documents, appointments and payments; keep users and settings';

    public function handle(): int
    {
        $caseCount = UserCase::withTrashed()->count();

        if ($caseCount === 0) {
            $this->info('Already clean: no cases exist.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete all {$caseCount} case(s) and their documents? Accounts and settings are kept.")) {
            $this->warn('Aborted.');

            return self::FAILURE;
        }

        $caseIds = UserCase::withTrashed()->pluck('id');

        // Remove stored uploads first so the bucket does not keep orphaned files.
        $deletedFiles = 0;
        foreach (UploadedFile::whereIn('user_case_id', $caseIds)->pluck('file_path') as $path) {
            if ($path && Storage::disk('private')->exists($path)) {
                Storage::disk('private')->delete($path);
                $deletedFiles++;
            }
        }

        Inspection::whereIn('user_case_id', $caseIds)->forceDelete();
        Payment::whereIn('user_case_id', $caseIds)->delete();
        UserFormScheduling::whereIn('user_case_id', $caseIds)->delete();
        UploadedFile::whereIn('user_case_id', $caseIds)->forceDelete();
        CaseTimeline::whereIn('user_case_id', $caseIds)->delete();
        Component::whereIn('user_case_id', $caseIds)->delete();
        Vehicle::whereIn('user_case_id', $caseIds)->forceDelete();
        UserCase::withTrashed()->whereIn('id', $caseIds)->forceDelete();

        $this->info("Cleared {$caseCount} case(s) and {$deletedFiles} stored file(s).");
        $this->line('Accounts and pricing settings were kept. Ready for a fresh demo run.');

        return self::SUCCESS;
    }
}
