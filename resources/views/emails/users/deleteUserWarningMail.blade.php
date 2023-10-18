@inject('utilService', 'App\Services\Util')
@component('mail::message')

Hi {{ $details['name'] }},

@if($details['final'] )
Your LeadGen App account {{ $details['email'] }} has been unused for a long time and it will be deleted on {{ $details['date'] }}.
This action will permenantly delete your account with its data from LeadGen App system and there will be no way to restore your account.

You should have received one warning prior to this. Please consider this the last warning before we take further action.

If you want to use your account and cancel its deletion, <a href="{{$utilService->config('leadgen.app_url')}}">sign in</a> before {{ $details['date'] }}.

@else
We've noticed that your LeadGen App account has been unused for long time. We routinely remove inactive accounts to ensure
we're not storing any data you don't want us to. Your account will be deleted on {{ $details['date'] }}.

This action will permanently delete your account with its data from LeadGen App and there will be no way to restore your account.

If you like to keep your account just sign in below:

@component('mail::button', ['url' => $utilService->config('leadgen.app_url')])
Sign In
@endcomponent

@endif
Thanks,<br>
{{ config('app.name') }} App
@endcomponent
