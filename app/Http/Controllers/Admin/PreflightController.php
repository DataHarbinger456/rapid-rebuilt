<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Preflight\PreflightCheck;
use App\Services\Preflight\PreflightService;
use Illuminate\View\View;

/**
 * Admin-facing view of the same checks the artisan command runs.
 *
 * Exists because the person about to demo the system is not usually the person
 * with shell access to the host.
 */
class PreflightController extends Controller
{
    public function __construct(
        private readonly PreflightService $preflight,
    ) {}

    public function index(): View
    {
        // Runs live on every load rather than caching: a cached answer about
        // whether the host works is worth nothing, and this page is loaded
        // rarely and deliberately. Costs one HTTPS call and two process spawns.
        $checks = $this->preflight->run();

        return view('admin.preflight.index', [
            'checks' => $checks,
            'failCount' => count(array_filter($checks, fn (PreflightCheck $c): bool => $c->failed())),
            'warnCount' => count(array_filter(
                $checks,
                fn (PreflightCheck $c): bool => $c->status === PreflightCheck::WARN
            )),
        ]);
    }
}
