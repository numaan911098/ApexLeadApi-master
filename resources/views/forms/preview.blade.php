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
  </style>
</head>
<body>
<div id="leadgen-form-wrap-{{ $form->key }}" style="{{ $style }}">
  <leadgen-form-{{ $form->key }} form-key="{{ $form->key }}" :form-variant-id="{{ $variant->id }}"></leadgen-form-{{ $form->key }}>
</div>
<script type="text/javascript" src="{{$utilService->config('leadgen.forms_domain')}}/js/lf.min.js/{{ $form->key }}" async></script>
</body>
</html>
