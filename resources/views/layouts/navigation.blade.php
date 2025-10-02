<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
  <!-- Primary Navigation Menu -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16 gap-4">
      <div class="flex items-center gap-6 min-w-0">
        <!-- Logo -->
        <div class="shrink-0 flex items-center">
          @php
            $rurl = function(string $name, string $fallback = '/'){
              return \Illuminate\Support\Facades\Route::has($name) ? route($name) : url($fallback);
            };
          @endphp
          <a href="{{ $rurl('dashboard','/dashboard') }}" class="inline-flex items-center">
            <x-application-logo class="block" />
          </a>
        </div>

        @php
          $is  = fn($p) => request()->is(ltrim($p,'/'));
          $tab = 'inline-flex items-center px-2.5 py-1.5 rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition';
          $on  = 'text-indigo-700 bg-indigo-50 ring-1 ring-indigo-200';

          /* ===== 求人一覧リンクの切替（web優先 → admin） ===== */
          $webUser = auth('web')->user();
          $isFront = $webUser && in_array($webUser->role ?? 'enduser', ['enduser','company'], true);
          $isAdmin = !$isFront && auth('admin')->check();

          $jobsIndex  = $isAdmin ? $rurl('admin.jobs.index','/admin/recruit_jobs')
                                 : $rurl('front.jobs.index','/recruit_jobs');
          $jobsActive = $is('admin/recruit_jobs*') || $is('recruit_jobs*') || request()->routeIs('front.jobs.*');
        @endphp

        <!-- Dashboard と同じ列に整列（PC） -->
        <div class="hidden sm:flex items-center gap-2 overflow-x-auto min-w-0">
          {{-- Dashboard --}}
          <x-nav-link :href="$rurl('dashboard','/dashboard')" :active="request()->routeIs('dashboard')">
            {{ __('Dashboard') }}
          </x-nav-link>

          <span class="h-4 w-px bg-gray-200 mx-0.5"></span>

          {{-- 管理用タブ --}}
          <a href="{{ url('/posts') }}"
             class="{{ $tab }} {{ $is('posts*') ? $on : '' }}">投稿一覧（フロント）</a>

          <a href="{{ $rurl('admin.posts.index','/admin/posts') }}"
             class="{{ $tab }} {{ $is('admin/posts*') ? $on : '' }}">投稿一覧（バック）</a>

          <a href="{{ $rurl('admin.posts.create','/admin/posts/create') }}"
             class="{{ $tab }} {{ $is('admin/posts/create') ? $on : '' }}">新規投稿</a>

          <a href="{{ $rurl('admin.users.index','/admin/users') }}"
             class="{{ $tab }} {{ $is('admin/users*') ? $on : '' }}">ユーザー管理</a>

          <a href="{{ $rurl('admin.users.create','/admin/users/create') }}"
             class="{{ $tab }} {{ $is('admin/users/create') ? $on : '' }}">ユーザー追加</a>

          <a href="{{ $rurl('profile.edit','/profile') }}"
             class="{{ $tab }} {{ $is('profile*') ? $on : '' }}">プロフィール</a>

          <a href="{{ \Illuminate\Support\Facades\Route::has('user.company.edit') ? route('user.company.edit') : url('/company') }}"
             class="{{ $tab }} {{ $is('company*') ? $on : '' }}">企業情報</a>

          <a href="{{ $rurl('admin.companies.index','/admin/companies') }}"
             class="{{ $tab }} {{ $is('admin/companies*') ? $on : '' }}">企業一覧（管理）</a>

          <a href="{{ $rurl('admin.jobs.create','/admin/recruit_jobs/create') }}"
             class="{{ $tab }} {{ $is('admin/recruit_jobs/create') ? $on : '' }}">求人投稿</a>

          {{-- ▼ 求人一覧（ロールに応じてURL切替／アクティブ判定両対応） --}}
          <a href="{{ $jobsIndex }}" class="{{ $tab }} {{ $jobsActive ? $on : '' }}">求人一覧</a>

          {{-- 応募履歴（← フロントへ の直前に移動） --}}
          <a href="{{ $rurl('mypage.applications.index','/mypage/applications') }}"
             class="{{ $tab }} {{ request()->routeIs('mypage.applications.*') ? $on : '' }}">応募履歴</a>

          <span class="h-4 w-px bg-gray-200 mx-0.5"></span>

          <a href="{{ url('/posts') }}" class="{{ $tab }}">← フロントへ</a>
        </div>
      </div>

      <!-- 右：ユーザードロップダウン -->
      <div class="hidden sm:flex sm:items-center sm:ms-6">
        <x-dropdown align="right" width="48">
          <x-slot name="trigger">
            <button
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md
                     text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
              <div>{{ Auth::user()->name }}</div>
              <div class="ms-1">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
              </div>
            </button>
          </x-slot>
          <x-slot name="content">
            <x-dropdown-link :href="$rurl('profile.edit','/profile')">
              {{ __('Profile') }}
            </x-dropdown-link>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <x-dropdown-link :href="route('logout')"
                onclick="event.preventDefault(); this.closest('form').submit();">
                {{ __('Log Out') }}
              </x-dropdown-link>
            </form>
          </x-slot>
        </x-dropdown>
      </div>

      <!-- Hamburger（SP） -->
      <div class="-me-2 flex items-center sm:hidden">
        <button @click="open = ! open"
          class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500
                 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
          <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16" />
            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden"
                  stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Responsive Navigation Menu（SP展開） -->
  <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
    <div class="pt-2 pb-3 space-y-1">
      <x-responsive-nav-link :href="$rurl('dashboard','/dashboard')" :active="request()->routeIs('dashboard')">
        {{ __('Dashboard') }}
      </x-responsive-nav-link>
    </div>

    @php
      $r  = 'block w-full text-left px-4 py-2 text-sm hover:bg-gray-100';
      $on = 'bg-indigo-50 text-indigo-700';

      /* SP も同じ切替ロジック（web優先 → admin） */
      $webUser = auth('web')->user();
      $isFront = $webUser && in_array($webUser->role ?? 'enduser', ['enduser','company'], true);
      $isAdmin = !$isFront && auth('admin')->check();

      $jobsIndex  = $isAdmin ? $rurl('admin.jobs.index','/admin/recruit_jobs')
                             : $rurl('front.jobs.index','/recruit_jobs');
      $jobsActive = $is('admin/recruit_jobs*') || $is('recruit_jobs*') || request()->routeIs('front.jobs.*');
    @endphp

    <div class="pt-2 pb-3 border-t border-gray-200">
      <a href="{{ url('/posts') }}"                                  class="{{ $r }} {{ $is('posts*') ? $on : '' }}">投稿一覧（フロント）</a>
      <a href="{{ $rurl('admin.posts.index','/admin/posts') }}"      class="{{ $r }} {{ $is('admin/posts*') ? $on : '' }}">投稿一覧（バック）</a>
      <a href="{{ $rurl('admin.posts.create','/admin/posts/create') }}" class="{{ $r }} {{ $is('admin/posts/create') ? $on : '' }}">新規投稿</a>
      <a href="{{ $rurl('admin.users.index','/admin/users') }}"      class="{{ $r }} {{ $is('admin/users*') ? $on : '' }}">ユーザー管理</a>
      <a href="{{ $rurl('admin.users.create','/admin/users/create') }}" class="{{ $r }} {{ $is('admin/users/create') ? $on : '' }}">ユーザー追加</a>
      <a href="{{ $rurl('profile.edit','/profile') }}"               class="{{ $r }} {{ $is('profile*') ? $on : '' }}">プロフィール</a>
      <a href="{{ \Illuminate\Support\Facades\Route::has('user.company.edit') ? route('user.company.edit') : url('/company') }}"
                                                                    class="{{ $r }} {{ $is('company*') ? $on : '' }}">企業情報</a>
      <a href="{{ $rurl('admin.companies.index','/admin/companies') }}" class="{{ $r }} {{ $is('admin/companies*') ? $on : '' }}">企業一覧（管理）</a>
      <a href="{{ $rurl('admin.jobs.create','/admin/recruit_jobs/create') }}" class="{{ $r }} {{ $is('admin/recruit_jobs/create') ? $on : '' }}">求人投稿</a>

      {{-- ▼ 求人一覧（ロールに応じてURL切替） --}}
      <a href="{{ $jobsIndex }}" class="{{ $r }} {{ $jobsActive ? $on : '' }}">求人一覧</a>

      {{-- 応募履歴（← フロントへ の直前に移動） --}}
      <a href="{{ $rurl('mypage.applications.index','/mypage/applications') }}"
         class="{{ $r }} {{ request()->routeIs('mypage.applications.*') ? $on : '' }}">応募履歴</a>

      <a href="{{ url('/posts') }}" class="{{ $r }}">← フロントへ</a>
    </div>

    <!-- Responsive Settings Options -->
    <div class="pt-4 pb-1 border-t border-gray-200">
      <div class="px-4">
        <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
        <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
      </div>

      <div class="mt-3 space-y-1">
        <x-responsive-nav-link :href="$rurl('profile.edit','/profile')">
          {{ __('Profile') }}
        </x-responsive-nav-link>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <x-responsive-nav-link :href="route('logout')"
              onclick="event.preventDefault(); this.closest('form').submit();">
            {{ __('Log Out') }}
          </x-responsive-nav-link>
        </form>
      </div>
    </div>
  </div>
</nav>
