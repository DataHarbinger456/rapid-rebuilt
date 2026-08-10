<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Preflight\PreflightCheck;
use App\Services\Preflight\PreflightService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The preflight checks exist because a green build can still be a broken
 * deployment: two external binaries, an API key and a schema state all live on
 * the host rather than in the repository.
 *
 * Every external thing is faked here. A diagnostic that needs the real world to
 * be healthy in order to test it is not a diagnostic.
 */
class PreflightTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Describes the host for one test. Everything is present unless overridden.
     *
     * Called exactly once per test, because both Process::fake() and
     * Http::fake() merge their stubs rather than replacing them: a second call
     * that looks like an override is easy to write and hard to notice.
     *
     * The patterns are globbed on both sides because an array command reaches
     * the matcher as Symfony's escaped command line, which starts with a quote
     * rather than the binary name.
     *
     * @param  array{pdftk?: mixed, pdftoppm?: mixed, nhtsa?: mixed}  $host
     */
    private function fakeHost(array $host = []): void
    {
        // Without this, an unmatched pattern silently runs the real binary and
        // a "missing pdftk" test passes on any machine that has pdftk.
        Process::preventStrayProcesses();

        Process::fake([
            '*pdftk*' => $host['pdftk'] ?? Process::result('pdftk port to java 3.3.3'),
            '*pdftoppm*' => $host['pdftoppm'] ?? Process::result(errorOutput: 'pdftoppm version 26.04.0'),
        ]);

        Http::fake([
            'vpic.nhtsa.dot.gov/*' => $host['nhtsa'] ?? Http::response(['Results' => [
                ['Variable' => 'Model Year', 'Value' => '2025'],
            ]]),
        ]);

        Storage::fake('private');

        config(['extraction.default' => 'anthropic']);
        config(['prism.providers.anthropic.api_key' => 'sk-ant-fake-value-for-testing']);
    }

    /**
     * What a shell reports for a command that is not installed.
     */
    private function binaryMissing(): \Illuminate\Process\FakeProcessResult
    {
        return Process::result(errorOutput: 'command not found', exitCode: 127);
    }

    /**
     * @return array<string, PreflightCheck>
     */
    private function checksByName(): array
    {
        $keyed = [];

        foreach (app(PreflightService::class)->run() as $check) {
            $keyed[$check->name] = $check;
        }

        return $keyed;
    }

    public function test_a_healthy_host_passes_every_check(): void
    {
        $this->fakeHost();

        foreach ($this->checksByName() as $name => $check) {
            $this->assertTrue($check->passed(), "{$name} should pass on a healthy host, got: {$check->detail}");
        }
    }

    public function test_a_missing_pdftk_fails_because_no_form_can_be_produced(): void
    {
        $this->fakeHost(['pdftk' => $this->binaryMissing()]);

        $check = $this->checksByName()['pdftk'];

        $this->assertSame(PreflightCheck::FAIL, $check->status);
        $this->assertNotNull($check->remedy);
    }

    /**
     * The severity split is the point of the page. A missing pdftoppm only
     * costs PDF uploads, which still leaves a working system, so calling it a
     * failure would send an operator hunting a problem they do not have.
     */
    public function test_a_missing_pdftoppm_only_degrades(): void
    {
        $this->fakeHost(['pdftoppm' => $this->binaryMissing()]);

        $checks = $this->checksByName();

        $this->assertSame(PreflightCheck::WARN, $checks['pdftoppm']->status);
        $this->assertFalse(app(PreflightService::class)->hasFailures(array_values($checks)));
    }

    public function test_a_missing_api_key_fails_and_names_the_variable_to_set(): void
    {
        $this->fakeHost();
        config(['prism.providers.anthropic.api_key' => '']);

        $check = $this->checksByName()['Extraction API key'];

        $this->assertSame(PreflightCheck::FAIL, $check->status);
        $this->assertStringContainsString('ANTHROPIC_API_KEY', (string) $check->remedy);
    }

    /**
     * Whitespace is not a key. A provider configured with a stray space would
     * otherwise pass here and fail on the first real document.
     */
    public function test_a_whitespace_only_api_key_counts_as_missing(): void
    {
        $this->fakeHost();
        config(['prism.providers.anthropic.api_key' => '   ']);

        $this->assertSame(PreflightCheck::FAIL, $this->checksByName()['Extraction API key']->status);
    }

    /**
     * The whole point of reporting presence rather than value. This asserts on
     * every field of every check, because a leak anywhere is a leak.
     */
    public function test_the_api_key_value_never_appears_in_any_output(): void
    {
        $this->fakeHost();
        $secret = 'sk-ant-SUPERSECRET-must-never-be-rendered';
        config(['prism.providers.anthropic.api_key' => $secret]);

        foreach ($this->checksByName() as $check) {
            $serialised = implode(' ', [$check->name, $check->detail, $check->impact, (string) $check->remedy]);
            $this->assertStringNotContainsString($secret, $serialised);
        }

        // And not through the page either.
        $response = $this->actingAs($this->admin())->get(route('admin.preflight'));

        $response->assertOk();
        $response->assertDontSee($secret);
        $response->assertDontSee('SUPERSECRET');
    }

    /**
     * EXTRACTION_PROVIDER is free text. A typo would otherwise look like a
     * missing key and send someone setting a variable that is never read.
     */
    public function test_an_unknown_provider_is_reported_as_such(): void
    {
        $this->fakeHost();
        config(['extraction.default' => 'nosuchprovider']);

        $check = $this->checksByName()['Extraction API key'];

        $this->assertSame(PreflightCheck::FAIL, $check->status);
        $this->assertStringContainsString('nosuchprovider', $check->detail);
    }

    public function test_missing_extraction_tables_fail(): void
    {
        $this->fakeHost();

        Schema::shouldReceive('hasTable')->andReturn(false);

        $check = $this->checksByName()['Extraction tables'];

        $this->assertSame(PreflightCheck::FAIL, $check->status);
        $this->assertStringContainsString('document_extractions', $check->detail);
        $this->assertStringContainsString('migrate', (string) $check->remedy);
    }

    /**
     * NHTSA being down is a normal Tuesday, and the review screen is built to
     * show its "could not confirm" notice when it happens. Failing the whole
     * preflight for it would train people to ignore the page.
     */
    public function test_an_unreachable_nhtsa_only_degrades(): void
    {
        $this->fakeHost(['nhtsa' => Http::response(null, 503)]);

        $check = $this->checksByName()['NHTSA VIN decoder'];

        $this->assertSame(PreflightCheck::WARN, $check->status);
        $this->assertStringContainsString('503', $check->detail);
    }

    public function test_a_network_exception_does_not_break_the_page(): void
    {
        $this->fakeHost(['nhtsa' => fn () => throw new \RuntimeException('dns failure')]);

        $check = $this->checksByName()['NHTSA VIN decoder'];

        $this->assertSame(PreflightCheck::WARN, $check->status);
    }

    // =====================
    // COMMAND
    // =====================

    public function test_the_command_exits_zero_when_everything_passes(): void
    {
        $this->fakeHost();

        $this->artisan('preflight')
            ->expectsOutputToContain('All checks passed')
            ->assertExitCode(0);
    }

    public function test_the_command_exits_non_zero_on_failure_so_a_deploy_can_gate_on_it(): void
    {
        $this->fakeHost(['pdftk' => $this->binaryMissing()]);

        $this->artisan('preflight')->assertExitCode(1);
    }

    public function test_warnings_alone_do_not_fail_the_command(): void
    {
        $this->fakeHost(['nhtsa' => Http::response(null, 503)]);

        $this->artisan('preflight')->assertExitCode(0);
        $this->artisan('preflight', ['--fail-on-warning' => true])->assertExitCode(1);
    }

    // =====================
    // PAGE ACCESS
    // =====================

    public function test_an_admin_can_see_the_page(): void
    {
        $this->fakeHost();

        $response = $this->actingAs($this->admin())->get(route('admin.preflight'));

        $response->assertOk();
        $response->assertViewIs('admin.preflight.index');
        $response->assertSee('All checks passed');
        $response->assertSee('pdftk');
    }

    public function test_the_page_reports_failures_with_their_remedy(): void
    {
        $this->fakeHost(['pdftk' => $this->binaryMissing()]);

        $response = $this->actingAs($this->admin())->get(route('admin.preflight'));

        $response->assertOk();
        $response->assertSee('1 check failed');
        $response->assertSee('Install pdftk');
        $response->assertViewHas('failCount', 1);
    }

    /**
     * A diagnostics page nobody can navigate to is a page nobody runs. The
     * link is admin-only for the same reason the route is.
     */
    public function test_the_page_is_reachable_from_the_admin_menu(): void
    {
        $this->fakeHost();

        // Any admin page renders the sidebar. The case list is used rather
        // than the dashboard because the dashboard has a pre-existing query
        // against a users.role column that does not exist.
        $this->actingAs($this->admin())
            ->get(route('admin.cases.index'))
            ->assertOk()
            ->assertSee(route('admin.preflight'), false);
    }

    public function test_non_admin_staff_are_not_shown_the_menu_link(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'staff']))
            ->get(route('admin.cases.index'))
            ->assertOk()
            ->assertDontSee(route('admin.preflight'), false);
    }

    /**
     * Staff run cases; only an admin configures the host.
     */
    public function test_non_admin_staff_cannot_see_the_page(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'staff']))
            ->get(route('admin.preflight'))
            ->assertForbidden();
    }

    public function test_a_customer_cannot_see_the_page(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->get(route('admin.preflight'))
            ->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.preflight'))->assertRedirect(route('login'));
    }

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }
}
