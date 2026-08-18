<?php

namespace Tests\Feature;

use App\Models\DocumentExtraction;
use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Smoke test: every real application screen renders with a 200 for an
 * appropriately-roled logged-in user.
 *
 * Not a functional test of any screen — a guard against the whole class of
 * "this view has never rendered" bugs this codebase has already produced
 * twice: the inspector documents screen 500ed on a nonexistent route name,
 * and its Generate All button 401'd behind the wrong auth. Neither was
 * noticed because nothing ever loaded the page.
 *
 * Excludes the purchased theme's demo views (miscellaneous/, comingsoon/,
 * errors/, apps/, ui-kits/) and every /api route: this is about the screens
 * people actually use, not the theme tour or JSON endpoints.
 */
class EveryScreenRendersTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $inspector;
    private User $customer;
    private array $seed = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['user_type' => 'admin']);
        $this->inspector = User::factory()->create(['user_type' => 'inspector']);
        $this->customer = User::factory()->create(['user_type' => 'user']);

        // Cases at each status the screens gate on, so a guard is exercised
        // with the status it exists for rather than 403ing on a status it is
        // rightly refusing.
        $byStatus = [];

        foreach (['draft', 'pending_payment', 'paid', 'inspection_passed'] as $status) {
            $case = UserCase::create([
                'user_id' => $this->customer->id,
                'case_number' => 'RR-TEST-' . uniqid(),
                'status' => $status,
                'service_type' => 'rebuilt_title',
                'inspection_fee' => 150.00,
                'service_fee' => 100.00,
                'total_amount' => 250.00,
            ]);

            Vehicle::create([
                'user_case_id' => $case->id,
                'vin' => '1HGCV1F30LA123456',
                'title_state' => 'GA',
                'year' => 2020,
                'make' => 'Honda',
                'model' => 'Accord',
                'body_type' => 'Sedan',
                'color' => 'Blue',
                'odometer_reading' => 128450,
                'odometer_date' => '2025-11-04',
                'odometer_status' => 'actual',
            ]);

            $byStatus[$status] = $case;
        }

        // The full workflow hangs off the paid case: appointment, passed
        // inspection, and the inspector screens' bindings.
        $paid = $byStatus['paid'];

        $inspection = Inspection::create([
            'user_case_id' => $paid->id,
            'inspector_id' => $this->inspector->id,
            'result' => 'passed',
            'odometer_digit_count' => 6,
            'completed_at' => now(),
        ]);

        $appointment = UserFormScheduling::create([
            'user_case_id' => $paid->id,
            'user_id' => $this->customer->id,
            'inspector_id' => $this->inspector->id,
            'start' => now()->addDay()->setTime(10, 0),
            'end' => now()->addDay()->setTime(11, 0),
            'subject' => 'Rebuilt inspection',
            'status' => 'confirmed',
        ]);

        $this->seed = [
            'case' => $byStatus['inspection_passed']->id,
            'case:draft' => $byStatus['draft']->id,
            'case:pending_payment' => $byStatus['pending_payment']->id,
            'case:paid' => $paid->id,
            'inspection' => $inspection->id,
            'appointment' => $appointment->id,
        ];
    }

    /**
     * The screens, in route-name order, each with the user who should see
     * it, the bindings its URI needs, an optional case status to bind, and an
     * optional query string.
     *
     * @return array<string, array{0: string, 1: string, 2?: array<int, string>, 3?: string, 4?: string}>
     */
    public static function screens(): array
    {
        return [
            // Customer portal.
            'customer.dashboard' => ['customer', '/customer'],
            'customer.appointments.index' => ['customer', '/customer/appointments'],
            'customer.appointments.show' => ['customer', '/customer/appointments/{appointment}', ['appointment']],
            'customer.appointments.reschedule' => ['customer', '/customer/appointments/{appointment}/reschedule', ['appointment']],
            'customer.cases.index' => ['customer', '/customer/cases'],
            'customer.cases.create' => ['customer', '/customer/cases/create'],
            'customer.cases.show' => ['customer', '/customer/cases/{case}', ['case:paid']],
            'customer.cases.documents' => ['customer', '/customer/cases/{case}/documents', ['case:paid']],
            'customer.cases.edit' => ['customer', '/customer/cases/{case}/edit', ['case:draft']],
            'customer.cases.pay' => ['customer', '/customer/cases/{case}/pay', ['case:pending_payment']],
            'customer.cases.payment-success' => ['customer', '/customer/cases/{case}/payment-success', ['case:pending_payment'], null, 'session_id=cs_test_123'],
            'customer.cases.schedule' => ['customer', '/customer/cases/{case}/schedule', ['case:paid']],
            'customer.documents.index' => ['customer', '/customer/documents'],
            'customer.faq' => ['customer', '/customer/faq'],
            'customer.messages' => ['customer', '/customer/messages'],
            'customer.messages.create' => ['customer', '/customer/messages/create'],
            'customer.notifications' => ['customer', '/customer/notifications'],
            'customer.payments.index' => ['customer', '/customer/payments'],
            'customer.profile' => ['customer', '/customer/profile'],
            'customer.settings' => ['customer', '/customer/settings'],
            'customer.support' => ['customer', '/customer/support'],

            // Help.
            'help.manual' => ['customer', '/help/manual'],

            // Admin.
            'admin.cases.index' => ['admin', '/admin/cases'],
            'admin.cases.show' => ['admin', '/admin/cases/{case}', ['case:paid']],
            'admin.reports.customers' => ['admin', '/admin/reports/customers'],
            'admin.reports.inspector-performance' => ['admin', '/admin/reports/inspector-performance'],

            // Inspector portal.
            'inspector.dashboard' => ['inspector', '/inspector'],
            'inspector.dashboard.index' => ['inspector', '/inspector/dashboard'],
            'inspector.agenda' => ['inspector', '/inspector/agenda'],
            'inspector.appointment.show' => ['inspector', '/inspector/appointments/{appointment}', ['appointment']],
            'inspector.checklist' => ['inspector', '/inspector/appointments/{appointment}/checklist', ['appointment']],
            'inspector.history' => ['inspector', '/inspector/history'],
            'inspector.components' => ['inspector', '/inspector/inspections/{inspection}/components', ['inspection']],
            'inspector.documents' => ['inspector', '/inspector/inspections/{inspection}/documents', ['inspection']],
            'inspector.photos' => ['inspector', '/inspector/inspections/{inspection}/photos', ['inspection']],
            'inspector.print' => ['inspector', '/inspector/inspections/{inspection}/print', ['inspection']],
            'inspector.summary' => ['inspector', '/inspector/inspections/{inspection}/summary', ['inspection']],
        ];
    }

    /**
     * @dataProvider screens
     */
    public function test_the_screen_renders(string $as, string $uri, array $bindings = [], string $_unused = null, string $query = null): void
    {
        $resolved = $uri;

        foreach ($bindings as $placeholder) {
            // 'case:draft' binds a case seeded at that status; a bare name
            // binds the default seed row.
            $key = $placeholder;

            if (str_contains($placeholder, ':')) {
                [$bare, $status] = explode(':', $placeholder, 2);
                $key = "case:{$status}";
                $placeholder = $bare;
            }

            $resolved = str_replace('{' . $placeholder . '}', (string) $this->seed[$key], $resolved);
        }

        if ($query) {
            $resolved .= '?' . $query;
        }

        $response = $this->actingAs($this->{$as})->get($resolved);

        $status = $response->status();

        // The print screen's correct behaviour without a generated package
        // is the flash redirect, not a render: its fixture seeds a passed
        // inspection but never generates documents, and print() sends the
        // inspector back with "No documents generated yet". Asserting that
        // redirect keeps this suite honest about reachable states instead
        // of generating a package just to paint the screen.
        $expected = $uri === '/inspector/inspections/{inspection}/print' ? 302 : 200;

        $this->assertTrue(
            $status === $expected,
            "GET {$resolved} as [{$as}] returned {$status}, not {$expected}."
            . ($status === 500 ? ' — the view is crashing.' : '')
        );
    }
}
