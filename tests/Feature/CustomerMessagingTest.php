<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use App\Models\UserCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * The customer messaging loop: compose against one of your own cases, send it
 * to the support pool (recipient_id null), and see it on the index.
 */
class CustomerMessagingTest extends TestCase
{
    use DatabaseTransactions;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['user_type' => 'user']);
    }

    private function caseFor(User $owner): UserCase
    {
        return UserCase::create([
            'user_id' => $owner->id,
            'status' => 'paid',
            'service_type' => 'rebuilt_title',
            'total_amount' => 250.00,
        ]);
    }

    // =====================
    // Compose screen
    // =====================

    public function test_the_compose_screen_preselects_the_requested_own_case(): void
    {
        $case = $this->caseFor($this->customer);

        $response = $this->actingAs($this->customer)
            ->get('/customer/messages/create?case_id=' . $case->id);

        $response->assertOk()->assertSee($case->case_number);

        $this->assertTrue(
            $response->viewData('selectedCase')?->is($case),
            'The requested case should be preselected.'
        );
    }

    public function test_without_a_case_id_nothing_is_preselected(): void
    {
        $this->caseFor($this->customer);

        $response = $this->actingAs($this->customer)->get('/customer/messages/create');

        $response->assertOk();
        $this->assertNull($response->viewData('selectedCase'));
    }

    public function test_someone_elses_case_id_is_neither_selected_nor_offered(): void
    {
        $strangersCase = $this->caseFor(User::factory()->create(['user_type' => 'user']));

        $response = $this->actingAs($this->customer)
            ->get('/customer/messages/create?case_id=' . $strangersCase->id);

        $response->assertOk()
            ->assertDontSee($strangersCase->case_number);

        $this->assertNull($response->viewData('selectedCase'));
        $this->assertFalse(
            $response->viewData('cases')->contains($strangersCase),
            'A foreign case must not appear in the select at all.'
        );
    }

    public function test_a_customer_with_no_cases_sees_the_empty_state(): void
    {
        $this->actingAs($this->customer)
            ->get('/customer/messages/create')
            ->assertOk()
            ->assertSee('Messages are attached to a case.');
    }

    // =====================
    // Sending
    // =====================

    public function test_a_valid_message_is_stored_and_redirects_to_the_index(): void
    {
        $case = $this->caseFor($this->customer);

        $this->actingAs($this->customer)
            ->post('/customer/messages', [
                'case_id' => $case->id,
                'message' => 'Where do I drop off the receipts?',
            ])
            ->assertRedirect(route('customer.messages'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('messages', [
            'user_case_id' => $case->id,
            'sender_id' => $this->customer->id,
            'recipient_id' => null,
            'message' => 'Where do I drop off the receipts?',
            'message_type' => 'text',
            'is_internal' => 0,
        ]);
    }

    public function test_sending_against_someone_elses_case_stores_nothing(): void
    {
        $strangersCase = $this->caseFor(User::factory()->create(['user_type' => 'user']));

        $this->actingAs($this->customer)
            ->post('/customer/messages', [
                'case_id' => $strangersCase->id,
                'message' => 'Let me in.',
            ])
            ->assertSessionHasErrors('case_id');

        $this->assertDatabaseMissing('messages', ['user_case_id' => $strangersCase->id]);
    }

    public function test_an_empty_message_is_rejected(): void
    {
        $case = $this->caseFor($this->customer);

        $this->actingAs($this->customer)
            ->post('/customer/messages', ['case_id' => $case->id, 'message' => ''])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('messages', ['user_case_id' => $case->id]);
    }

    public function test_an_over_long_message_is_rejected(): void
    {
        $case = $this->caseFor($this->customer);

        $this->actingAs($this->customer)
            ->post('/customer/messages', [
                'case_id' => $case->id,
                'message' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors('message');

        $this->assertDatabaseMissing('messages', ['user_case_id' => $case->id]);
    }

    // =====================
    // Index
    // =====================

    public function test_the_index_shows_a_sent_message_with_its_case(): void
    {
        $case = $this->caseFor($this->customer);

        $case->messages()->create([
            'sender_id' => $this->customer->id,
            'recipient_id' => null,
            'message' => 'Following up on my receipts.',
            'message_type' => 'text',
            'is_internal' => false,
        ]);

        $this->actingAs($this->customer)
            ->get('/customer/messages')
            ->assertOk()
            ->assertSee('Following up on my receipts.')
            ->assertSee($case->case_number)
            ->assertSee('You');
    }

    public function test_the_index_hides_staff_internal_notes(): void
    {
        $case = $this->caseFor($this->customer);

        $case->messages()->create([
            'sender_id' => User::factory()->create(['user_type' => 'staff'])->id,
            'recipient_id' => $this->customer->id,
            'message' => 'Internal: verify this customer by phone.',
            'message_type' => 'text',
            'is_internal' => true,
        ]);

        $this->actingAs($this->customer)
            ->get('/customer/messages')
            ->assertOk()
            ->assertDontSee('Internal: verify this customer by phone.');
    }

    public function test_the_index_hides_another_customers_message(): void
    {
        $stranger = User::factory()->create(['user_type' => 'user']);

        $this->caseFor($stranger)->messages()->create([
            'sender_id' => $stranger->id,
            'recipient_id' => null,
            'message' => 'This belongs to somebody else.',
            'message_type' => 'text',
            'is_internal' => false,
        ]);

        $this->actingAs($this->customer)
            ->get('/customer/messages')
            ->assertOk()
            ->assertDontSee('This belongs to somebody else.')
            ->assertSee('No messages yet');
    }

    public function test_an_incoming_unread_reply_is_flagged_new(): void
    {
        $staff = User::factory()->create(['user_type' => 'staff']);

        $this->caseFor($this->customer)->messages()->create([
            'sender_id' => $staff->id,
            'recipient_id' => $this->customer->id,
            'message' => 'Bring the originals to the inspection.',
            'message_type' => 'text',
            'is_internal' => false,
            'read_at' => null,
        ]);

        $this->actingAs($this->customer)
            ->get('/customer/messages')
            ->assertOk()
            ->assertSee('Bring the originals to the inspection.')
            ->assertSee($staff->full_name)
            ->assertSee('New');
    }

    public function test_a_message_you_sent_is_never_flagged_new(): void
    {
        // Outgoing messages have read_at null forever; that must not render
        // as an unread badge on the sender's own screen.
        $this->caseFor($this->customer)->messages()->create([
            'sender_id' => $this->customer->id,
            'recipient_id' => null,
            'message' => 'My own outgoing note.',
            'message_type' => 'text',
            'is_internal' => false,
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->customer)->get('/customer/messages');

        $response->assertOk()->assertSee('My own outgoing note.');
        $this->assertStringNotContainsString(
            'badge badge-primary me-2',
            $response->getContent(),
            'An outgoing message must not carry the New badge.'
        );
    }
}
