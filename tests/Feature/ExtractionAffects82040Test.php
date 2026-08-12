<?php

namespace Tests\Feature;

use App\Models\DocumentExtraction;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\UserCase;
use App\Models\Vehicle;
use App\Services\DocumentExtractionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Tests\Concerns\ReadsPdfFormFields;
use Tests\TestCase;

/**
 * The test that proves the feature is worth having: a title read by the model
 * and confirmed by staff changes what is stamped on the HSMV 82040.
 *
 * A Florida title is an Original application, an out-of-state title is a
 * Transfer (client, 5 Aug 2026). If extraction cannot move that checkbox, it
 * has not reached the state form at all.
 *
 * The provider is faked; nothing here touches the network.
 */
class ExtractionAffects82040Test extends TestCase
{
    use DatabaseTransactions;
    use ReadsPdfFormFields;

    protected function formKey(): string
    {
        return 'hsmv_82040';
    }

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');
    }

    private function makeCase(): UserCase
    {
        $customer = User::factory()->create();

        $case = UserCase::create([
            'user_id' => $customer->id,
            'case_number' => 'RR-TEST-' . uniqid(),
            'status' => 'pending_documents',
            'service_type' => 'rebuilt_title',
            'inspection_fee' => 150.00,
            'service_fee' => 100.00,
            'total_amount' => 250.00,
        ]);

        // What the customer entered at intake: a Florida title, so the form
        // starts out as an Original application.
        Vehicle::create([
            'user_case_id' => $case->id,
            'vin' => '1HGCV1F30LA123456',
            'title_state' => 'FL',
            'title_brand' => 'salvage',
            'year' => 2020,
            'make' => 'HONDA',
            'model' => 'ACCORD',
        ]);

        return $case->fresh();
    }

    private function makeSalvageTitleUpload(UserCase $case): UploadedFile
    {
        $path = 'cases/' . $case->id . '/documents/salvage_title/title.png';

        Storage::disk('private')->put(
            $path,
            file_get_contents(base_path('tests/Fixtures/salvage-title.png'))
        );

        return UploadedFile::create([
            'user_case_id' => $case->id,
            'user_id' => $case->user_id,
            'uploaded_by' => $case->user_id,
            'document_type' => 'salvage_title',
            'file_path' => $path,
            'path' => $path,
            'original_name' => 'title.png',
            'mime_type' => 'image/png',
            'file_size' => 8208,
            'size' => 8208,
            'status' => 'pending',
        ]);
    }

    public function test_a_confirmed_out_of_state_title_switches_the_82040_to_transfer(): void
    {
        $case = $this->makeCase();

        // Before: intake said Florida.
        $before = $this->stampedValues($case);
        $this->assertSame('On', $before['Original']);
        $this->assertSame('Off', $before['Transfer']);

        // The uploaded title actually shows Georgia.
        Prism::fake([
            StructuredResponseFake::make()->withStructured([
                'vin' => '1HGCV1F30LA123456',
                'title_number' => '0123456789',
                'title_state' => 'GA',
                'title_brand' => 'salvage',
                'year' => '2020',
                'make' => 'HONDA',
                'model' => 'ACCORD',
                'body_type' => '4D SEDAN',
                'color' => 'BLUE',
                'weight' => '3400',
                'odometer_reading' => '128450',
                'odometer_date' => '2025-11-04',
                'odometer_status' => 'actual',
                'previous_state' => null,
                'license_plate' => 'ABC1234',
                'owner_name_on_title' => 'SAMPLE TESTCASE',
            ]),
        ]);

        $document = $this->makeSalvageTitleUpload($case);
        $extraction = app(DocumentExtractionService::class)->extract($document);

        $this->assertSame(DocumentExtraction::STATUS_COMPLETED, $extraction->fresh()->status);

        // Reading it is not enough: until staff confirm, the form is unchanged.
        $this->assertSame('On', $this->stampedValues($case->fresh())['Original']);

        $staff = User::factory()->create(['user_type' => 'staff']);

        $this->actingAs($staff)
            ->post(route('admin.extractions.confirm', [$case->id, $extraction->id]), [
                'fields' => collect($extraction->fresh()->fields)
                    ->mapWithKeys(fn ($field): array => [
                        $field->field_key => [
                            'status' => $field->ai_value === null ? 'rejected' : 'confirmed',
                            'value' => $field->ai_value,
                        ],
                    ])
                    ->all(),
            ])
            ->assertSessionHas('success');

        $this->assertSame('GA', $case->vehicle()->first()->title_state);

        $after = $this->stampedValues($case->fresh());

        $this->assertSame('On', $after['Transfer'], 'A Georgia title must file as a Transfer.');
        $this->assertSame('Off', $after['Original'], 'Original must not stay ticked.');
    }
}
