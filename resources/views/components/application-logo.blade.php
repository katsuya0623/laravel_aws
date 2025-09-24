@php $src = asset('images/logo.svg'); @endphp
@if (file_exists(public_path('images/logo.svg')))
  <img 
    src="{{ $src }}" 
    alt="{{ config('app.name','nibi') }}" 
    class="{{ $attributes->get('class','h-7 w-auto') }}"
  >
@else
  <span class="{{ $attributes->get('class','text-lg font-bold') }}">
    {{ config('app.name','nibi') }}
  </span>
@endif
