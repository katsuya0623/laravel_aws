<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>@yield('title','Blog')</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-dvh bg-gray-50 text-gray-800">

  {{-- ▼ログイン時だけ管理バー --}}
  @auth
    <x-admin-bar />
    {{-- 管理バーの高さぶん押し下げる --}}
    <div style="height:40px;"></div>
  @endauth
  {{-- ▲ここまで追加 --}}

  <header class="bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-6">
      <a href="{{ route('front.home') }}" class="font-bold">My Blog</a>
      <nav class="text-sm overflow-x-auto">
        <ul class="flex gap-4">
          <li><a href="{{ route('front.posts.index') }}" class="hover:underline">記事一覧</a></li>
          @isset($categories)
            @foreach($categories as $cat)
              <li>
                @if(!empty($cat->slug))
                  <a href="{{ route('front.category.show',$cat->slug) }}" class="hover:underline">{{ $cat->name }}</a>
                @else
                  <span class="text-gray-500">{{ $cat->name }}</span>
                @endif
              </li>
            @endforeach
          @endisset
        </ul>
      </nav>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">@yield('content')</main>
  <footer class="py-10 text-center text-sm text-gray-500">© {{ date('Y') }}</footer>
</body>
</html>
