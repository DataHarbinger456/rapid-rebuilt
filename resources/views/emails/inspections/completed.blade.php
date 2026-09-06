@component('mail::message')
# Inspection {{ $passed ? 'Passed' : 'Results' }}

Hello {{ $case->user->name }},

@if($passed)
**Congratulations!** Your vehicle has passed the rebuilt title inspection.

Your {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }} has been verified and meets all Florida HSMV requirements for a rebuilt title.

## What Happens Next?

1. **Document Generation** - We're preparing your HSMV forms
2. **DMV Submission** - Documents will be submitted to the FL DMV
3. **Title Processing** - The DMV will process your rebuilt title
4. **Title Issued** - You'll receive your new Florida rebuilt title

Expected timeline: 2-4 weeks after DMV submission.
@else
We've completed the inspection of your {{ $vehicle->year }} {{ $vehicle->make }} {{ $vehicle->model }}.

Unfortunately, the vehicle did not pass all inspection requirements at this time.

## Inspection Notes:

{{ $inspection->inspector_notes ?? 'Please contact us for detailed information about the inspection results.' }}

## Next Steps:

1. Review the inspection findings
2. Address the noted issues
3. Schedule a re-inspection

Our team is available to answer any questions and help you understand what needs to be corrected.
@endif

@component('mail::panel')
**Case Number:** {{ $case->case_number }}
**VIN:** {{ $vehicle->vin }}
**Inspection Date:** {{ $inspection->completed_at?->format('F j, Y') ?? $inspection->created_at->format('F j, Y') }}
**Inspector:** {{ $inspection->inspector->name ?? 'N/A' }}
**Result:** {{ ucfirst($inspection->result) }}
@endcomponent

@component('mail::button', ['url' => $caseUrl, 'color' => $passed ? 'success' : 'primary'])
View Case Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}

---
<small>If you have questions about your inspection results, please reply to this email or call our support line.</small>
@endcomponent
