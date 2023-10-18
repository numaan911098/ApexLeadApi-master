@inject('utilService', 'App\Services\Util')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Leadgen</title>
    <style>
      body {
        margin: 0;
        padding: 0;
      }
    </style>
</head>
<body>
  <div id="leadgen-form-wrap-{{ $form->key }}"><leadgen-form-{{ $form->key }}></leadgen-form-{{ $form->key }}></div>
  <script type="text/javascript" src="{{$utilService->config('leadgen.forms_domain')}}/js/lf.min.js/{{ $form->key }}" async></script>
</body>
</html>
