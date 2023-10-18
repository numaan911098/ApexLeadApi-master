@component('mail::message')

## **{{ $user->name }}** your Subscription to **Pro Plan** is cancelled successfully.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
