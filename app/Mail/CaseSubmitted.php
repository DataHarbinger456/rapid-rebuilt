<?php

namespace App\Mail;

use App\Models\UserCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CaseSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public UserCase $case;

    /**
     * Create a new message instance.
     */
    public function __construct(UserCase $case)
    {
        $this->case = $case->load(['vehicle', 'user']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Case #{$this->case->case_number} Submitted - Rapid Rebuilt",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.cases.submitted',
            with: [
                'case' => $this->case,
                'user' => $this->case->user,
                'vehicle' => $this->case->vehicle,
                'dashboardUrl' => route('customer.cases.show', $this->case),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
