<?php

namespace App\Jobs;

use App\Models\DocumentExtraction;
use App\Services\DocumentExtractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Runs one extraction attempt.
 *
 * QUEUE_CONNECTION defaults to sync, so in development this executes inline.
 * The job boundary still earns its keep: turning on a real queue later is an
 * env change rather than a rewrite, and the retry policy lives in one place.
 */
class ExtractDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * Provider rate limits recover in seconds, not minutes; a third attempt a
     * minute out is the last one worth paying for.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60];

    /**
     * Comfortably above the provider timeout in config/extraction.php so a
     * slow response fails as a provider error rather than a killed worker.
     */
    public int $timeout = 120;

    public function __construct(
        public readonly int $extractionId,
    ) {}

    public function handle(DocumentExtractionService $service): void
    {
        $extraction = DocumentExtraction::find($this->extractionId);

        if ($extraction === null) {
            return;
        }

        // A newer attempt has taken over; this one must not overwrite it.
        if ($extraction->status === DocumentExtraction::STATUS_SUPERSEDED) {
            return;
        }

        try {
            $service->perform($extraction);
        } catch (Throwable $e) {
            // Record the failure on the final attempt only, so intermediate
            // retries do not each write a failed row and a timeline event.
            if ($this->attempts() >= $this->tries) {
                $service->fail($extraction, $e);
            }

            throw $e;
        }
    }

    /**
     * Reached when the queue gives up for a reason handle() never sees, such
     * as the job timing out.
     */
    public function failed(Throwable $e): void
    {
        $extraction = DocumentExtraction::find($this->extractionId);

        if ($extraction === null || $extraction->status === DocumentExtraction::STATUS_FAILED) {
            return;
        }

        app(DocumentExtractionService::class)->fail($extraction, $e);
    }
}
