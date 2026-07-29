<?php

namespace App\Mail;

use App\Models\Inspection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InspectionCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Inspection $inspection;

    /**
     * Create a new message instance.
     */
    public function __construct(Inspection $inspection)
    {
        $this->inspection = $inspection->load(['case.vehicle', 'case.user', 'inspector']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $result = ucfirst($this->inspection->result);
        return new Envelope(
            subject: "Inspection {$result} - Case #{$this->inspection->case->case_number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.inspections.completed',
            with: [
                'inspection' => $this->inspection,
                'case' => $this->inspection->case,
                'vehicle' => $this->inspection->case->vehicle,
                'passed' => $this->inspection->result === 'passed',
                'caseUrl' => route('customer.cases.show', $this->inspection->case),
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
