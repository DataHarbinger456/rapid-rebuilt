<?php

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * business_name/business_email are the single source of truth (that's what
 * the real seeder and production DB use). companyName()/companyEmail() used
 * to read company_name/company_email instead — a key nothing ever wrote in
 * production, so every PDF and outbound email silently fell back to the
 * hardcoded default.
 */
class SettingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_company_email_reads_the_business_email_setting(): void
    {
        Setting::set('business_email', 'contact@rapidrebuilt.com');

        $this->assertSame('contact@rapidrebuilt.com', Setting::companyEmail());
    }

    public function test_company_name_reads_the_business_name_setting(): void
    {
        Setting::set('business_name', 'Rapid Rebuilt LLC');

        $this->assertSame('Rapid Rebuilt LLC', Setting::companyName());
    }
}
