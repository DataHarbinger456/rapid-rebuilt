<?php

namespace Tests\Unit;

use App\Support\PdftkBinary;
use Tests\TestCase;

/**
 * Production has no system pdftk, so which binary gets picked is the
 * difference between a working title application and a red error message.
 *
 * These pin the order and, more importantly, the fallback: a developer machine
 * with a working system pdftk must keep working whether or not the bundled
 * install is present.
 */
class PdftkBinaryTest extends TestCase
{
    private string $fakeBundled;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeBundled = sys_get_temp_dir() . '/pdftk-test-' . uniqid();
    }

    protected function tearDown(): void
    {
        @unlink($this->fakeBundled);

        parent::tearDown();
    }

    private function makeExecutable(): void
    {
        file_put_contents($this->fakeBundled, "#!/bin/sh\nexit 0\n");
        chmod($this->fakeBundled, 0o755);
    }

    public function test_an_explicit_override_wins(): void
    {
        $this->makeExecutable();

        config([
            'pdftk.binary' => '/custom/pdftk',
            'pdftk.bundled_path' => $this->fakeBundled,
            'pdftk.fallback' => 'pdftk',
        ]);

        $resolved = PdftkBinary::resolve();

        $this->assertSame('/custom/pdftk', $resolved['path']);
        $this->assertSame(PdftkBinary::SOURCE_CONFIGURED, $resolved['source']);
    }

    public function test_the_bundled_install_is_preferred_over_the_system_binary(): void
    {
        $this->makeExecutable();

        config([
            'pdftk.binary' => null,
            'pdftk.bundled_path' => $this->fakeBundled,
            'pdftk.fallback' => 'pdftk',
        ]);

        $resolved = PdftkBinary::resolve();

        $this->assertSame($this->fakeBundled, $resolved['path']);
        $this->assertSame(PdftkBinary::SOURCE_BUNDLED, $resolved['source']);
    }

    public function test_it_falls_back_to_the_system_binary_when_nothing_is_bundled(): void
    {
        config([
            'pdftk.binary' => null,
            'pdftk.bundled_path' => $this->fakeBundled, // never created
            'pdftk.fallback' => 'pdftk',
        ]);

        $resolved = PdftkBinary::resolve();

        $this->assertSame('pdftk', $resolved['path']);
        $this->assertSame(PdftkBinary::SOURCE_SYSTEM, $resolved['source']);
    }

    /**
     * A half-finished install must not take down a machine that was working.
     */
    public function test_a_present_but_non_executable_bundle_falls_through(): void
    {
        file_put_contents($this->fakeBundled, 'not executable');
        chmod($this->fakeBundled, 0o644);

        config([
            'pdftk.binary' => null,
            'pdftk.bundled_path' => $this->fakeBundled,
            'pdftk.fallback' => 'pdftk',
        ]);

        $this->assertSame(PdftkBinary::SOURCE_SYSTEM, PdftkBinary::resolve()['source']);
    }

    public function test_a_blank_override_is_ignored_rather_than_used_as_a_path(): void
    {
        config([
            'pdftk.binary' => '   ',
            'pdftk.bundled_path' => $this->fakeBundled,
            'pdftk.fallback' => 'pdftk',
        ]);

        $this->assertSame(PdftkBinary::SOURCE_SYSTEM, PdftkBinary::resolve()['source']);
    }

    public function test_every_source_has_a_label_for_the_preflight_page(): void
    {
        foreach ([
            PdftkBinary::SOURCE_CONFIGURED,
            PdftkBinary::SOURCE_BUNDLED,
            PdftkBinary::SOURCE_SYSTEM,
        ] as $source) {
            $this->assertNotSame('', PdftkBinary::describeSource($source));
        }
    }
}
