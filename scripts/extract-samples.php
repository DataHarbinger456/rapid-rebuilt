<?php

/**
 * One-off harness: run the real extraction pipeline over docs/samples/*.jpeg
 * and dump the results as JSON for side-by-side model comparison.
 *
 * Hits the live provider and bills the configured API key. Not part of the
 * test suite; run deliberately.
 *
 * Usage: php scripts/extract-samples.php <out.json> [model]
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Services\DocumentExtractionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$outPath = $argv[1] ?? throw new RuntimeException('Pass an output path.');
$model = $argv[2] ?? config('extraction.model');

config(['extraction.model' => $model]);

$samples = glob(base_path('docs/samples/*.jpeg'));
sort($samples);

if ($samples === []) {
    throw new RuntimeException('No sample images found in docs/samples/.');
}

$customer = User::where('user_type', 'user')->first()
    ?? throw new RuntimeException('No customer user to attach cases to.');

$service = app(DocumentExtractionService::class);
$results = [];

fwrite(STDERR, "Model: {$model} — " . count($samples) . " images\n");

foreach ($samples as $i => $absolute) {
    $label = basename($absolute);
    $n = $i + 1;

    fwrite(STDERR, sprintf('[%2d/%d] %s ... ', $n, count($samples), $label));

    $case = UserCase::create([
        'user_id' => $customer->id,
        'case_number' => 'RR-SAMPLE-' . strtoupper(substr(md5($label . $model), 0, 8)),
        'status' => 'pending_documents',
        'service_type' => 'rebuilt_title',
        'inspection_fee' => 150.00,
        'service_fee' => 100.00,
        'total_amount' => 250.00,
    ]);

    // Copy into the private disk exactly as a customer upload would land.
    $storedPath = "cases/{$case->id}/documents/salvage_title/" . $n . '.jpeg';
    Storage::disk('private')->put($storedPath, file_get_contents($absolute));

    $document = UploadedFile::create([
        'user_case_id' => $case->id,
        'user_id' => $customer->id,
        'uploaded_by' => $customer->id,
        'document_type' => 'salvage_title',
        'file_path' => $storedPath,
        'path' => $storedPath,
        'original_name' => $label,
        'mime_type' => 'image/jpeg',
        'file_size' => filesize($absolute),
        'size' => filesize($absolute),
        'status' => 'pending',
    ]);

    $started = microtime(true);

    try {
        // QUEUE_CONNECTION=sync, so this runs the job inline against the
        // real provider.
        $extraction = $service->extract($document)->fresh('fields');

        $results[$n] = [
            'image' => $label,
            'case_id' => $case->id,
            'status' => $extraction->status,
            'model' => $extraction->model,
            'provider' => $extraction->provider,
            'prompt_tokens' => $extraction->prompt_tokens,
            'completion_tokens' => $extraction->completion_tokens,
            'seconds' => round(microtime(true) - $started, 2),
            'fields' => $extraction->fields
                ->sortBy('id')
                ->mapWithKeys(fn ($f): array => [$f->field_key => $f->ai_value])
                ->all(),
        ];

        fwrite(STDERR, "ok ({$results[$n]['seconds']}s)\n");
    } catch (Throwable $e) {
        $results[$n] = [
            'image' => $label,
            'case_id' => $case->id,
            'status' => 'failed',
            'model' => $model,
            'error' => $e->getMessage(),
            'seconds' => round(microtime(true) - $started, 2),
            'fields' => [],
        ];

        fwrite(STDERR, 'FAILED: ' . $e->getMessage() . "\n");
    }
}

file_put_contents($outPath, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

fwrite(STDERR, "Wrote {$outPath}\n");
