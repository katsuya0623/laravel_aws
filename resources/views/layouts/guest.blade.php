<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Vite -->
    @vite(['resources/css/app.css','resources/js/app.js'])

    <!-- build CSS（重複しないよう1本に） -->
    <link rel="stylesheet" href="/build/tw.css?v=1756706474">

    <!-- ログインロゴのサイズ固定 -->
    <style>
      .login-logo{height:64px!important;width:auto!important;display:block;}
      @media (min-width:640px){.login-logo{height:80px!important;}} /* sm */
      @media (min-width:768px){.login-logo{height:96px!important;}} /* md */
    </style>
</head>
<body class="antialiased bg-gray-100 dark:bg-gray-900">

    <!-- ★ 画面ど真ん中に固定（Tailwindに依存しない保険付き） -->
    <main class="px-6"
          style="min-height:100dvh;display:flex;align-items:center;justify-content:center;">
        <div class="w-full max-w-md">
            <!-- ロゴ（中央） -->
            <a href="/" class="mb-6 flex justify-center">
                <x-application-logo class="login-logo" />
            </a>

            <!-- フォームカード（中央） -->
            <div class="w-full bg-white shadow-md sm:rounded-lg p-6
                        dark:bg-white/10 dark:backdrop-blur dark:ring-1 dark:ring-white/10">
                {{ $slot }}
            </div>
        </div>
    </main>

</body>
</html>
