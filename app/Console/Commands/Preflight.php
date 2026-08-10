<?php

namespace App\Console\Commands;

use App\Services\Preflight\PreflightCheck;
use App\Services\Preflight\PreflightService;
use Illuminate\Console\Command;

/**
 * Checks the host dependencies that composer cannot install.
 *
 * Exits non-zero when anything has failed, so a deploy pipeline can gate on it.
 * Warnings do not fail the command: a degraded VIN cross-check is a working
 * deployment, and a check that cries wolf gets removed from the pipeline.
 */
class Preflight extends Command
{
    protected $signature = 'preflight {--fail-on-warning : Treat warnings as failures too}';

    protected $description = 'Check external binaries, API keys, migrations, storage and third-party APIs';

    public function handle(PreflightService $preflight): int
    {
        $checks = $preflight->run();

        $this->newLine();
        $this->line('  Preflight: ' . config('app.env') . ' @ ' . config('app.url'));
        $this->newLine();

        foreach ($checks as $check) {
            $this->line(sprintf(
                '  %s  %-22s %s',
                $this->marker($check),
                $check->name,
                $check->detail
            ));

            if ($check->remedy !== null) {
                $this->line(sprintf('      %s', $check->impact));
                $this->line(sprintf('      <options=bold>Fix:</> %s', $check->remedy));
            }
        }

        $this->newLine();

        $failed = array_filter($checks, fn (PreflightCheck $c): bool => $c->failed());
        $warned = array_filter($checks, fn (PreflightCheck $c): bool => $c->status === PreflightCheck::WARN);

        if ($failed !== []) {
            $this->error(sprintf(
                '  %d check(s) failed. Features that depend on them will not work.',
                count($failed)
            ));

            return self::FAILURE;
        }

        if ($warned !== []) {
            $this->warn(sprintf(
                '  %d degraded, nothing broken. The app works with its designed fallbacks.',
                count($warned)
            ));

            return $this->option('fail-on-warning') ? self::FAILURE : self::SUCCESS;
        }

        $this->info('  All checks passed.');

        return self::SUCCESS;
    }

    private function marker(PreflightCheck $check): string
    {
        return match ($check->status) {
            PreflightCheck::PASS => '<fg=green>PASS</>',
            PreflightCheck::WARN => '<fg=yellow>WARN</>',
            default => '<fg=red>FAIL</>',
        };
    }
}
