<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // The suite must never reach a third party — a paid AI provider above
        // all, where a stray call costs money and makes results
        // non-deterministic. Anything not explicitly faked now fails loudly.
        Http::preventStrayRequests();
    }
}
