{{-- resources/views/components/guest-layout.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Laravel') }}</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
  @vite(['resources/css/app.css','resources/js/app.js'])
  <link rel="stylesheet" href="/build/tw.css?v=1756706474">

  <style>
    .login-logo{height:64px!important;width:auto!important;display:block;}
    @media (min-width:640px){.login-logo{height:80px!important;}}
    @media (min-width:768px){.login-logo{height:96px!important;}}

    .guest-wrapper{
      min-height:100dvh;display:flex;flex-direction:column;justify-content:center;align-items:center;
      padding-left:clamp(16px,5vw,80px);padding-right:clamp(16px,5vw,80px);padding-top:32px;padding-bottom:32px;
    }
    .guest-logo{margin-bottom:2.5rem;text-align:center;}
    /* 念のためカード内のパディングを最優先にするクラス */
    .card-pad{padding:3rem!important;box-sizing:border-box;}
    @media (min-width:768px){ .card-pad{padding-left:4rem!important;padding-right:4rem!important;} }
  </style>
</head>
<body class="antialiased bg-gray-100 dark:bg-gray-900">

  <main class="guest-wrapper px-6 sm:px-10 lg:px-20">
    <div class="guest-logo">
      <a href="/"><x-application-logo class="login-logo mx-auto" /></a>
    </div>

    <div class="w-full max-w-3xl mx-auto bg-white shadow-lg sm:rounded-2xl
                dark:bg-white/10 dark:backdrop-blur dark:ring-1 dark:ring-white/10">

      {{-- ★ ここで上下左右に強制 3rem（md 以上は左右 4rem） --}}
      <div class="card-pad" style="padding:3rem">

        {{-- 本文の行幅も制限して中央に（読みやすさ用） --}}
        <div class="max-w-[62ch] mx-auto leading-relaxed text-[15px] sm:text-base text-gray-800">
          {{ $slot }}
        </div>

      </div>
    </div>
  </main>

</body>
</html>
