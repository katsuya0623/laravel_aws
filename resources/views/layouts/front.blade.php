<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title','Blog')</title>
  @vite(['resources/css/app.css','resources/js/app.js']) {{-- Breeze/Tailwind想定 --}}
</head>
<body class="bg-gray-50 text-gray-900">
  <header class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <a href="{{ route('home') }}" class="font-bold text-xl">nibi Blog</a>

      {{-- ヘッダーにカテゴリ一覧 --}}
      @php($allCategories = \App\Models\Category::orderBy('name')->get())
      <nav class="hidden md:flex gap-4">
        @foreach($allCategories as $cat)
          <a class="text-sm hover:underline" href="{{ route('front.categories.show',$cat->slug) }}">{{ $cat->name }}</a>
        @endforeach
      </nav>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    @yield('content')
  </main>
</body>
</html>
