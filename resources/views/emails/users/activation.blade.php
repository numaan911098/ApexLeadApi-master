@component('mail::message')

## **{{ $user->name }}** your account is {{ $user->active ? 'Activated' : 'Deactivated' }}.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
