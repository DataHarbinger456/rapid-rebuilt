@component('mail::message')
# Inspection Scheduled

Hello {{ $case->user->name }},

Your vehicle inspection has been confirmed. Please review the details below:

@component('mail::panel')
**Date:** {{ $appointment->start->format('l, F j, Y') }}

**Time:** {{ $appointment->start->format('g:i A') }} - {{ $appointment->end->format('g:i A') }}

{{-- There is no inspection_type/location column. A mobile booking is the
     one with a customer address to travel to; office bookings store none
     (AppointmentController::store). Guard on location_address, not
     full_address, which still returns the default state for office rows. --}}
**Type:** {{ $appointment->location_address ? 'Mobile Inspection (We come to you)' : 'Office Inspection' }}

@if($appointment->location_address)
**Location:** {{ $appointment->full_address }}
@else
**Location:** Rapid Rebuilt Office
123 Main Street, Miami, FL 33101
@endif
@endcomponent

**Vehicle:** {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
**VIN:** {{ $vehicle->vin }}
**Case #:** {{ $case->case_number }}

## Please Have Ready:

- Your vehicle (clean and accessible)
- Original title document
- All parts receipts and invoices
- Valid Florida driver's license
- Proof of insurance

@component('mail::button', ['url' => $detailsUrl])
View Appointment Details
@endcomponent

## Need to Reschedule?

You can reschedule your appointment up to 24 hours before the scheduled time. Please note that late cancellations may incur a fee.

Thanks,<br>
{{ config('app.name') }}

---
<small>Add this to your calendar: {{ $appointment->start->format('Ymd\THis') }} - {{ $appointment->end->format('Ymd\THis') }}</small>
@endcomponent
