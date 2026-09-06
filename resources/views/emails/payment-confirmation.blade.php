@component('mail::message')
# Payment Received

Hello {{ $case->user->name }},

Thank you — we have received your payment.

@component('mail::panel')
**Case #:** {{ $case->case_number }}

**Amount:** ${{ number_format($case->total_amount, 2) }}
@endcomponent

## What's Next?

You can now schedule your vehicle inspection. Pick a date and time that works for you, at our office or at your location.

@component('mail::button', ['url' => route('customer.cases.schedule', $case)])
Schedule Inspection
@endcomponent

Thanks,<br>
{{ config('app.name') }}

---
<small>Keep this email as your receipt. If you did not make this payment, please contact us immediately.</small>
@endcomponent
