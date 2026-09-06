@component('mail::message')
# Appointment Rescheduled

Hello {{ $case->user->name }},

Your vehicle inspection has been moved. Here are the new details:

@component('mail::panel')
**Date:** {{ $scheduling->start->format('l, F j, Y') }}

**Time:** {{ $scheduling->start->format('g:i A') }} - {{ $scheduling->end->format('g:i A') }}

**Type:** {{ $scheduling->location_address ? 'Mobile Inspection (We come to you)' : 'Office Inspection' }}

@if($scheduling->location_address)
**Location:** {{ $scheduling->full_address }}
@else
**Location:** Rapid Rebuilt Office
123 Main Street, Miami, FL 33101
@endif
@endcomponent

**Case #:** {{ $case->case_number }}

@component('mail::button', ['url' => route('customer.appointments.show', $scheduling)])
View Appointment Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}

---
<small>Add this to your calendar: {{ $scheduling->start->format('Ymd\THis') }} - {{ $scheduling->end->format('Ymd\THis') }}</small>
@endcomponent
