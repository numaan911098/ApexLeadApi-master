@component('mail::message')

Hi {{$user->name}},

@if ($user->isEmailVerificationNeeded())
You are just one click away from creating your LeadGen App account.
Just click the button below.
@else
Please click the below button to verify your LeadGen account.
@endif

@component('mail::button', ['url' => $verifyLink])
@if ($user->isEmailVerificationNeeded())
Activate my account
@else
Verify Account
@endif
@endcomponent

Didn't ask for this email? No further action is required.
For any issues, contact us at hello@leadgenapp.io

Thanks,<br>
{{ config('app.name') }}
@endcomponent
