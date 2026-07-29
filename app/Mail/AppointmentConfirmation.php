<?php

namespace App\Mail;

use App\Models\UserFormScheduling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public UserFormScheduling $appointment;

    /**
     * Create a new message instance.
     */
    public function __construct(UserFormScheduling $appointment)
    {
        $this->appointment = $appointment->load(['case.vehicle', 'case.user', 'inspector']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $date = $this->appointment->start->format('M j, Y');
        return new Envelope(
            subject: "Inspection Confirmed for {$date} - Rapid Rebuilt",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.confirmation',
            with: [
                'appointment' => $this->appointment,
                'case' => $this->appointment->case,
                'vehicle' => $this->appointment->case->vehicle,
                'detailsUrl' => route('customer.appointments.show', $this->appointment),
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
