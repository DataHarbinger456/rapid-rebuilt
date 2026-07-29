@component('mail::message')
# Case Submitted Successfully

Hello {{ $user->name }},

Your rebuilt title case has been submitted successfully. Here are the details:

**Case Number:** {{ $case->case_number }}

@if($vehicle)
**Vehicle Information:**
- **VIN:** {{ $vehicle->vin }}
- **Year/Make/Model:** {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}
- **Color:** {{ ucfirst($vehicle->color ?? 'N/A') }}
@endif

## What's Next?

1. **Document Review** - Our team will review your submitted documents
2. **Payment** - Once approved, you'll receive a payment link
3. **Scheduling** - After payment, you can schedule your inspection
4. **Inspection** - A certified inspector will verify your vehicle
5. **Title Issued** - Upon passing, we'll process your rebuilt title

@component('mail::button', ['url' => $dashboardUrl])
View Case Details
@endcomponent

If you have any questions, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }}

---
<small>This email was sent because you submitted a rebuilt title case. If you did not submit this case, please contact us immediately.</small>
@endcomponent
