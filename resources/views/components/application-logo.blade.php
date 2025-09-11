@props(['class' => ''])

{{-- resources/views/components/application-logo.blade.php --}}
<img
  src="{{ asset('logo.svg') }}"
  alt="{{ config('app.name', 'App') }}"
  height="32"
  {{ $attributes->merge(['class' => 'block w-auto']) }}
/>

