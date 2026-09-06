@component('mail::message')
# New Case Assigned

Hello {{ $inspector->name }},

You have been assigned to a rebuilt title inspection.

@component('mail::panel')
**Case #:** {{ $case->case_number }}

**Customer:** {{ $case->user->name }}

@if($case->vehicle)
**Vehicle:** {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}

**VIN:** {{ $case->vehicle->vin }}
@endif
@endcomponent

Open your inspector dashboard to review the case and any scheduled appointment.

@component('mail::button', ['url' => route('inspector.dashboard')])
Open Inspector Dashboard
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
