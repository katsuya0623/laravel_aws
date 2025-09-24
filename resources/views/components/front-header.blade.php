@php
  $is = fn($p) => request()->is(ltrim($p,'/')) || request()->fullUrlIs(url($p));
  $link = 'px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100';
  $active = 'bg-gray-100';
@endphp

<header class="border-b bg-white/90 backdrop-blur sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
    <a href="{{ url('/') }}" class="flex items-center gap-2">
      <img src="{{ asset('logo.svg') }}" alt="{{ config('app.name') }}" class="h-6">
      <span class="sr-only">{{ config('app.name') }}</span>
    </a>
    <nav class="hidden md:flex items-center gap-2">
      <a href="{{ url('/') }}"        class="{{ $link }} {{ $is('/') ? $active : '' }}">HOME</a>
      <a href="{{ url('/company') }}" class="{{ $link }} {{ $is('/company*') ? $active : '' }}">企業一覧</a>
      <a href="{{ url('/jobs') }}"    class="{{ $link }} {{ $is('/jobs*') ? $active : '' }}">求人一覧</a>
      <a href="{{ url('/posts') }}"   class="{{ $link }} {{ $is('/posts*') ? $active : '' }}">お知らせ</a>
      <a href="{{ url('/contact') }}" class="{{ $link }} {{ $is('/contact') ? $active : '' }}">お問い合わせ</a>
    </nav>
    <div class="md:hidden">
      <a href="{{ url('/posts') }}" class="text-sm underline">メニュー</a>
    </div>
  </div>
</header>
