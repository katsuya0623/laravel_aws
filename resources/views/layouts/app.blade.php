{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- ビルド済みの追加スタイル（必要なものだけ）。重複は削除 --}}
    <link rel="stylesheet" href="/build/tw.css?v=1756707288">
    <link rel="stylesheet" href="/build/override.css?v=1756708119">

    {{-- ページごとに <head> に追加したいとき用 --}}
    @stack('head')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        {{-- Breeze のナビゲーション（ログイン/ユーザー名など） --}}
        @include('layouts.navigation')

        {{-- Page Heading --}}
        @isset($header)
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        {{-- Page Content: $slot 優先。無ければ @yield('content') を使用 --}}
        <main>
            @if (trim($slot ?? '') !== '')
                {{ $slot }}
            @else
                @yield('content')
            @endif
        </main>
    </div>

    {{-- ページ末尾に追加したいスクリプト用 --}}
    @stack('scripts')
</body>
</html>
