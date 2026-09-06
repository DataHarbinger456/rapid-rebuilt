@component('mail::message')
# Case Cancelled

Hello {{ $case->user->name }},

Your rebuilt title case **{{ $case->case_number }}** has been cancelled.

@if($case->vehicle)
**Vehicle:** {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}
@endif

If this was a mistake, or you would like to start again, you can open a new case from your dashboard at any time.

@component('mail::button', ['url' => route('customer.dashboard')])
Go to Dashboard
@endcomponent

If you have any questions about this cancellation, please contact our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
