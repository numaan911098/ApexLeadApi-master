@component('mail::message')
Hi {{$details['name']}},

We would like to inform you that your LeadGen App account has exceeded the limit on partial lead analytics. This means that you will currently no longer get insights into partial lead submissions in any of your forms.

@if ($details['resetPeriod'] !== 'NONE')
<h2> How You Can Re-Activate Partial Lead Analytics</h2>

<ol>
  <li>Login to your LeadGen App account.</li>
  <li>Go to the Plans page.</li>
  <li>Switch between the monthly/ yearly toggle to find your current plan.</li>
  <li>Check your partial lead quota.</li>
  <li>Upgrade to a higher plan to access to more Partial Lead Analytics each month (Limits shown on the plans page based on each plan).</li>
</ol>

Your quota will reset on the first day of the next
@if ($details['resetPeriod'] === 'MONTHLY')
    month
@elseif ($details['resetPeriod'] === 'YEARLY')
    year
@elseif ($details['resetPeriod'] === 'AS_PER_PLAN')
    billing date
@endif
. Until then, no more partial lead submissions will be recorded. Upgrading today will ensure you will no longer miss any leads!
@endif

Best wishes,<br>
Your LeadGen App Team
@endcomponent
