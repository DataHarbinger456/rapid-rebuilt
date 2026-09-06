@component('mail::message')
# Case Status Updated

Hello {{ $case->user->name }},

The status of your rebuilt title case has changed.

@component('mail::panel')
**Case #:** {{ $case->case_number }}

**New Status:** {{ $newStatus }}
@endcomponent

@if($case->vehicle)
**Vehicle:** {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}
@endif

Sign in to see what happens next and whether anything is needed from you.

@component('mail::button', ['url' => route('customer.cases.show', $case)])
View Case
@endcomponent

Thanks,<br>
{{ config('app.name') }}

---
<small>You can turn off these emails under Settings in your account.</small>
@endcomponent
