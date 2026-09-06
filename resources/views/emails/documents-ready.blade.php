@component('mail::message')
# Your Documents Are Ready

Hello {{ $case->user->name }},

The DMV paperwork for your rebuilt title case is complete and ready for you.

@component('mail::panel')
**Case #:** {{ $case->case_number }}

@if($case->vehicle)
**Vehicle:** {{ $case->vehicle->year }} {{ $case->vehicle->make }} {{ $case->vehicle->model }}
@endif
@endcomponent

## What's Next?

1. **Download** your completed forms from the case page
2. **Review and sign** where indicated
3. **Submit** the packet to your local tax collector's office

@component('mail::button', ['url' => route('customer.cases.show', $case)])
View Documents
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
