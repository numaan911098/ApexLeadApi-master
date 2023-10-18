@component('mail::message')

### Form Details

**Form:** https://{{config('leadgen.forms_domain')}}/preview/forms/{{$formVariant->form->id}}/variants/{{$formVariant->id}}

**Variant:** {{ $formVariant->title }} ({{ $formVariant->id }})

**Customer:** {{ $formVariant->form->createdBy->email }}

**Phishing Content:** {{ $phishingContent }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
