@component('mail::message')
# Inspection Reminder

Hello {{ $case->user->name }},

This is a friendly reminder that your vehicle inspection is scheduled for **{{ $hoursUntil <= 24 ? 'tomorrow' : "in {$hoursUntil} hours" }}**.

@component('mail::panel')
**Date:** {{ $appointment->start->format('l, F j, Y') }}

**Time:** {{ $appointment->start->format('g:i A') }}

@if($appointment->location_address)
**Location:** {{ $appointment->full_address }}
@else
**Location:** Rapid Rebuilt Office
@endif
@endcomponent

**Vehicle:** {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
**Case #:** {{ $case->case_number }}

## Checklist Before Your Inspection:

@component('mail::table')
| Item | Status |
|:-----|:------:|
| Vehicle is clean and accessible | ☐ |
| VIN plate is visible | ☐ |
| Original title document ready | ☐ |
| Parts receipts organized | ☐ |
| Valid ID ready | ☐ |
| Insurance card ready | ☐ |
@endcomponent

@component('mail::button', ['url' => $detailsUrl, 'color' => 'primary'])
View Appointment Details
@endcomponent

**Need to reschedule?** Contact us at least 24 hours before your appointment to avoid cancellation fees.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
