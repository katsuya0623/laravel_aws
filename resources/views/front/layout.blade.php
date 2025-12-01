<!doctype html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'nibi')</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="stylesheet" href="/build/override.css?v=1756708119">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&family=Zen+Maru+Gothic:wght@400;700;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --c-border: #e5e7eb;
      --c-text: #111827;
      --c-sub: #6b7280;
      --c-bg: #ffffff;
      --c-muted: #f8fafc;
      --c-accent: #6d28d9;
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      margin: 0;
      padding: 0;
      background: var(--c-bg);
      color: var(--c-text);
      font: 14px/1.6 -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, "Noto Sans JP", "Hiragino Kaku Gothic ProN", "Yu Gothic", Meiryo, sans-serif;
    }

    header.site-header {
      border-bottom: 1px solid var(--c-border);
      background: #fff;
    }

    .container {
      max-width: 980px;
      margin: 0 auto;
      padding: 0 16px;
    }

    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 56px;
      gap: 16px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 700;
    }

    .brand a {
      color: inherit;
      text-decoration: none
    }

    .nav {
      display: flex;
      align-items: center;
      gap: 16px;
      font-size: 13px;
    }

    .nav a.link {
      color: #374151;
      text-decoration: none
    }

    .nav a.link:hover {
      color: var(--c-accent);
      text-decoration: none
    }

    .btn-register {
      background: #C23A41;
      color: #fff;
      border-radius: 9999px;
      padding: .375rem .875rem;
      font-size: .75rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
      transition: background-color .15s ease, opacity .15s ease;
      text-decoration: none;
      border: 1px solid transparent;
    }

    .btn-register:hover {
      background: #a53036;
      color: #fff;
      text-decoration: none
    }

    .btn-register:focus {
      outline: 2px solid rgba(194, 58, 65, .35);
      outline-offset: 2px
    }

    main.site-main .toolbar {
      display: flex;
      gap: 12px;
      align-items: center;
      color: var(--c-sub);
      font-size: 12px;
      margin-bottom: 12px;
    }

    /* ▼ フッター（ダーク） */
    footer.site-footer {
      background: #111111;
      color: #d1d5db;
      border-top: 1px solid #27272a;
      padding: 48px 0;
    }

    .site-footer .footer-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 32px;
    }

    @media (min-width:1024px) {
      .site-footer .footer-grid {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 28px;
      }

      .site-footer .footer-left {
        flex: 0 0 540px;
        max-width: 540px;
      }
    }

    .site-footer .brand-title {
      color: #fff;
      font-weight: 900;
      font-size: 32px;
      line-height: 1;
      margin-bottom: 8px;
    }

    .site-footer .lead {
      color: #d1d5db;
      font-size: 14px;
      margin: 0 0 20px;
    }

    /* ▼ SNSボタン（白背景＋黒文字に変更） */
    .site-footer .sns-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-width: 320px;
    }

    .site-footer .sns-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      width: 100%;
      height: 44px;
      padding: 0 16px;
      border: 1px solid #1a1a1a;
      background: #ffffff;
      color: #111111;
      text-decoration: none;
      border-radius: 8px;
      transition: background .15s ease, color .15s ease, opacity .15s ease;
    }

    .site-footer .sns-btn:hover {
      background: #f3f4f6;
      color: #000000;
    }

    .site-footer svg {
      width: 20px;
      height: 20px;
    }

    .site-footer .mini-links {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 32px;
      margin-top: 24px;
      font-size: 13px;
    }

    .site-footer .mini-links a {
      color: #d1d5db;
      text-decoration: none;
    }

    .site-footer .mini-links a:hover {
      color: #ffffff;
    }

    /* 右カラム：配置指定 */
    .site-footer .right-col {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
    }

    .site-footer .cta-btn {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: #C23A41;
      color: #fff;
      text-decoration: none;
      padding: 12px 22px;
      border-radius: 9999px;
      transition: opacity .15s ease;
      margin: 0 auto;
    }

    .site-footer .cta-btn:hover {
      opacity: .9;
    }

    .site-footer .right-nav {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 40px;
      margin-top: 32px;
      font-size: 14px;
      justify-content: center;
    }

    .site-footer .right-nav a {
      color: #d1d5db;
      text-decoration: none;
    }

    .site-footer .right-nav a:hover {
      color: #fff;
    }

    .site-footer .right-col .copyright {
      margin-top: 32px;
      width: 100%;
      text-align: right;
      color: #9ca3af;
      font-size: 13px;
    }

    nav[role="navigation"] svg {
      width: 1em;
      height: 1em;
    }

    nav[role="navigation"] .hidden {
      display: none
    }

    .font-zen-maru {
      font-family: 'Zen Maru Gothic', sans-serif;
    }

    .font-noto-sans {
      font-family: 'Noto Sans JP', sans-serif;
    }

    @media (min-width:1024px) {
      .site-footer .right-col {
        position: relative;
        top: 70px;
      }

      footer.site-footer {
        padding-bottom: calc(48px + 70px);
      }
    }
  </style>
</head>


<body>
  @php use Illuminate\Support\Facades\Route; @endphp

  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <a href="/" aria-label="ドウソコ top" class="flex flex-col items-start leading-tight">
          <span class="font-noto-sans text-[11px] font-normal leading-[100%] tracking-[0] text-gray-500">
            リアルな就活、リアルな暮らし。
          </span>
          <span class="font-zen-maru text-[40px] font-black leading-[100%] tracking-[-0.1em] text-[#C23A41]" style="font-weight:900;">
            ドウソコ
          </span>
        </a>
      </div>

      {{-- 右側ナビ：記事一覧／企業／求人＋ログイン＆登録 --}}
      <nav class="nav">
        @php
        $urlPosts = Route::has('front.posts.index') ? route('front.posts.index') : null;
        $urlCompany = Route::has('front.company.index') ? route('front.company.index') : null;
        $urlJobs = Route::has('front.jobs.index') ? route('front.jobs.index') : null;
        @endphp

        @if ($urlPosts) <a class="link" href="{{ $urlPosts }}">記事一覧</a> @endif
        @if ($urlCompany) <a class="link" href="{{ $urlCompany }}">企業</a> @endif
        @if ($urlJobs) <a class="link" href="{{ $urlJobs }}">求人</a> @endif

        <span class="hidden sm:inline-block mx-2 opacity-30">|</span>

        @guest
        <a href="{{ Route::has('login') ? route('login') : url('/login') }}"
          class="px-3 py-1.5 rounded-full border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">
          ログイン
        </a>
        @if (Route::has('register'))
        <a href="{{ route('register') }}" class="ml-2 btn-register">
          求職者登録（無料）
        </a>
        @endif
        @else
        @php
        // ダッシュボードURL
        $urlDashboard = Route::has('dashboard')
        ? route('dashboard')
        : url('/dashboard');
        @endphp

        {{-- ユーザー名 → ダッシュボードへ → ログアウト の順 --}}
        <span class="text-gray-600 text-sm">{{ Auth::user()->name }}</span>

        <a href="{{ $urlDashboard }}"
          class="ml-2 px-3 py-1.5 rounded-full border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">
          ダッシュボードへ
        </a>

        <form method="POST" action="{{ route('logout') }}" class="inline">
          @csrf
          <button type="submit"
            class="ml-2 px-3 py-1.5 rounded-full border border-gray-300 text-gray-700 text-xs font-medium hover:bg-gray-50 transition">
            ログアウト
          </button>
        </form>
        @endguest
      </nav>


    </div>
  </header>

  <main class="site-main">
    <div class="container">
      @hasSection('toolbar')
      <div class="toolbar">@yield('toolbar')</div>
      @endif
      @yield('content')
    </div>
  </main>


  {{-- ▼ ダーク版のリッチフッター --}}
  <footer class="site-footer">
    <div class="container">
      @php
      $urlPosts = Route::has('front.posts.index') ? route('front.posts.index') : '#';
      $urlJobs = Route::has('front.jobs.index') ? route('front.jobs.index') : '#';
      $urlCompany = Route::has('front.company.index') ? route('front.company.index') : '#';
      $urlContact = Route::has('front.contact') ? route('front.contact') : (url('/contact') ?: '#');
      $urlOperator = url('/company') ?: '#';
      $urlPrivacy = url('/privacy') ?: '#';
      $urlForBiz = url('/for-business');
      @endphp

      <div class="footer-grid">
        <!-- 左：ブランド＆SNS -->
        <div class="footer-left">
          <div class="brand-title">ドウソコ</div>
          <p class="lead">ドウソコをフォローして最新情報をゲット！</p>

          <div class="sns-list">
            <a href="#" class="sns-btn">
              <!-- X -->
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 3h3.5L21 20.5V21h-3.5L3 3.5V3zM20.5 3H21v.5L4.5 21H3v-.5L20.5 3z" />
              </svg>
              ドウソコをフォローする
            </a>
            <a href="#" class="sns-btn">
              <!-- TikTok -->
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                <path d="M15 3c1.1 1.7 2.6 2.7 4.5 2.9V9c-1.9-.1-3.5-.8-4.5-1.8v6.4c0 3.2-2.6 5.8-5.8 5.8S3.4 16.8 3.4 13.6 6 7.8 9.2 7.8c.5 0 1 .1 1.4.2v3.2c-.4-.2-.9-.3-1.4-.3-1.6 0-2.9 1.3-2.9 2.9S7.6 16.7 9.2 16.7s2.9-1.3 2.9-2.9V3h2.9z" />
              </svg>
              ドウソコをフォローする
            </a>
          </div>

          <div class="mini-links">
            <a href="{{ $urlContact  }}">お問い合わせ</a>
            <a href="{{ $urlOperator }}">運営会社</a>
            <a href="{{ $urlPrivacy  }}">プライバシーポリシー</a>
          </div>
        </div>

        <!-- 右：企業向けCTA＆ナビ -->
        <div class="right-col">
          <a href="{{ $urlForBiz }}" class="cta-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
              <path d="M3 21V5l9-3 9 3v16h-6v-5H9v5H3zm8-7h2V9h-2v5z" />
            </svg>
            掲載希望の企業の方はこちら
          </a>

          <div class="right-nav">
            <a href="{{ $urlPosts   }}">記事一覧</a>
            <a href="{{ $urlJobs    }}">求人情報</a>
            <a href="{{ $urlCompany }}">求人企業</a>
          </div>

          <!-- 右寄せのコピーライト -->
          <div class="copyright">
            © {{ date('Y') }} DOUSOKO..
          </div>
        </div>
      </div> <!-- /.footer-grid -->

    </div> <!-- /.container -->
  </footer>

  @stack('scripts')
</body>

</html>

<!-- LAYOUTMARK -->