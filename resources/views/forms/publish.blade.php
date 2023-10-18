@inject('utilService', 'App\Services\Util')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>LeadGen</title>
    <style>
      body {
        min-height: 100vh;
        display: flex;
        align-items: center;
      }
      #leadgen-form-branding {
        animation: 1.5s fadeIn;
        animation-fill-mode: forwards;
        visibility: hidden;
        position: relative;
        margin-top: 120px;
      }
      @keyframes fadeIn {
        99% {
          visibility: hidden;
        }
        100% {
          visibility: visible;
        }
      }
    </style>
</head>
<body>
  <div id="leadgen-form-wrap-{{ $form->key }}" style="{{ $style }}">
    <leadgen-form-{{ $form->key }} form-key="{{ $form->key }}"></leadgen-form-{{ $form->key }}>
    @if ($form->brandingOnPublished())
      <!-- FORM PUBLISHED VIEW BRANDING -->
      <div id="leadgen-form-branding" class="lf-form__branding">
        {{$utilService->config('leadgen.branding.prefix')}}
        <a target="_blank" href="{{$utilService->config('leadgen.branding.url')}}">
          {{$utilService->config('leadgen.branding.title')}}
        </a>
      </div>
    @endif
  </div>
  <script type="text/javascript" src="{{$utilService->config('leadgen.forms_domain')}}/js/lf.min.js/{{ $form->key }}" async></script>
</body>
</html>
