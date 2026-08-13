<?php

namespace Tests\Feature;

use App\Models\Inspection;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ReadsPdfFormFields;
use Tests\TestCase;

/**
 * The inspector-recorded odometer digit count, across all three forms that ask.
 *
 * "5 or 6 digit odometer" asks how many digits the instrument cluster
 * physically displays. The title never states it and the reading cannot imply
 * it, because 62,712 miles looks identical on either. Until 13 Aug we inferred
 * it from the model year; the client's answer is that inspectors record what
 * they can see.
 *
 * The rule that matters: the inspector's answer wins, and the model-year
 * fallback may only ever assert SIX digits. A pre-1995 car might have either,
 * so nothing but a human can tick the 5-digit box on a declaration sworn under
 * penalty of perjury.
 */
class OdometerDigitCountTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    /**
     * Defaults to the 82040; the per-form tests override it.
     */
    protected function formKey(): string
    {
        return $this->formKeyOverride ?? 'hsmv_82040';
    }

    private ?string $formKeyOverride = null;

    /**
     * The 5- and 6-digit box on each form, by exact PDF field name. Every one
     * is spelled differently, so none can be copied between forms.
     *
     * @return array<string, array{five: string, six: string}>
     */
    private const DIGIT_BOXES = [
        'hsmv_82040' => [
            'five' => '5-digit odometer now reads',
            'six' => '6-digit odometer now reads',
        ],
        'hsmv_82042' => [
            'five' => 'Select if 5 digit odometer reading',
            'six' => 'Select if 6 digit odometer reading',
        ],
        'hsmv_82050' => [
            'five' => '5 DIGIT OR',
            'six' => '6 DIGIT ODOMETER NOW READS',
        ],
    ];

    private function makeCase(
        int $modelYear = 2020,
        ?int $digitCount = null,
        bool $withInspection = true,
        string $result = 'passed'
    ): UserCase {
        $customer = User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
            'email' => 'kelvyn.quiroz+' . uniqid() . '@example.test',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
        ]);

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'year' => $modelYear,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ]);

        if ($withInspection) {
            Inspection::create([
                'user_case_id' => $case->id,
                'inspector_id' => User::factory()->create()->id,
                'result' => $result,
                'odometer_digit_count' => $digitCount,
            ]);
        }

        return $case->fresh();
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function everyFormThatAsks(): array
    {
        return [
            'HSMV 82040' => ['hsmv_82040'],
            'HSMV 82042' => ['hsmv_82042'],
            'HSMV 82050' => ['hsmv_82050'],
        ];
    }

    /**
     * A recorded 6 ticks the six box and leaves the five blank, on every form.
     */
    #[DataProvider('everyFormThatAsks')]
    public function test_a_recorded_six_digit_reading_ticks_only_the_six_box(string $formKey): void
    {
        $this->formKeyOverride = $formKey;
        $boxes = self::DIGIT_BOXES[$formKey];

        $values = $this->fieldValuesInFilledPdf($this->makeCase(digitCount: 6));

        $this->assertSame('On', $values[$boxes['six']], "{$formKey}: the 6-digit box should be ticked.");
        $this->assertContains(
            $values[$boxes['five']] ?? '',
            ['', 'Off'],
            "{$formKey}: the 5-digit box must stay blank when the inspector recorded six."
        );
    }

    /**
     * A recorded 5 ticks the five box, which nothing could do before.
     */
    #[DataProvider('everyFormThatAsks')]
    public function test_a_recorded_five_digit_reading_ticks_only_the_five_box(string $formKey): void
    {
        $this->formKeyOverride = $formKey;
        $boxes = self::DIGIT_BOXES[$formKey];

        $values = $this->fieldValuesInFilledPdf($this->makeCase(modelYear: 1988, digitCount: 5));

        $this->assertSame('On', $values[$boxes['five']], "{$formKey}: the 5-digit box should be ticked.");
        $this->assertContains(
            $values[$boxes['six']] ?? '',
            ['', 'Off'],
            "{$formKey}: the 6-digit box must stay blank when the inspector recorded five."
        );
    }

    /**
     * The inspector overrules the model year, not the other way round.
     *
     * A 2020 car would be inferred as six-digit, so recording five is the case
     * where the two sources disagree and the human has to win.
     */
    public function test_the_inspector_overrules_the_model_year_guess(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->makeCase(modelYear: 2020, digitCount: 5));

        $this->assertSame('On', $values['5-digit odometer now reads']);
        $this->assertContains($values['6-digit odometer now reads'] ?? '', ['', 'Off']);
    }

    /**
     * With no inspection yet, the model-year fallback still fills the form, so
     * a case that has not been inspected does not regress to a blank pair.
     */
    public function test_without_an_inspection_the_model_year_still_answers(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->makeCase(modelYear: 2020, withInspection: false)
        );

        $this->assertSame('On', $values['6-digit odometer now reads']);
        $this->assertContains($values['5-digit odometer now reads'] ?? '', ['', 'Off']);
    }

    /**
     * The fallback may never assert five digits.
     *
     * A pre-1995 car might have either cluster, so an uninspected old vehicle
     * leaves BOTH boxes blank for a human rather than guessing the smaller one.
     * This is the assertion that keeps a convenience inference off a sworn
     * declaration.
     */
    public function test_an_old_uninspected_vehicle_leaves_both_boxes_blank(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->makeCase(modelYear: 1988, withInspection: false)
        );

        $this->assertContains($values['5-digit odometer now reads'] ?? '', ['', 'Off']);
        $this->assertContains($values['6-digit odometer now reads'] ?? '', ['', 'Off']);
    }

    /**
     * An inspection that exists but was left blank falls back rather than
     * ticking nothing, since a half-filled checklist is normal mid-inspection.
     */
    public function test_an_unanswered_inspection_falls_back_to_the_model_year(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->makeCase(modelYear: 2020, digitCount: null)
        );

        $this->assertSame('On', $values['6-digit odometer now reads']);
        $this->assertContains($values['5-digit odometer now reads'] ?? '', ['', 'Off']);
    }

    /**
     * The two boxes are never both ticked, whatever the inputs.
     *
     * @return array<string, array{0: int, 1: int|null}>
     */
    public static function everyCombination(): array
    {
        return [
            'modern car, inspector says six' => [2020, 6],
            'modern car, inspector says five' => [2020, 5],
            'old car, inspector says six' => [1988, 6],
            'old car, inspector says five' => [1988, 5],
            'modern car, not answered' => [2020, null],
            'old car, not answered' => [1988, null],
        ];
    }

    #[DataProvider('everyCombination')]
    public function test_the_two_boxes_are_never_both_ticked(int $year, ?int $digitCount): void
    {
        $values = $this->fieldValuesInFilledPdf($this->makeCase($year, $digitCount));

        $ticked = array_filter([
            '5-digit odometer now reads' => ($values['5-digit odometer now reads'] ?? 'Off') !== 'Off',
            '6-digit odometer now reads' => ($values['6-digit odometer now reads'] ?? 'Off') !== 'Off',
        ]);

        $this->assertLessThanOrEqual(
            1,
            count($ticked),
            'Both digit boxes were ticked, which is two contradictory sworn statements.'
        );
    }

    /**
     * The API accepts 5 and 6 and rejects anything else, so a stray value can
     * never reach the form and silently tick neither box.
     *
     * The route is unnamed in routes/api.php, hence the literal URI.
     */
    public function test_the_api_only_accepts_five_or_six(): void
    {
        $this->assertSame([5, 6], Inspection::ODOMETER_DIGIT_COUNTS);

        // A completed inspection is closed to edits, so this one is still in
        // progress: recording the digit count is something done during it.
        $case = $this->makeCase(digitCount: null, result: 'in_progress');
        $inspection = $case->inspection;
        $inspector = $inspection->inspector;
        $uri = '/api/inspections/' . $inspection->id;

        foreach ([5, 6] as $valid) {
            $this->actingAs($inspector)
                ->putJson($uri, ['odometer_digit_count' => $valid])
                ->assertOk();

            $this->assertSame($valid, $inspection->fresh()->odometer_digit_count);
        }

        foreach ([4, 7, 0, 99] as $invalid) {
            $this->actingAs($inspector)
                ->putJson($uri, ['odometer_digit_count' => $invalid])
                ->assertStatus(422)
                ->assertJsonValidationErrors('odometer_digit_count');
        }

        // The last valid write survived every rejected one.
        $this->assertSame(6, $inspection->fresh()->odometer_digit_count);
    }

    /**
     * The inspector checklist offers exactly the two answers the forms have
     * boxes for, so the UI cannot produce a value the form cannot represent.
     */
    public function test_the_checklist_offers_only_the_two_valid_answers(): void
    {
        $odometer = Inspection::getChecklistTemplate()['odometer']['items'];

        $this->assertArrayHasKey('odometer_digit_count', $odometer);
        $this->assertSame([5, 6], $odometer['odometer_digit_count']['options']);
        $this->assertTrue($odometer['odometer_digit_count']['required']);
    }
}
