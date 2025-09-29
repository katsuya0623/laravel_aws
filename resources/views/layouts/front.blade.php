<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}"><!-- ★ お気に入りAJAX用 -->
  <title>@yield('title','Blog')</title>
  @vite(['resources/css/app.css','resources/js/app.js']) {{-- Breeze/Tailwind想定 --}}
</head>
<body class="bg-gray-50 text-gray-900">
  <header class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      {{-- ロゴ / ホーム --}}
      @php
        $homeUrl = \Illuminate\Support\Facades\Route::has('home')
          ? route('home')
          : url('/');
      @endphp
      <a href="{{ $homeUrl }}" class="font-bold text-xl">nibi Blog</a>

      {{-- ヘッダーにカテゴリ一覧（存在チェックつき） --}}
      @php
        use Illuminate\Support\Facades\Schema;
        $allCategories = collect();
        try {
          if (class_exists(\App\Models\Category::class) && Schema::hasTable('categories')) {
            $allCategories = \App\Models\Category::orderBy('name')->get();
          }
        } catch (\Throwable $e) {
          $allCategories = collect();
        }

        // ルート存在チェック（単数: front.category.show）
        $catRouteExists = \Illuminate\Support\Facades\Route::has('front.category.show');
      @endphp

      <nav class="hidden md:flex gap-4">
        @foreach($allCategories as $cat)
          @if ($catRouteExists)
            <a class="text-sm hover:underline"
               href="{{ route('front.category.show', $cat->slug) }}">{{ $cat->name }}</a>
          @else
            {{-- ルート未登録時は /category/{slug} にフォールバック --}}
            <a class="text-sm hover:underline"
               href="{{ url('/category/'.$cat->slug) }}">{{ $cat->name }}</a>
          @endif
        @endforeach
      </nav>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    @yield('content')
  </main>

  {{-- ★ コンポーネント側の @push('scripts') を受け取る（favorite-toggle用） --}}
  @stack('scripts')
</body>
</html>
