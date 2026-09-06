@component('mail::message')
# Appointment Cancelled

Hello {{ $case->user->name }},

Your vehicle inspection scheduled for **{{ $scheduling->start->format('l, F j, Y') }} at {{ $scheduling->start->format('g:i A') }}** has been cancelled.

@component('mail::panel')
**Case #:** {{ $case->case_number }}

**Reason:** {{ $reason }}
@endcomponent

Your case is still open. Please book a new time so we can keep things moving.

@component('mail::button', ['url' => route('customer.cases.schedule', $case)])
Reschedule Inspection
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
