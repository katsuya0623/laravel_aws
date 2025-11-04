{{-- resources/views/dashboard.blade.php --}}
<x-app-layout>
  <x-slot name="header">
    <div class="bg-base-100 border border-base-200 rounded-2xl px-6 py-5 shadow-sm">
      <h2 class="text-2xl font-semibold tracking-tight text-base-content text-center">ダッシュボード</h2>
      <p class="text-center text-base-content/60 text-sm mt-1">ここから各機能へ移動できます。</p>
    </div>
  </x-slot>

  @php
    /* ---------------- 見た目トークン（daisyUI） ---------------- */
    $card   = 'group card border border-base-200 bg-base-100 shadow-sm transition
               hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus-visible:ring ring-primary/30';
    $body   = 'card-body p-5 sm:p-6 flex items-center gap-4';
    $icon   = 'grid place-content-center w-12 h-12 rounded-xl';
    $title  = 'text-base font-semibold text-base-content';
    $desc   = 'text-sm text-base-content/60 leading-relaxed';
    $chevBtn= 'btn btn-ghost btn-circle ml-auto shrink-0 group-hover:translate-x-0.5 transition';
    $badge  = 'badge badge-sm';

    /* ---------------- 既存ロジック（変更なし） ---------------- */
    $r = function(array $names, $param = null, ?string $fallback = null) {
      foreach ($names as $n) if (\Illuminate\Support\Facades\Route::has($n)) {
        return $param !== null ? route($n, $param) : route($n);
      }
      return $fallback ?? '#';
    };

    $webUser     = auth('web')->user();
    $role        = $webUser->role ?? null;
    $isFront     = $webUser && in_array($role, ['enduser','company'], true);
    $isCompany   = $isFront && $role === 'company';
    $isEnduser   = $isFront && $role === 'enduser';
    $isAdminOnly = ! $isFront && auth('admin')->check();

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

    $favCount = 0;
    try { if ($webUser && method_exists($webUser,'favorites')) $favCount = $webUser->favorites()->count(); } catch (\Throwable $e) {}
    $pendingApplicantsCount = 0;
    try { if ($isCompany && method_exists($webUser,'pendingApplicantsCount')) $pendingApplicantsCount = (int)$webUser->pendingApplicantsCount(); } catch (\Throwable $e) {}
  @endphp

  <div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center gap-3 mb-5">
        <span class="text-xs uppercase tracking-wide text-base-content/60">コンテンツ管理</span>
        <div class="divider divider-horizontal my-0"></div>
      </div>

      {{-- auto-fit でカードが気持ちよく折り返す＆高さ揃え --}}
      <div class="grid items-stretch gap-4 sm:gap-5"
           style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">

        {{-- 投稿一覧 --}}
        <a href="{{ $postsIndexUrl }}" class="{{ $card }}">
          <div class="{{ $body }}">
            <span class="{{ $icon }} bg-primary/10 text-primary">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 6h16M4 12h16M4 18h7"/>
              </svg>
            </span>
            <div class="flex-1">
              <div class="{{ $title }}">投稿一覧（フロント）</div>
              <div class="{{ $desc }}">サイト側に公開された記事</div>
            </div>
            <button type="button" class="{{ $chevBtn }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </a>

        {{-- 求人一覧 --}}
        <a href="{{ $jobsIndexUrl }}" class="{{ $card }}">
          <div class="{{ $body }}">
            <span class="{{ $icon }} bg-secondary/10 text-secondary">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                <path d="M4 7h16v2H4zM4 11h16v2H4zM4 15h10v2H4z"/>
              </svg>
            </span>
            <div class="flex-1">
              <div class="{{ $title }}">{{ $jobsIndexTitle }}</div>
              <div class="{{ $desc }}">{{ $jobsIndexDesc }}</div>
            </div>
            <button type="button" class="{{ $chevBtn }}">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </button>
          </div>
        </a>

        {{-- ===== エンドユーザー ===== --}}
        @if($isEnduser)
          <a href="{{ $userProfileUrl }}" class="{{ $card }}">
            <div class="{{ $body }}">
              <span class="{{ $icon }} bg-accent/10 text-accent">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5z"/><path d="M21 20a8.94 8.94 0 0 0-9-9 8.94 8.94 0 0 0-9 9v1h18z"/>
                </svg>
              </span>
              <div class="flex-1">
                <div class="{{ $title }}">プロフィール</div>
                <div class="{{ $desc }}">自己紹介・アイコン画像を登録</div>
              </div>
              <button type="button" class="{{ $chevBtn }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </a>

          <a href="{{ $userApplicationsUrl }}" class="{{ $card }}">
            <div class="{{ $body }}">
              <span class="{{ $icon }} bg-info/10 text-info">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5h6m-9 2h12v11.25A2.25 2.25 0 0 1 15.75 20.5H8.25A2.25 2.25 0 0 1 6 18.25V7z"/>
                </svg>
              </span>
              <div class="flex-1">
                <div class="{{ $title }}">応募履歴</div>
                <div class="{{ $desc }}">あなたの応募状況を確認</div>
              </div>
              <button type="button" class="{{ $chevBtn }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </a>

          <a href="{{ $userFavoritesUrl }}" class="{{ $card }}">
            <div class="{{ $body }}">
              <span class="{{ $icon }} bg-warning/10 text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M11.48 3.5l2.16 4.38 4.83.7-3.5 3.41.83 4.82-4.32-2.27-4.32 2.27.83-4.82-3.5-3.41 4.83-.7 2.16-4.38z"/>
                </svg>
              </span>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <div class="{{ $title }}">お気に入り</div>
                  <span class="{{ $badge }}">{{ $favCount }}</span>
                </div>
                <div class="{{ $desc }}">求人のお気に入り一覧</div>
              </div>
              <button type="button" class="{{ $chevBtn }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </a>
        @endif

        {{-- ===== 企業ユーザー ===== --}}
        @if($isCompany)
          <a href="{{ $companyEditUrl }}" class="{{ $card }}">
            <div class="{{ $body }}">
              <span class="{{ $icon }} bg-secondary/10 text-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M3 21V4a1 1 0 0 1 1-1h7v18H3zM13 21h7V8h-7v13z"/>
                </svg>
              </span>
              <div class="flex-1">
                <div class="{{ $title }}">企業情報</div>
                <div class="{{ $desc }}">企業プロフィールの編集</div>
              </div>
              <button type="button" class="{{ $chevBtn }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </a>

          <a href="{{ $companyApplicantsUrl }}" class="{{ $card }}">
            <div class="{{ $body }}">
              <span class="{{ $icon }} bg-info/10 text-info">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7.5A2.5 2.5 0 0 1 5.5 5h13A2.5 2.5 0 0 1 21 7.5V15a4 4 0 0 1-4 4h-2.382a2 2 0 0 1-1.447-.618l-1.342-1.414a2 2 0 0 0-1.447-.618H7a4 4 0 0 1-4-4V7.5z"/>
                </svg>
              </span>
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <div class="{{ $title }}">応募者一覧（企業）</div>
                  <span class="{{ $badge }}">{{ $pendingApplicantsCount }}</span>
                </div>
                <div class="{{ $desc }}">自社求人への応募一覧</div>
              </div>
              <button type="button" class="{{ $chevBtn }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </a>

          <a href="{{ $sponsoredArticlesUrl }}" class="{{ $card }}">
            <div class="{{ $body }}">
              <span class="{{ $icon }} bg-warning/10 text-warning">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M12 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10z"/><path d="M7 13l5 3 5-3v7l-5-3-5 3v-7z"/>
                </svg>
              </span>
              <div class="flex-1">
                <div class="{{ $title }}">スポンサー記事一覧</div>
                <div class="{{ $desc }}">スポンサーの記事</div>
              </div>
              <button type="button" class="{{ $chevBtn }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </button>
            </div>
          </a>
        @endif
      </div>
    </div>
  </div>

  <script>document.documentElement.dataset.theme ||= 'dousoko';</script>
</x-app-layout>
