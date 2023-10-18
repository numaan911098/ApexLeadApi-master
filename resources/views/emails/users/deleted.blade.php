@component('mail::message')

Hi {{ $details['name'] }},

@if ($source === \App\Enums\RequestSourceEnum::SOURCE_COMMAND)
## Your LeadGen App account **{{$details['email']}}** has been deleted due to a long period of inactivity.
@else

We regret to inform you that your LeadGen App account **{{$details['email']}}** has been deleted.
If this was not your intention or if you have any concerns, please feel free to reach out to our support team.

We appreciate your time with us and hope to see you again in the future.

@endif

Thanks,<br>
{{ config('app.name') }} App
@endcomponent
