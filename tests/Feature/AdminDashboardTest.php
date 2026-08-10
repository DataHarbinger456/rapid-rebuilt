<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The admin dashboard is the first thing the sidebar links to, and it was
 * returning a 500: three of its queries named columns that do not exist
 * (users.role instead of user_type, user_cases.payment_status and total_price
 * instead of is_paid and total_amount).
 *
 * Nothing caught it because no test loaded the page. These tests exercise the
 * real queries against the real schema, which is the only thing that would
 * have.
 */
class AdminDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    public function test_an_admin_can_load_the_dashboard(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats');
    }

    public function test_staff_can_load_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'staff']))
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_a_customer_cannot(): void
    {
        $this->actingAs(User::factory()->create(['user_type' => 'user']))
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    /**
     * Every statistic is computed by its own query, so a stale column name in
     * any one of them takes the whole page down. Asserting each key is present
     * means the query behind it ran.
     */
    public function test_every_statistic_is_computed(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function (array $stats): bool {
            foreach ([
                'total_cases', 'active_cases', 'cases_this_month', 'completed_this_month',
                'total_users', 'total_inspectors', 'inspections_today', 'pending_documents',
                'pending_scheduling', 'pending_review', 'revenue_this_month',
            ] as $key) {
                if (! array_key_exists($key, $stats)) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * The user counts were reading users.role, which does not exist. Seeding
     * one of each type proves the replacement column actually discriminates
     * rather than just failing to throw.
     */
    public function test_the_user_counts_use_the_real_column(): void
    {
        $before = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->viewData('stats');

        User::factory()->create(['user_type' => 'user']);
        User::factory()->create(['user_type' => 'inspector']);
        // An admin must not be counted as either.
        User::factory()->create(['user_type' => 'admin']);

        $after = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->viewData('stats');

        $this->assertSame($before['total_users'] + 1, $after['total_users']);
        $this->assertSame($before['total_inspectors'] + 1, $after['total_inspectors']);
    }

    /**
     * Revenue was summing user_cases.total_price filtered on payment_status,
     * neither of which exists. The real columns are total_amount and is_paid.
     */
    public function test_revenue_sums_paid_cases_only(): void
    {
        $customer = User::factory()->create(['user_type' => 'user']);

        $before = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->viewData('stats')['revenue_this_month'];

        $this->makeCase($customer, paid: true, amount: 250.00);
        // Unpaid, and must not be counted.
        $this->makeCase($customer, paid: false, amount: 999.00);

        $after = $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->viewData('stats')['revenue_this_month'];

        $this->assertEquals(250.00, round($after - $before, 2));
    }

    public function test_the_revenue_chart_covers_six_months(): void
    {
        $response = $this->actingAs($this->admin())->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('revenue_chart', function (array $chart): bool {
            return count($chart) === 6
                && array_key_exists('month', $chart[0])
                && array_key_exists('revenue', $chart[0]);
        });
    }

    private function makeCase(User $customer, bool $paid, float $amount): UserCase
    {
        return UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => $amount,
            'is_paid' => $paid,
            'paid_at' => $paid ? now() : null,
        ]);
    }
}
