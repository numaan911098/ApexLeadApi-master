@component('mail::message')
## {{ $user->name }} your password has been changed @if ($isAdmin) by Administrator @endif.

@if ($isAdmin)
Your new password is **{{ $password }}**
@else
Please be informed that your password has been changed. If this change was not initiated by you, please take immediate action:
- Change your password.
- Enable two-factor authentication (2FA) for added security.
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent
