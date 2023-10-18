@component('mail::layout')
    {{-- Header --}}
    @slot('header')
      @if(empty($whitelabel) || !$whitelabel->enabled)
        @component('mail::header', ['url' => config('app.url')])
            {{ config('app.name') }} App
        @endcomponent
      @endif
    @endslot

    {{-- Body --}}
    {{ $slot }}

    {{-- Subcopy --}}
    @isset($subcopy)
        @slot('subcopy')
            @component('mail::subcopy')
                {{ $subcopy }}
            @endcomponent
        @endslot
    @endisset

    {{-- Footer --}}
    @slot('footer')
      @if(empty($whitelabel) || !$whitelabel->enabled)
        @component('mail::footer')
            © {{ date('Y') }} {{ config('app.name') }} App. @lang('All rights reserved.')
        @endcomponent
      @endif
    @endslot
@endcomponent
