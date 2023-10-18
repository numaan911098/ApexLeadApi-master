@component('mail::message')

**{{ $user->name }}**,

Sorry, we're unable to complete the payment please click below button to complete your payment.

@component('mail::button', ['url' => $paymentUrl])
Complete Payment Here
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
