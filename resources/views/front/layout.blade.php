<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}"> {{-- ★ お気に入りAJAX用 --}}
  <title>@yield('title', 'nibi')</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  {{-- ▲ 元の link が壊れていたので修正（&display=swap を正しい位置に） --}}
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="/build/tw.css?v=1756707288">
  <link rel="stylesheet" href="/build/override.css?v=1756708119">

  <style>
    /* ===== 基本レイアウト ===== */
    :root{
      --c-border:#e5e7eb;
      --c-text:#111827;
      --c-sub:#6b7280;
      --c-bg:#ffffff;
      --c-muted:#f8fafc;
      --c-accent:#6d28d9; /* 紫 */
    }
    *{ box-sizing:border-box }
    html,body{ margin:0; padding:0; background:var(--c-bg); color:var(--c-text); font:14px/1.6 -apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica,Arial,"Noto Sans JP","Hiragino Kaku Gothic ProN","Yu Gothic",Meiryo,sans-serif; }
    a{ color:var(--c-accent); text-decoration:none }
    a:hover{ text-decoration:underline }

    /* ===== WordPress風 上部バー（ログインバー）===== */
    .adminbar{
      background:#1f2937; color:#fff; height:32px; display:flex; align-items:center;
      gap:14px; padding:0 12px; font-size:12px;
    }
    .adminbar a{ color:#fff; opacity:.9; }
    .adminbar a:hover{ opacity:1; text-decoration:underline }

    /* ===== ヘッダー ===== */
    header.site-header{
      border-bottom:1px solid var(--c-border);
      background:#fff;
    }
    .container{ max-width:980px; margin:0 auto; padding:0 16px; }
    .header-inner{
      display:flex; align-items:center; justify-content:space-between; height:56px; gap:16px;
    }
    .brand{ display:flex; align-items:center; gap:10px; font-weight:700; }
    .brand a{ color:inherit; text-decoration:none }
    .nav{ display:flex; gap:16px; font-size:13px; }
    .nav a{ color:#374151 }
    .nav a:hover{ color:var(--c-accent); text-decoration:none }

    /* ===== 本文 ===== */
    main.site-main{ padding:20px 0; }
    .toolbar{ display:flex; gap:12px; align-items:center; color:var(--c-sub); font-size:12px; margin-bottom:12px; }

    /* ===== フッター ===== */
    footer.site-footer{
      border-top:1px solid var(--c-border); color:var(--c-sub); text-align:center; padding:24px 0; background:#fff;
      margin-top:28px;
    }

    /* ===== ページネーションの巨大SVG対策 ===== */
    nav[role="navigation"] svg{ width:1em; height:1em; }
    nav[role="navigation"] .hidden{ display:none }
  </style>
</head>
<body>
  @php use Illuminate\Support\Facades\Route; @endphp

  {{-- WordPress っぽい上部バー（ログイン時のみ） --}}
  @auth
    <div class="adminbar">
      <a href="{{ url('/dashboard') }}">ダッシュボード</a>
      <a href="{{ url('/admin/posts') }}">投稿（管理）</a>
      <a href="{{ url('/admin/users') }}">ユーザー（管理）</a>
      <span style="opacity:.6">|</span>
      <form method="POST" action="{{ route('logout') }}" style="display:inline">
        @csrf
        <button style="background:none;border:0;color:#fff;cursor:pointer;padding:0">ログアウト</button>
      </form>
      <span style="margin-left:auto; opacity:.75;">{{ Auth::user()->name ?? 'Logged in' }}</span>
    </div>
  @else
    <div class="adminbar" style="background:#111827">
      <a href="{{ Route::has('login') ? route('login') : url('/login') }}">ログイン</a>
      @if (Route::has('register'))
        <a href="{{ route('register') }}">新規登録</a>
      @endif
    </div>
  @endauth

  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <a href="/" aria-label="nibi top">
          {{-- ロゴの実体は public/images/logo.svg --}}
          <img src="{{ asset('images/logo.svg') }}"
               alt="{{ config('app.name', 'nibi') }}"
               height="28" width="120"  {{-- おおよその幅（CLS対策） --}}
               style="height:28px;width:auto" />
        </a>
      </div>

      {{-- ルート存在チェック付きナビ --}}
      <nav class="nav">
        @php
          $urlPosts   = Route::has('front.posts.index')   ? route('front.posts.index')   : null;
          $urlCompany = Route::has('front.company.index') ? route('front.company.index') : null;
          $urlJobs    = Route::has('front.jobs.index')    ? route('front.jobs.index')    : null;
        @endphp

        @if ($urlPosts)
          <a href="{{ $urlPosts }}">記事一覧</a>
        @endif
        @if ($urlCompany)
          <a href="{{ $urlCompany }}">企業</a>
        @endif
        @if ($urlJobs)
          <a href="{{ $urlJobs }}">求人</a>
        @endif
      </nav>
    </div>
  </header>

  <main class="site-main">
    <div class="container">
      {{-- 任意のツールバー（定義されている場合のみ） --}}
      @hasSection('toolbar')
        <div class="toolbar">@yield('toolbar')</div>
      @endif

      {{-- ページ本文 --}}
      @yield('content')
    </div>
  </main>

  <footer class="site-footer">
    &copy; {{ date('Y') }}
  </footer>

  {{-- ★ コンポーネント（favorite-toggle など）の @push('scripts') をここで出力 --}}
  @stack('scripts')
</body>
</html>

<!-- LAYOUTMARK -->
