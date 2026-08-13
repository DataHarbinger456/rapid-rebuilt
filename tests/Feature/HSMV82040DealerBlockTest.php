<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\ReadsPdfFormFields;
use Tests\TestCase;

/**
 * Section 7 of the 82040, the dealer sales tax report on page 2.
 *
 * The section heading ends "(If applicable)", and it applies to a dealer
 * filing on their own account. The numbers belong to that dealer, not to Rapid
 * Rebuilt: two dealers using this portal have two different licences. It used
 * to read Setting::get('prvip_license_number'), one company-wide value that is
 * also empty, so the box printed nothing for anybody.
 *
 * Both branches are read back out of a real filled document, because the
 * failure that matters here is a private customer's form carrying a dealer's
 * licence number.
 */
class HSMV82040DealerBlockTest extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_82040';
    }

    /**
     * Every box in the Section 7 row. Date of Sale and Amount of Tax are
     * deliberately unmapped, so they must stay blank on both branches.
     */
    private const SECTION_7_BOXES = [
        'Florida Sales Tax Registration Number',
        'Dealer License Number',
        'Date of Sale',
        'Amount of Tax',
    ];

    private function caseFor(User $owner): UserCase
    {
        $case = UserCase::create([
            'user_id' => $owner->id,
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
            'title_number' => '0123456789',
            'year' => 2020,
            'make' => 'Honda',
            'model' => 'Accord',
            'body_type' => 'Sedan',
            'color' => 'Blue',
            'odometer_reading' => 128450,
            'odometer_date' => '2025-11-04',
            'odometer_status' => 'actual',
        ]);

        return $case->fresh();
    }

    private function dealer(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'first_name' => 'Genesis',
            'last_name' => 'Nicholson',
            'email' => 'dealer+' . uniqid() . '@example.test',
            'user_type' => 'dealer',
            'dealer_name' => 'Rapid Rebuilt Motors',
            'license_type' => 'VI',
            'license_number' => '1043277',
            'sales_tax_registration_number' => '78-8012345678-4',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
        ], $overrides));
    }

    private function privateCustomer(): User
    {
        return User::factory()->create([
            'first_name' => 'Kelvyn',
            'last_name' => 'Quiroz',
            'email' => 'customer+' . uniqid() . '@example.test',
            'user_type' => 'user',
            'address_1' => '1841 NW 21st Terrace',
            'city' => 'Miami',
            'states' => 'FL',
            'zip' => '33142',
        ]);
    }

    public function test_a_dealer_case_fills_section_7_from_the_dealers_own_record(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->caseFor($this->dealer()));

        $this->assertSame('78-8012345678-4', $values['Florida Sales Tax Registration Number']);

        // One box for two columns: the licence class and its number.
        $this->assertSame('VI-1043277', $values['Dealer License Number']);
    }

    /**
     * The failure that would actually matter: a private customer's application
     * carrying somebody's dealer credentials.
     */
    public function test_a_private_customer_leaves_the_whole_block_blank(): void
    {
        // A dealer exists in the system, so a mapping that read a company
        // setting or the first dealer it found would still print something.
        $this->dealer();

        $values = $this->fieldValuesInFilledPdf($this->caseFor($this->privateCustomer()));

        foreach (self::SECTION_7_BOXES as $box) {
            $this->assertSame(
                '',
                $values[$box] ?? '',
                "[{$box}] is part of Section 7 and must be blank on a private customer's form."
            );
        }
    }

    /**
     * Date of Sale and Amount of Tax are not stored, so they stay blank even
     * for a dealer. Better an empty box the dealer completes than a number we
     * invented on a sales tax report.
     */
    public function test_the_unstored_boxes_stay_blank_even_for_a_dealer(): void
    {
        $values = $this->fieldValuesInFilledPdf($this->caseFor($this->dealer()));

        $this->assertSame('', $values['Date of Sale'] ?? '');
        $this->assertSame('', $values['Amount of Tax'] ?? '');
    }

    /**
     * A dealer who registered before this field existed, or who left it blank
     * at signup, still gets their licence number. One missing value must not
     * take the other with it.
     */
    public function test_a_dealer_without_a_sales_tax_number_still_gets_their_licence(): void
    {
        $values = $this->fieldValuesInFilledPdf(
            $this->caseFor($this->dealer(['sales_tax_registration_number' => null]))
        );

        $this->assertSame('', $values['Florida Sales Tax Registration Number'] ?? '');
        $this->assertSame('VI-1043277', $values['Dealer License Number']);
    }

    /**
     * A half-populated licence prints what we hold, never a stray separator.
     */
    public function test_a_partial_licence_never_prints_a_bare_hyphen(): void
    {
        $numberOnly = $this->fieldValuesInFilledPdf(
            $this->caseFor($this->dealer(['license_type' => null]))
        );

        $this->assertSame('1043277', $numberOnly['Dealer License Number']);

        $typeOnly = $this->fieldValuesInFilledPdf(
            $this->caseFor($this->dealer(['license_number' => null]))
        );

        $this->assertSame('VI', $typeOnly['Dealer License Number']);

        $neither = $this->fieldValuesInFilledPdf(
            $this->caseFor($this->dealer(['license_type' => null, 'license_number' => null]))
        );

        $this->assertSame('', $neither['Dealer License Number'] ?? '');
    }

    /**
     * Page 2 carries a second box named 'Sales Tax Registration Number', which
     * belongs to the rental-use exemption claim rather than to Section 7.
     * Filling it would assert an exemption nobody claimed.
     */
    public function test_the_exemption_sales_tax_box_is_never_touched(): void
    {
        foreach ([$this->dealer(), $this->privateCustomer()] as $owner) {
            $values = $this->fieldValuesInFilledPdf($this->caseFor($owner));

            $this->assertSame(
                '',
                $values['Sales Tax Registration Number'] ?? '',
                'The exemption section was filled from the dealer block.'
            );
        }
    }

    /**
     * The values come off the case's own owner, so two dealers produce two
     * different forms. This is what the old company-wide setting could not do.
     */
    public function test_two_dealers_get_their_own_numbers(): void
    {
        $first = $this->fieldValuesInFilledPdf($this->caseFor($this->dealer()));

        $second = $this->fieldValuesInFilledPdf($this->caseFor($this->dealer([
            'license_type' => 'VW',
            'license_number' => '9900123',
            'sales_tax_registration_number' => '39-0099887766-1',
        ])));

        $this->assertSame('VI-1043277', $first['Dealer License Number']);
        $this->assertSame('VW-9900123', $second['Dealer License Number']);
        $this->assertNotSame(
            $first['Florida Sales Tax Registration Number'],
            $second['Florida Sales Tax Registration Number']
        );
    }
}
