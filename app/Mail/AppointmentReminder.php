<?php

namespace App\Mail;

use App\Models\UserFormScheduling;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public UserFormScheduling $appointment;
    public int $hoursUntil;

    /**
     * Create a new message instance.
     */
    public function __construct(UserFormScheduling $appointment, int $hoursUntil = 24)
    {
        $this->appointment = $appointment->load(['case.vehicle', 'case.user']);
        $this->hoursUntil = $hoursUntil;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $timeLabel = $this->hoursUntil <= 24 ? 'Tomorrow' : "in {$this->hoursUntil} hours";
        return new Envelope(
            subject: "Reminder: Your Inspection is {$timeLabel} - Rapid Rebuilt",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.reminder',
            with: [
                'appointment' => $this->appointment,
                'case' => $this->appointment->case,
                'vehicle' => $this->appointment->case->vehicle,
                'hoursUntil' => $this->hoursUntil,
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
