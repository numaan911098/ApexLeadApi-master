@component('mail::message')

Hi {{ $details['name'] }},

I hope you're well.

@if($details['first'] )
The payment for your LeadGen App account didn't go through this month and your account access is currently restricted.
@else
This is a reminder that the payment for your LeadGen App account didn't go through this month and your account access is currently restricted.
@endif

This can have different reasons like a problem with the bank, card expiry or insufficient funds.

To regain access to the account, click on the button below and update the bank/card information.

@component('mail::button', ['url' => $details['url']])
Update Your Bank Details
@endcomponent

This will re-activate the account and ensures your forms and lead access will keep active.

Thanks,<br>
{{ config('app.name') }} App
@endcomponent
