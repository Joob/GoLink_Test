
@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => url('/')])
{{ get_settings('app_title') ?? 'GoLink.co - Encryped Cloud' }}
@endcomponent
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
@component('mail::footer')
© {{ date('Y') }} {{ get_settings('app_title') ?? 'GoLink.co - Encryped Cloud' }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
