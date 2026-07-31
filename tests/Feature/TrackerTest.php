<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TrackerTest extends TestCase
{
    use DatabaseTransactions;

    private function makeCase(string $status = 'scheduled'): UserCase
    {
        $customer = User::factory()->create([
            'email' => 'tracker.owner.' . uniqid() . '@example.com',
            'phone' => '555' . random_int(1000000, 9999999),
        ]);

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TRACK-' . uniqid(),
            'status' => $status,
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA111222',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'color' => 'blue',
            'title_state' => 'FL',
        ]);

        return $case;
    }

    public function test_valid_signed_url_shows_case_number_and_stages(): void
    {
        $case = $this->makeCase('scheduled');

        $response = $this->get(URL::signedRoute('case.track', ['case' => $case->id]));

        $response->assertOk();
        $response->assertSee($case->case_number);
        $response->assertSee('Documents Received');
        $response->assertSee('Inspection Scheduled');
        $response->assertSee('Title Issued');
        $response->assertSee('2020 Honda Accord');
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $case = $this->makeCase();

        $this->get("/track/{$case->id}")->assertForbidden();
    }

    public function test_tampered_signed_url_is_rejected(): void
    {
        $caseA = $this->makeCase();
        $caseB = $this->makeCase();

        $signedForA = URL::signedRoute('case.track', ['case' => $caseA->id]);
        $tampered = str_replace("/track/{$caseA->id}", "/track/{$caseB->id}", $signedForA);

        $this->get($tampered)->assertForbidden();
    }

    public function test_tracker_exposes_no_owner_pii(): void
    {
        $case = $this->makeCase();

        $response = $this->get(URL::signedRoute('case.track', ['case' => $case->id]));

        $response->assertOk();
        $response->assertDontSee($case->user->email);
        $response->assertDontSee($case->user->phone);
        $response->assertDontSee($case->user->first_name);
    }
}
