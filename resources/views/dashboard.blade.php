{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="text-center">
      <h2 class="font-semibold text-2xl tracking-tight">ダッシュボード</h2>
      <p class="text-gray-500 text-sm mt-1">ここから各機能へ移動できます。</p>
    </div>
  </x-slot>

  @php
    // ✅ Blade内では use を使えないので、完全修正版
    // Routeファサードをそのまま参照可能（use不要）

    // ===== 共通スタイル =====
    $btn = 'group flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-7
            shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200
            hover:bg-indigo-50/40 focus:outline-none focus:ring-2 focus:ring-indigo-500/30';
    $iconWrap = 'grid place-items-center h-12 w-12 rounded-xl bg-indigo-50 text-indigo-600';
    $arrow = 'ml-auto text-gray-400 transition group-hover:translate-x-0.5 group-hover:text-gray-600';
    $title = 'text-base font-semibold leading-tight';
    $desc  = 'text-sm text-gray-500';

    // ===== 安全なURLヘルパ =====
    $r = function(array $names, $param = null, ?string $fallback = null) {
      foreach ($names as $n) {
        if (\Illuminate\Support\Facades\Route::has($n)) {
          return $param !== null ? route($n, $param) : route($n);
        }
      }
      return $fallback ?? '#';
    };

    // ===== ロール/ガード判定 =====
    $webUser     = auth('web')->user();
    $role        = $webUser->role ?? null;
    $isFront     = $webUser && in_array($role, ['enduser','company'], true);
    $isCompany   = $isFront && $role === 'company';
    $isEnduser   = $isFront && $role === 'enduser';
    $isAdminOnly = ! $isFront && auth('admin')->check();

    // ===== リンク =====
    $postsIndexUrl = $r(['front.posts.index','posts.index'], null, url('/posts'));
    $jobsFrontUrl  = $r(['front.jobs.index','jobs.index','recruit_jobs.index'], null, url('/recruit_jobs'));
    $jobsAdminUrl  = $r(['admin.jobs.index','admin.recruit_jobs.index'], null, url('/admin/recruit_jobs'));
    $jobsIndexUrl   = $isAdminOnly ? $jobsAdminUrl : $jobsFrontUrl;
    $jobsIndexTitle = $isAdminOnly ? '求人一覧（管理）' : '求人一覧';
    $jobsIndexDesc  = $isAdminOnly ? '掲載中の求人を管理' : '公開中の求人一覧';

    $userProfileUrl      = $r(['user.profile.edit','profile.edit','front.profile.edit'], null, url('/profile'));
    $userApplicationsUrl = $r(['mypage.applications.index','applications.index','front.applications.index'], null, url('/applications'));
    $userFavoritesUrl    = $r(['mypage.favorites.index','favorites.index','front.favorites.index'], null, url('/favorites'));

    $companyEditUrl       = $r(['user.company.edit','users.company.edit','company.profile.edit'], null, url('/users/company'));
    $companyApplicantsUrl = $r(['users.applicants.index','company.applicants.index'], null, url('/users/applicants'));
    $sponsoredArticlesUrl = $r(['users.sponsored_articles.index','sponsored_articles.index'], null, url('/users/sponsored-articles'));

    $adminPostsIndexUrl  = $r(['admin.posts.index'], null, url('/admin/posts'));
    $adminPostsCreateUrl = $r(['admin.posts.create'], null, url('/admin/posts/create'));
    $adminUsersIndexUrl  = $r(['admin.users.index'], null, url('/admin/users'));
    $adminUsersCreateUrl = $r(['admin.users.create'], null, url('/admin/users/create'));
    $adminCompaniesUrl   = $r(['admin.companies.index'], null, url('/admin/companies'));

    // ===== バッジ件数 =====
    $favCount = 0;
    try { if ($webUser && method_exists($webUser, 'favorites')) { $favCount = $webUser->favorites()->count(); } } catch (\Throwable $e) {}
    $pendingApplicantsCount = 0;
    try {
      if ($isCompany && method_exists($webUser, 'pendingApplicantsCount')) {
        $pendingApplicantsCount = (int) $webUser->pendingApplicantsCount();
      }
    } catch (\Throwable $e) {}
  @endphp

  {{-- ====== カード一覧 ====== --}}
  <div class="py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">コンテンツ管理</h3>

      <div class="grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        {{-- 投稿一覧 --}}
        <a href="{{ $postsIndexUrl }}" class="{{ $btn }}">
          <span class="{{ $iconWrap }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/>
            </svg>
          </span>
          <span>
            <div class="{{ $title }}">投稿一覧（フロント）</div>
            <div class="{{ $desc }}">サイト側に公開された記事</div>
          </span>
          <span class="{{ $arrow }}">→</span>
        </a>

        {{-- 求人一覧 --}}
        <a href="{{ $jobsIndexUrl }}" class="{{ $btn }}">
          <span class="{{ $iconWrap }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
              <path d="M4 7h16v2H4zM4 11h16v2H4zM4 15h10v2H4z"/>
            </svg>
          </span>
          <span>
            <div class="{{ $title }}">{{ $jobsIndexTitle }}</div>
            <div class="{{ $desc }}">{{ $jobsIndexDesc }}</div>
          </span>
          <span class="{{ $arrow }}">→</span>
        </a>

        {{-- エンドユーザー --}}
        @if($isEnduser)
          <a href="{{ $userProfileUrl }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z"/><path d="M21 20a8.94 8.94 0 0 0-9-9 8.94 8.94 0 0 0-9 9v1h18z"/>
              </svg>
            </span>
            <span>
              <div class="{{ $title }}">プロフィール</div>
              <div class="{{ $desc }}">自己紹介・アイコン画像を登録</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ $userApplicationsUrl }}" class="{{ $btn }}">
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

          <a href="{{ $userFavoritesUrl }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}"><span class="text-lg">★</span></span>
            <span>
              <div class="flex items-center gap-2">
                <div class="{{ $title }}">お気に入り</div>
                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $favCount }}</span>
              </div>
              <div class="{{ $desc }}">求人のお気に入り一覧</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>
        @endif

        {{-- 企業ユーザー --}}
        @if($isCompany)
          <a href="{{ $companyEditUrl }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 21V4a1 1 0 0 1 1-1h7v18H3zM13 21h7V8h-7v13z"/>
              </svg>
            </span>
            <span>
              <div class="{{ $title }}">企業情報</div>
              <div class="{{ $desc }}">企業プロフィールの編集</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ $companyApplicantsUrl }}" class="{{ $btn }}">
            <span class="{{ $iconWrap }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V15a4 4 0 0 1-4 4h-2.382a2 2 0 0 1-1.447-.618l-1.342-1.414a2 2 0 0 0-1.447-.618H7a4 4 0 0 1-4-4V7.5z" />
              </svg>
            </span>
            <span>
              <div class="{{ $title }}">応募者一覧（企業）</div>
              <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ $pendingApplicantsCount }}</span>
              <div class="{{ $desc }}">自社求人への応募一覧</div>
            </span>
            <span class="{{ $arrow }}">→</span>
          </a>

          <a href="{{ $sponsoredArticlesUrl }}" class="{{ $btn }}">
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

      </div>
    </div>
  </div>
</x-app-layout>
