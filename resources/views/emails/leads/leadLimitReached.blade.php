@component('mail::message')
Hi {{$details['name']}},

@if ($details['threshHold'] < 100)
We would like to inform you that your LeadGen App account has reached {{$details['threshHold']}}% of the lead submission limit. This means that you are approaching the maximum number of leads allowed for your current plan.
@else
We would like to inform you that your LeadGen App account has exceeded the limit on lead analytics. This means that you will currently no longer get insights into lead submissions in any of your forms.

@endif

@if ($details['resetPeriod'] !== 'NONE')
<h2> How You Can Re-Activate Lead Analytics</h2>

<ol>
  <li>Login to your LeadGen App account.</li>
  <li>Go to the Plans page.</li>
  <li>Switch between the monthly/ yearly toggle to find your current plan.</li>
  <li>Check your lead quota.</li>
  <li>Upgrade to a higher plan to access to more Lead Analytics each month (Limits shown on the plans page based on each plan).</li>
</ol>

Your quota will reset on the first day of the next
@if ($details['resetPeriod'] === 'MONTHLY')
    month
@elseif ($details['resetPeriod'] === 'YEARLY')
    year
@elseif ($details['resetPeriod'] === 'AS_PER_PLAN')
    billing date
@endif
. Until then, no more lead submissions will be recorded. Upgrading today will ensure you will no longer miss any leads!
@endif

Best wishes,<br>
Your LeadGen App Team
@endcomponent
