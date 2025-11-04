<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @vite(['resources/css/app.css','resources/js/app.js'])

  {{-- 一時ホットフィックス：ヘッダー内の異常スタイルをリセット --}}
  <style>
    header a, header nav a {
      background: transparent !important;
      display: inline-flex !important;
      height: auto !important;
      border-radius: .375rem; /* Tailwind rounded-md 相当 */
      padding: .5rem .75rem;  /* px-3 py-2 相当 */
      color: #4b5563;         /* text-gray-600 */
      text-decoration: none;
    }
    header a:hover { color:#111827; background:#f3f4f6; } /* hover */
    header ul { list-style: none; padding:0; margin:0; }
    /* もし daisyUI の menu / btn 等が残っていても無効化 */
    header .menu, header .btn, header .navbar { all: unset; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

  {{-- ★ ここを唯一のヘッダーとして使う。ほかのヘッダー@includeは全部削除！ --}}
  @include('components.front-header')

  <main class="max-w-7xl mx-auto px-4 py-8">
    @yield('content')
  </main>
</body>
</html>
