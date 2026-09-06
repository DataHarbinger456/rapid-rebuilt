@component('mail::message')
# Missed Appointment

Hello {{ $case->user->name }},

We were unable to complete your vehicle inspection on **{{ $scheduling->start->format('l, F j, Y') }} at {{ $scheduling->start->format('g:i A') }}**.

**Case #:** {{ $case->case_number }}

Your case is still open. Please book a new time at your earliest convenience — the sooner we inspect the vehicle, the sooner your title can be processed.

@component('mail::button', ['url' => route('customer.cases.schedule', $case)])
Reschedule Inspection
@endcomponent

If something came up and you need help, just reply to this email or contact our support team.

Thanks,<br>
{{ config('app.name') }}

---
<small>Repeated missed appointments may incur a rescheduling fee.</small>
@endcomponent
