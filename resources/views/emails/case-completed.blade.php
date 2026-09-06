@component('mail::message')
# Congratulations!

Hello {{ $case->user->name }},

Your rebuilt title case **{{ $case->case_number }}** is complete.

@if($case->vehicle)
**Vehicle:** {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}

**VIN:** {{ $case->vehicle->vin }}
@endif

Thank you for choosing {{ config('app.name') }}. Your case records and documents will remain available in your account.

@component('mail::button', ['url' => route('customer.cases.show', $case)])
View Case
@endcomponent

If you were happy with the service, we'd be grateful if you told a friend.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
