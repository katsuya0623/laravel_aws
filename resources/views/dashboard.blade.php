<x-app-layout>
  <x-slot name="header">
    <div class="text-center">
      <h2 class="font-semibold text-2xl tracking-tight">ダッシュボード</h2>
      <p class="text-gray-500 text-sm mt-1">ここから各機能へ移動できます。</p>
    </div>
  </x-slot>

  @php
    // 共通スタイル
    $btn = 'group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-7
            shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200
            hover:bg-indigo-50/40 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';
    $iconWrap = 'grid place-items-center h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600';
    $arrow = 'ml-auto text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600';
    $title = 'text-base font-semibold leading-tight';
    $desc  = 'text-sm text-gray-500';

    // ===== ロール/ガード判定（web を最優先、なければ admin）=====
    $webUser     = auth('web')->user();
    $role        = $webUser->role ?? null;                 // 'enduser' or 'company' を想定
    $isFront     = $webUser && in_array($role, ['enduser','company'], true);
    $isCompany   = $isFront && $role === 'company';
    $isEnduser   = $isFront && $role === 'enduser';
    $isAdminOnly = !$isFront && auth('admin')->check();    // webが居ない状態で admin のみ

    // 求人一覧リンク（会社/一般→/recruit_jobs、adminのみ→/admin/recruit_jobs）
    $jobsIndexUrl   = $isAdminOnly ? route('admin.jobs.index') : route('front.jobs.index');
    $jobsIndexTitle = $isAdminOnly ? '求人一覧（管理）' : '求人一覧';
    $jobsIndexDesc  = $isAdminOnly ? '掲載中の求人を管理' : '公開中の求人一覧';
  @endphp

  <div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">コンテンツ管理</h3>

      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        {{-- ===== フロント（全ロール可） ===== --}}
        <a href="{{ url('/posts') }}" class="{{ $btn }}">
          <span class="{{ $iconWrap }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/></svg>
          </span>
          <span>
            <div class="{{ $title }}">投稿一覧（フロント）</div>
            <div class="{{ $desc }}">サイト側に公開された記事</div>
          </span>
          <span class="{{ $arrow }}">→</span>
        </a>

        {{-- ===== 共通：求人一覧（ロールに応じてURL切替） ===== --}}
        <a href="{{ $jobsIndexUrl }}" class="{{ $btn }}">
          <span class="{{ $iconWrap }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M4 7h16v2H4zM4 11h16v2H4zM4 15h10v2H4z"/></svg>
          </span>
          <span>
            <div class="{{ $title }}">{{ $jobsIndexTitle }}</div>
            <div class="{{ $desc }}">{{ $jobsIndexDesc }}</div>
          </span>
          <span class="{{ $arrow }}">→</span>
        </a>

        {{-- ===== エンドユーザー専用 ===== --}}
        @if($isEnduser)
          <a href="{{ route('user.profile.edit') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z"/><path d="M21 20a8.94 8.94 0 0 0-9-9 8.94 8.94 0 0 0-9 9v1h18z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">プロフィール</div>
              <div class="{{ $desc }}">自己紹介・アイコン画像を登録</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('mypage.applications.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h9.75M3 6v12a2.25 2.25 0 002.25 2.25h13.5A2.25 2.25 0 0021 18V6" />
              </svg>
            </span>
            <span>
              <div class="{{ $title }}">応募履歴</div>
              <div class="{{ $desc }}">あなたの応募状況を確認</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('mypage.favorites.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}"><span class="text-lg">★</span></span>
            <span class="min-w-0">
              <div class="flex items-center gap-2">
                <div class="{{ $title }}">お気に入り</div>
                @auth('web')
                  @php($favCount = auth('web')->user()?->favorites()->count() ?? 0)
                  <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $favCount }}</span>
                @endauth
              </div>
              <div class="{{ $desc }}">求人のお気に入り一覧</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>
        @endif

        {{-- ===== 企業ユーザー（company）専用 ===== --}}
        @if($isCompany)
          <a href="{{ route('user.company.edit') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21V4a1 1 0 0 1 1-1h7v18H3zM13 21h7V8h-7v13zM6 7h2v2H6V7zm0 4h2v2H6v-2zm0 4h2v2H6v-2zm10-6h2v2h-2V9zm0 4h2v2h-2v-2z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">企業情報</div>
              <div class="{{ $desc }}">企業プロフィールの編集</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('users.applicants.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V15a4 4 0 0 1-4 4h-2.382a2 2 0 0 1-1.447-.618l-1.342-1.414a2 2 0 0 0-1.447-.618H7a4 4 0 0 1-4-4V7.5z" />
              </svg>
            </span>
            <span class="min-w-0">
              <div class="flex items-center gap-2">
                <div class="{{ $title }}">応募者一覧（企業）</div>
                @auth('web')
                  <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $pendingApplicantsCount ?? 0 }}</span>
                @endauth
              </div>
              <div class="{{ $desc }}">自社求人への応募一覧</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('users.sponsored_articles.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10z"/><path d="M7 13l5 3 5-3v7l-5-3-5 3v-7z"/>
              </svg>
            </span>
            <span>
              <div class="{{ $title }}">スポンサー記事一覧</div>
              <div class="{{ $desc }}">スポンサーの記事</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>
        @endif

        {{-- ===== 管理者のみ（webが居ない純admin時だけ表示） ===== --}}
        @if($isAdminOnly)
          <a href="{{ route('admin.posts.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 5a2 2 0 0 1 2-2h3v3H5v11h14V6h-3V3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5z"/><path d="M9 3h6v6H9z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">投稿一覧（バック）</div>
              <div class="{{ $desc }}">管理画面の投稿管理</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('admin.posts.create') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M11 4h2v16h-2z"/><path d="M4 11h16v2H4z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">新規投稿</div>
              <div class="{{ $desc }}">記事を作成する</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('admin.users.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"/><path d="M12 15c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">ユーザー管理</div>
              <div class="{{ $desc }}">アカウント一覧・編集</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('admin.users.create') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z"/><path d="M17 14H7a5 5 0 0 0-5 5v1h20v-1a5 5 0 0 0-5-5z"/><path d="M19 8h-2V6h-2V4h2V2h2v2h2v2h-2z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">ユーザー追加</div>
              <div class="{{ $desc }}">メンバーを新規作成</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ route('admin.companies.index') }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor"><path d="M3 21V4a1 1 0 0 1 1-1h7v18H3zM13 21h8V8h-8v13z"/></svg>
            </span>
            <span>
              <div class="{{ $title }}">企業一覧（管理）</div>
              <div class="{{ $desc }}">企業データの管理</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>
        @endif

      </div>
    </div>
  </div>
</x-app-layout>
