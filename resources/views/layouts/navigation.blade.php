{{-- resources/views/layouts/navigation.blade.php --}}
<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
  @php
    // 1) どの領域か（管理 or フロント）
    $isAdminArea = request()->routeIs('admin.*') || request()->is('admin/*');

    // 2) 現在ログインユーザー（領域優先で表示）
    $adminUser = auth('admin')->user();
    $webUser   = auth('web')->user();
    $current   = $isAdminArea ? ($adminUser ?? $webUser) : ($webUser ?? $adminUser);

    // 3) 役割
    $isAdmin = $current instanceof \App\Models\Admin;
    $role    = $isAdmin ? 'admin' : ($webUser ? \App\Support\RoleResolver::resolve($webUser) : null);

    // 4) ダッシュボードURL（領域に合わせる）
    $roleDashUrl = function () use ($isAdminArea) {
      if ($isAdminArea && \Illuminate\Support\Facades\Route::has('admin.dashboard')) {
        return route('admin.dashboard');
      }
      return \Illuminate\Support\Facades\Route::has('dashboard')
        ? route('dashboard')
        : url('/dashboard');
    };

    // 5) ログアウト先（admin/web で切替）
    $logoutRoute = ($isAdminArea && \Illuminate\Support\Facades\Route::has('admin.logout'))
      ? route('admin.logout')
      : route('logout');
  @endphp

  <!-- Primary Navigation Menu -->
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16 gap-4">
      <!-- 左：ロゴ + Dashboard + グロナビ -->
      <div class="flex items-center gap-6 min-w-0 flex-1">
        <!-- Logo -->
        <div class="shrink-0 flex items-center">
          <a href="{{ $roleDashUrl() }}" class="inline-flex items-center">
            <x-application-logo class="block" />
          </a>
        </div>

        <!-- Dashboard タブ -->
        <div class="hidden sm:flex">
          <x-nav-link
            :href="$roleDashUrl()"
            :active="request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->is('admin/dashboard')"
          >{{ __('Dashboard') }}</x-nav-link>
        </div>

        {{-- Dashboard 横のグロナビ（PC表示・役割別） --}}
        @if($current)
          @php
            $is     = fn($p) => request()->is(ltrim($p,'/'));
            $link   = 'px-2.5 py-1.5 rounded-md text-sm font-medium text-gray-800 hover:bg-gray-100 whitespace-nowrap';
            $active = 'bg-gray-100 text-gray-900';
            // $isAdmin / $role は先頭の計算結果をそのまま使う
          @endphp

          <div class="hidden sm:flex items-center overflow-x-auto min-w-0">
            <ul class="flex items-center gap-1 min-w-max">
              {{-- 管理者メニュー --}}
              @if($isAdmin)
                <li><a href="{{ route('admin.posts.index') }}"        class="{{ $link }} {{ $is('admin/posts*') ? $active : '' }}">記事一覧</a></li>
                <li><a href="{{ route('admin.users.index') }}"        class="{{ $link }} {{ $is('admin/users*') ? $active : '' }}">ユーザー管理</a></li>
                <li><a href="{{ route('admin.companies.index') }}"    class="{{ $link }} {{ $is('admin/companies*') ? $active : '' }}">企業一覧</a></li>
                <li><a href="{{ route('admin.jobs.index') }}"         class="{{ $link }} {{ $is('admin/recruit_jobs*') || $is('admin/jobs*') ? $active : '' }}">求人一覧</a></li>
                <li><a href="{{ route('admin.applications.index') }}" class="{{ $link }} {{ $is('admin/applications*') ? $active : '' }}">応募一覧</a></li>

              {{-- 企業ユーザー --}}
              @elseif($role === 'company')
                <li><a href="{{ url('/posts') }}"                             class="{{ $link }} {{ $is('posts*') ? $active : '' }}">投稿一覧（フロント）</a></li>
                <li><a href="{{ route('front.jobs.index') }}"                 class="{{ $link }} {{ $is('recruit_jobs') ? $active : '' }}">求人一覧</a></li>
                <li><a href="{{ route('user.company.edit') }}"                class="{{ $link }} {{ $is('company*') ? $active : '' }}">企業情報</a></li>
                <li><a href="{{ route('users.applicants.index') }}"           class="{{ $link }} {{ $is('users/applicants*') ? $active : '' }}">応募者一覧（企業）</a></li>
                <li><a href="{{ route('users.sponsored_articles.index') }}"   class="{{ $link }} {{ $is('users/sponsored-articles*') ? $active : '' }}">スポンサー記事一覧</a></li>

              {{-- エンドユーザー --}}
              @elseif($role === 'enduser')
                <li><a href="{{ url('/posts') }}"                      class="{{ $link }} {{ $is('posts*') ? $active : '' }}">投稿一覧（フロント）</a></li>
                <li><a href="{{ route('front.jobs.index') }}"          class="{{ $link }} {{ $is('recruit_jobs') ? $active : '' }}">求人一覧</a></li>
                <li><a href="{{ route('profile.edit') }}"              class="{{ $link }} {{ $is('profile*') ? $active : '' }}">プロフィール</a></li>
                <li><a href="{{ route('mypage.applications.index') }}" class="{{ $link }} {{ $is('mypage/applications*') ? $active : '' }}">応募履歴</a></li>
                <li><a href="{{ route('mypage.favorites.index') }}"    class="{{ $link }} {{ $is('mypage/favorite*') ? $active : '' }}">お気に入り</a></li>
              @endif
            </ul>
          </div>
        @endif
      </div>

      <!-- 右：アカウント -->
      <div class="hidden sm:flex sm:items-center sm:ms-6 flex-shrink-0">
        <x-dropdown align="right" width="48">
          <x-slot name="trigger">
            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
              <div>{{ $current->name ?? 'Account' }}</div>
              <div class="ms-1">
                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>
          </x-slot>

          <x-slot name="content">
            @unless($isAdmin)
              <x-dropdown-link :href="route('profile.edit')">
                {{ __('Profile') }}
              </x-dropdown-link>
            @endunless

            <!-- Authentication -->
            <form method="POST" action="{{ $logoutRoute }}">
              @csrf
              <x-dropdown-link :href="$logoutRoute"
                onclick="event.preventDefault(); this.closest('form').submit();">
                {{ __('Log Out') }}
              </x-dropdown-link>
            </form>
          </x-slot>
        </x-dropdown>
      </div>

      <!-- Hamburger（SP） -->
      <div class="-me-2 flex items-center sm:hidden">
        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none transition">
          <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
            <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Responsive Navigation Menu（SP展開時） -->
  <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
    <div class="pt-2 pb-3 space-y-1">
      <x-responsive-nav-link
        :href="$roleDashUrl()"
        :active="request()->routeIs('dashboard') || request()->routeIs('admin.dashboard') || request()->is('admin/dashboard')"
      >{{ __('Dashboard') }}</x-responsive-nav-link>
    </div>

    @if($current)
      @php
        $is    = fn($p) => request()->is(ltrim($p,'/'));
        $rItem = 'block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100';
        $on    = 'bg-gray-100 text-gray-900';
      @endphp

      <div class="pt-2 pb-3 border-t border-gray-200">
        @if($isAdmin)
          <a href="{{ route('admin.posts.index') }}"        class="{{ $rItem }} {{ $is('admin/posts*') ? $on : '' }}">記事一覧</a>
          <a href="{{ route('admin.users.index') }}"        class="{{ $rItem }} {{ $is('admin/users*') ? $on : '' }}">ユーザー管理</a>
          <a href="{{ route('admin.companies.index') }}"    class="{{ $rItem }} {{ $is('admin/companies*') ? $on : '' }}">企業一覧</a>
          <a href="{{ route('admin.jobs.index') }}"         class="{{ $rItem }} {{ $is('admin/recruit_jobs*') || $is('admin/jobs*') ? $on : '' }}">求人一覧</a>
          <a href="{{ route('admin.applications.index') }}" class="{{ $rItem }} {{ $is('admin/applications*') ? $on : '' }}">応募一覧</a>

        @elseif($role === 'company')
          <a href="{{ url('/posts') }}"                           class="{{ $rItem }} {{ $is('posts*') ? $on : '' }}">投稿一覧（フロント）</a>
          <a href="{{ route('front.jobs.index') }}"               class="{{ $rItem }} {{ $is('recruit_jobs') ? $on : '' }}">求人一覧</a>
          <a href="{{ route('user.company.edit') }}"              class="{{ $rItem }} {{ $is('company*') ? $on : '' }}">企業情報</a>
          <a href="{{ route('users.applicants.index') }}"         class="{{ $rItem }} {{ $is('users/applicants*') ? $on : '' }}">応募者一覧（企業）</a>
          <a href="{{ route('users.sponsored_articles.index') }}" class="{{ $rItem }} {{ $is('users/sponsored-articles*') ? $on : '' }}">スポンサー記事一覧</a>

        @elseif($role === 'enduser')
          <a href="{{ url('/posts') }}"                      class="{{ $rItem }} {{ $is('posts*') ? $on : '' }}">投稿一覧（フロント）</a>
          <a href="{{ route('front.jobs.index') }}"          class="{{ $rItem }} {{ $is('recruit_jobs') ? $on : '' }}">求人一覧</a>
          <a href="{{ route('profile.edit') }}"              class="{{ $rItem }} {{ $is('profile*') ? $on : '' }}">プロフィール</a>
          <a href="{{ route('mypage.applications.index') }}" class="{{ $rItem }} {{ $is('mypage/applications*') ? $on : '' }}">応募履歴</a>
          <a href="{{ route('mypage.favorites.index') }}"    class="{{ $rItem }} {{ $is('mypage/favorite*') ? $on : '' }}">お気に入り</a>
        @endif
      </div>
    @endif

    <!-- Responsive Settings Options -->
    <div class="pt-4 pb-1 border-t border-gray-200">
      <div class="px-4">
        <div class="font-medium text-base text-gray-800">{{ $current->name ?? '' }}</div>
        <div class="font-medium text-sm text-gray-500">{{ $current->email ?? '' }}</div>
      </div>

      <div class="mt-3 space-y-1">
        @unless($isAdmin)
          <x-responsive-nav-link :href="route('profile.edit')">
            {{ __('Profile') }}
          </x-responsive-nav-link>
        @endunless

        <form method="POST" action="{{ $logoutRoute }}">
          @csrf
          <x-responsive-nav-link :href="$logoutRoute"
            onclick="event.preventDefault(); this.closest('form').submit();">
            {{ __('Log Out') }}
          </x-responsive-nav-link>
        </form>
      </div>
    </div>
  </div>
</nav>
