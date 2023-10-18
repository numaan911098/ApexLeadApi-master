@component('mail::message')

**{{ $user->name }}**,

Congratulations! you are successfully subscribed to **PRO** plan.

@component('mail::button', ['url' => $appUrl])
Access Your LeadGen Account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
