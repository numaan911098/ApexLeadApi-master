@component('mail::message')

It looks like you tried to sign in from a different location, device, or browser.
<p>Enter this 6 digit code to confirm your identity:
  <br><strong>{{$details['code']}}</strong>
</p>

Account: {{ $details['account'] }}<br>
Date: {{ $details['date'] }}<br>
Location: {{ $details['location'] }}<br>
IP Address: {{ $details['ip'] }}<br>
Operating system: {{ $details['os'] }}<br>
Browser: {{ $details['browser'] }}<br>
Device: {{ $details['device'] }}<br>

<br> If you have not tried to login, ignore this email and we recommend you to change your password immediately.

Thanks,<br>
{{ config('app.name') }} App
@endcomponent
