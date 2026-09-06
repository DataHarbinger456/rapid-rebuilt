<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\UserFormScheduling;
use App\Models\Vehicle;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Every public send* method on NotificationService must land exactly one
 * email in the array transport and must not log a swallowed failure.
 * sendEmail() catches everything and only Log::error()s, so a missing or
 * broken Blade view is invisible in production — this test makes it fail
 * the suite instead. It enumerates the methods by reflection so a new
 * send* method is covered the moment it is added (see $fixtures).
 */
class NotificationEmailCoverageTest extends TestCase
{
    use DatabaseTransactions;

    private NotificationService $service;

    private User $customer;

    private User $inspector;

    private UserCase $case;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);
        $this->customer = User::factory()->create(['user_type' => 'user']);
        $this->inspector = User::factory()->create(['user_type' => 'inspector']);

        $this->case = UserCase::create([
            'user_id' => $this->customer->id,
            'case_number' => 'RR-COV-' . uniqid(),
            'status' => 'paid',
            'service_type' => 'rebuilt_title',
            'total_amount' => 250.00,
        ]);

        Vehicle::create([
            'user_case_id' => $this->case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'color' => 'blue',
            'odometer_reading' => 128450,
        ]);
    }

    /**
     * Arguments for each send* method. Adding a send* method without an
     * entry here fails test_every_send_method_has_a_fixture.
     *
     * @return array<string, callable(): array>
     */
    private function fixtures(): array
    {
        return [
            'sendCaseSubmitted' => fn () => [$this->case],
            'sendStatusChanged' => fn () => [$this->case, 'scheduled'],
            'sendInspectorAssigned' => fn () => [$this->case, $this->inspector],
            'sendPaymentConfirmation' => fn () => [$this->case],
            'sendCaseCancelled' => fn () => [$this->case],
            'sendAppointmentConfirmation' => fn () => [$this->scheduling()],
            'sendAppointmentRescheduled' => fn () => [$this->scheduling()],
            'sendAppointmentCancelled' => fn () => [$this->scheduling(), 'Inspector unavailable'],
            'sendAppointmentReminder' => fn () => [$this->scheduling()],
            'sendNoShowNotification' => fn () => [$this->scheduling()],
            'sendInspectionResult' => fn () => [$this->inspection('passed')],
            'sendDocumentsReady' => fn () => [$this->case],
            'sendCaseCompleted' => fn () => [$this->case],
        ];
    }

    /** @return string[] */
    private function sendMethods(): array
    {
        $methods = (new ReflectionClass(NotificationService::class))->getMethods(ReflectionMethod::IS_PUBLIC);

        return array_values(array_filter(
            array_map(fn (ReflectionMethod $m) => $m->getName(), $methods),
            fn (string $name) => str_starts_with($name, 'send')
        ));
    }

    public function test_every_send_method_has_a_fixture(): void
    {
        $missing = array_diff($this->sendMethods(), array_keys($this->fixtures()));

        $this->assertSame([], array_values($missing), 'Add a fixture for each new send* method.');
    }

    #[DataProvider('sendMethodProvider')]
    public function test_send_method_lands_one_email_without_a_swallowed_failure(string $method): void
    {
        Log::shouldReceive('error')->never();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $this->service->{$method}(...($this->fixtures()[$method])());

        $sent = $this->sentEmails();
        $this->assertCount(1, $sent, "{$method} should send exactly one email");

        $html = $sent[0]->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString($this->case->case_number, $html, "{$method} body should reference the case");
        $this->assertStringNotContainsString('@component', $html, "{$method} rendered raw Blade, not HTML");
    }

    public static function sendMethodProvider(): array
    {
        $methods = (new ReflectionClass(NotificationService::class))->getMethods(ReflectionMethod::IS_PUBLIC);
        $out = [];
        foreach ($methods as $m) {
            if (str_starts_with($m->getName(), 'send')) {
                $out[$m->getName()] = [$m->getName()];
            }
        }

        return $out;
    }

    // =====================
    // Helpers
    // =====================

    /** @return \Symfony\Component\Mailer\SentMessage[] */
    private function sentEmails(): array
    {
        return app('mailer')->getSymfonyTransport()->messages()->all();
    }

    private function scheduling(): UserFormScheduling
    {
        return UserFormScheduling::create([
            'user_id' => $this->customer->id,
            'user_case_id' => $this->case->id,
            'inspector_id' => $this->inspector->id,
            'start' => now()->addDay()->setTime(10, 0),
            'end' => now()->addDay()->setTime(11, 0),
            'status' => 'confirmed',
            'location_name' => 'Office Inspection',
            'location_state' => 'FL',
        ]);
    }

    private function inspection(string $result): Inspection
    {
        return Inspection::create([
            'user_case_id' => $this->case->id,
            'inspector_id' => $this->inspector->id,
            'result' => $result,
            'completed_at' => now(),
        ]);
    }
}
