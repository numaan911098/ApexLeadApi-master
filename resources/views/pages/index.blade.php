@inject('lpService', 'App\Services\LandingPageService')
@inject('utilService', 'App\Services\Util')
<!DOCTYPE html>
<html>
  <head>
    {{ $lpService->pageScripts($page, 'after_head_opening') }}
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <meta name="description" content="{{ $page->description }}">
    <meta name="keywords" content="{{ $page->keywords }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }}</title>

    <!-- Google / Search Engine Tags -->
    <meta itemprop="name" content="{{ $page->title }}">
    <meta itemprop="description" content="{{ $page->description }}">
    @if($page->config['media_type']['type'] === 'image')
    <meta itemprop="image" content="{{ $page->config['media_type']['image_url'] }}">
    @endif

    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="{{ route('leadgenpage', ['slug' => $page->slug]) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $page->title }}">
    <meta property="og:description" content="{{ $page->description }}">
    @if($page->config['media_type']['type'] === 'image')
    <meta property="og:image" content="{{ $page->config['media_type']['image_url'] }}">
    @endif

    <!-- Twitter Meta Tags -->
    @if($page->config['media_type']['type'] === 'image')
    <meta name="twitter:card" content="{{ $page->config['media_type']['image_url'] }}">
    @endif
    <meta name="twitter:title" content="{{ $page->title }}">
    <meta name="twitter:description" content="{{ $page->description }}">
    @if($page->config['media_type']['type'] === 'image')
    <meta name="twitter:image" content="{{ $page->config['media_type']['image_url'] }}">
    @endif

    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i" rel="stylesheet">
    <style>
      {!! $style !!}
    </style>
    <style>
      body {
        font-family: 'Roboto', sans-serif;
        padding: 0;
        margin: 0;
      }

      h1, h2, h3, h4, h5, h6, p, ul, ol {
        margin: 0px !important;
      }

      .ql-editor h1, .ql-editor h2, .ql-editor h3, .ql-editor h4, .ql-editor h5, .ql-editor h6, .ql-editor p, .ql-editor ul, .ql-editor ol {
        margin: 0px !important;
      }

      .ql-editor h2, h1, h2 {
        font-weight: normal;
      }

      .ql-editor h1, h1 {
        font-size: 36px;
        line-height: 48px;
      }

      .ql-editor h2, h2 {
        font-size: 45px;
        line-height: 40px;
      }

      .ql-editor h3, h3 {
        font-size: 26px;
        line-height: 45px;
      }

      .ql-editor h4, h4 {
        font-size: 22px;
        line-height: 36px;
      }

      .ql-editor h5, h5 {
        font-size: 18px;
        line-height: 30px;
      }

      .ql-editor h6, h6 {
        font-size: 16px;
        line-height: 25px;
      }

      .ql-editor p, p {
        font-size: 16px;
        line-height: 30px;
      }

      a {
        cursor: pointer;
        font-size: 16px;
        text-decoration: none;
      }

      ul, ol, .ql-editor ul, .ql-editor ol {
        padding-left: 0;
        margin-left: 0;
      }

      .ql-editor li, li {
        font-size: 16px;
      }

      .ql-editor a {
        color: #039be5;
      }



      .center-align {
        text-align: center;
      }
      #landingpage-view .preloader {
        min-height: 100vh;
        display: flex;
        align-items: center;
      }
      #landingpage-view .preloader > .ui-progress-circular {
        margin: auto;
      }
      #landingpage-view .create-form {
        padding: 20px;
        margin-top: 50px;
      }
      #landingpage-view form > div {
        margin-bottom: 20px;
      }

      .ui-textbox {
        margin-bottom: 0;
      }
      #template-content {
        padding: 30px 0;
        width: 100%;
        min-height: 100vh;
        align-items: center;
        display: flex;
        padding: 15px;
        box-sizing: border-box;
      }
      #template-content .template-content-elements {
        display: flex;
        flex-wrap: wrap;
        max-width: 980px;
        width: 100%;
        margin: 0 auto;
      }
      #template-content .template-content-elements > * {
        width: 100%;
        margin-bottom: 5px;
      }
      #template-content .template-content-elements .template-element-media {
        align-self: center;
      }
      #template-content .template-content-elements .template-element-media img {
        max-width: 90%;
      }
      #template-content .template-content-elements .template-element-title .ui-textbox .ui-textbox__label {
        color: inherit;
      }
      #template-content .template-content-elements .template-element-title .ui-textbox .ui-textbox__label textarea {
        color: inherit;
      }
      #template-content .template-content-elements .template-element-title textarea {
        font-size: 26px;
        text-align: center;
      }
      #template-content .leadgen-form-wrap {
        background-color: white;
        box-shadow: 0 0 10px;
        max-width: 500px;
        margin: 0 auto;
        width: 96%;
      }
      @media only screen and (max-width: 600px) {
        #template-content .template-content-elements > div {
          width: 100% !important;
        }
        #template-content .template-element-mid-content > div {
          width: 100% !important;
        }
      }
      h6 {
          font-size: 16px;
      }
      .lf-form-wrapper {
        flex-basis: 100%;
      }

      .is-danger {
        color: red;
      }

      .progress {
        margin: 0px;
      }

    </style>
    <!-- API BASE URL -->
    <script>window.apiBaseUrl = "{{ $utilService->config('leadgen.api_url') . '/api/' }}" </script>
    <script>window.googleIrecaptchaSiteKeyPagesDomain = "{{ $utilService->config('leadgen.google_irecaptcha_site_key_pages_domain') }}" </script>
    {{ $lpService->pageScripts($page, 'before_head_closing') }}
  </head>
<body>
  {{ $lpService->pageScripts($page, 'after_body_opening') }}
  <div id="app">
    <div id="landingpage-view">
        <landingpage-generator pagejson="{{ json_encode($page->toArray()) }}" page-id="{{ $page->id }}"></landingpage-generator>
    </div>
  </div>
  <script src="https://www.google.com/recaptcha/api.js" type="text/javascript"></script>
  <script src="{{ asset('js/app.js') }}"></script>
  {{ $lpService->pageScripts($page, 'before_body_closing') }}
</body>
</html>
