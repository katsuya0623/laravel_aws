@php
  // 現在のページ判定（routeIs とパス両方を安全にチェック）
  $is = function (string $pattern): bool {
    return request()->routeIs($pattern) || request()->is(ltrim($pattern, '/'));
  };

  // 右ナビ共通スタイル
  $link   = 'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100';
  $active = 'text-gray-900 bg-gray-100';
@endphp

<header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b">
  <div class="max-w-7xl mx-auto h-14 px-4 sm:px-6 lg:px-8 flex items-center justify-between">
    <!-- 左：ロゴ -->
    <a href="{{ url('/') }}" class="font-semibold tracking-tight">nibi Blog</a>

    <!-- 右：PCナビ -->
    <nav class="hidden md:flex items-center gap-1">
      <a href="{{ url('/') }}"        class="{{ $link }} {{ $is('/')            ? $active : '' }}">HOME</a>
      <a href="{{ url('/company') }}" class="{{ $link }} {{ $is('company*')     ? $active : '' }}">企業一覧</a>
      <a href="{{ url('/jobs') }}"    class="{{ $link }} {{ $is('jobs*')        ? $active : '' }}">求人一覧</a>
      <a href="{{ url('/posts') }}"   class="{{ $link }} {{ $is('posts*')       ? $active : '' }}">お知らせ</a>
      <a href="{{ url('/contact') }}" class="{{ $link }} {{ $is('contact')      ? $active : '' }}">お問い合わせ</a>
    </nav>

    <!-- SP：簡易メニュー（必要ならAlpine等で開閉を後付け） -->
    <a href="{{ url('/posts') }}" class="md:hidden inline-flex items-center px-3 py-2 border rounded-md text-sm">
      メニュー
    </a>
  </div>
</header>
