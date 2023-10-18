@inject('utilService', 'App\Services\Util')
@component('mail::message', ['whitelabel' => $whitelabel])

# Lead Summary



---
# QUESTIONS
---

@foreach($formLead->questionResponses as $response)


### Q. {{ $response->formQuestion->config['title'] }}

{{ $response->responseString($response) }}

@endforeach

@if($formLead->hiddenFieldResponses->count() > 0)
  ---
  # HIDDEN Fields
  ---
@endif


@foreach($formLead->hiddenFieldResponses as $response)


### {{ $response->formHiddenField->name }}

{{ $response->response }}

@endforeach

---
# META FIELDS
---

### REFERENCE NO: {{ $formLead->reference_no }}

@if ($formLead->formVisit->device_type)
  ### DEVICE TYPE: {{ $formLead->formVisit->device_type }}
@endif

@if ($formLead->formVisit->os)
  ### OS: {{ $formLead->formVisit->os }}
@endif

@if ($formLead->formVisit->browser)
  ### BROWSER: {{ $formLead->formVisit->browser }}
@endif

@if ($formLead->formVisit->ip)
  ### IP: {{ $formLead->formVisit->ip }}
@endif

@if ($formLead->formVisit->source_url)
  ### SOURCE URL: {{ $formLead->formVisit->source_url }}
@endif

@if(!$whitelabel->enabled)
  @component('mail::button', ['url' => $utilService->config('leadgen.app_url')])
  Go To LeadGen App
  @endcomponent
@endif

@endcomponent
