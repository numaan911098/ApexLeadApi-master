@component('mail::message')

## **{{ $user->name }}** your role has changed by the LeadGen Administrator to **{{$user->roles->first()->name}}**.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
