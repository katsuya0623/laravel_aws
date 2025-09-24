@php
  $is = fn($p) => request()->is(ltrim($p,'/')) || request()->fullUrlIs(url($p));
  $link = 'px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-100';
  $active = 'bg-gray-100';
@endphp

<header class="border-b bg-white sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between">
    <a href="{{ url('/dashboard') }}" class="flex items-center gap-2">
      <img src="{{ asset('logo.svg') }}" alt="Dashboard" class="h-6">
      <span class="text-sm text-gray-600">Dashboard</span>
    </a>
    <nav class="hidden md:flex items-center gap-2">
      <a href="{{ url('/dashboard') }}"        class="{{ $link }} {{ $is('/dashboard') ? $active : '' }}">ホーム</a>
      <a href="{{ url('/admin/posts') }}"      class="{{ $link }} {{ $is('/admin/posts*') ? $active : '' }}">投稿管理</a>
      <a href="{{ url('/admin/companies') }}"  class="{{ $link }} {{ $is('/admin/companies*') ? $active : '' }}">企業管理</a>
      <a href="{{ url('/admin/jobs') }}"       class="{{ $link }} {{ $is('/admin/jobs*') ? $active : '' }}">求人管理</a>
      <a href="{{ url('/admin/users') }}"      class="{{ $link }} {{ $is('/admin/users*') ? $active : '' }}">ユーザー</a>
      <a href="{{ url('/posts') }}"            class="{{ $link }}">← フロントへ</a>
    </nav>
    <div class="md:hidden">
      <a href="{{ url('/dashboard') }}" class="text-sm underline">メニュー</a>
    </div>
  </div>
</header>
