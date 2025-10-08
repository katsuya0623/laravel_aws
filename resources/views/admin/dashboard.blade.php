@extends('layouts.app')
@section('title','ダッシュボード')

@section('content')
<div class="py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <h1 class="text-2xl font-semibold mb-2">ダッシュボード</h1>
    <p class="text-sm text-gray-500 mb-6">ここから各機能へ移動できます。</p>

    @php
      $isAdmin = auth()->guard('admin')->check() || (method_exists(auth()->user() ?? null,'hasRole') && auth()->user()->hasRole('admin'));

      // Filament 企業一覧
      $companiesUrl = Route::has('filament.admin.resources.companies.index')
          ? route('filament.admin.resources.companies.index')
          : url('/admin/companies');

      // Filament EndUser 一覧（存在しなければ旧ルート /admin/users をフォールバック）
      $endUsersUrl = Route::has('filament.admin.resources.users.index')
          ? route('filament.admin.resources.users.index')
          : (Route::has('admin.users.index')
                ? route('admin.users.index')
                : url('/admin/users'));
    @endphp

    @if($isAdmin)
      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">

        {{-- 記事作成 --}}
        <a href="{{ url('/admin/posts/create') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">✍️</div>
            <div class="min-w-0">
              <h3 class="font-semibold">記事作成</h3>
              <p class="text-sm text-gray-500">新しい記事を作成・公開</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- 記事一覧 --}}
        <a href="{{ route('admin.posts.index') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">📄</div>
            <div class="min-w-0">
              <h3 class="font-semibold">記事一覧</h3>
              <p class="text-sm text-gray-500">サイト内に公開された記事の管理</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- エンドユーザー（Filament 優先） --}}
        <a href="{{ $endUsersUrl }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">👤</div>
            <div class="min-w-0">
              <h3 class="font-semibold">エンドユーザー</h3>
              <p class="text-sm text-gray-500">エンドユーザーの管理</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- 企業一覧（Filament） --}}
        <a href="{{ $companiesUrl }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">🏢</div>
            <div class="min-w-0">
              <h3 class="font-semibold">企業一覧</h3>
              <p class="text-sm text-gray-500">企業プロフィールの管理</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- 求人一覧 --}}
        <a href="{{ route('admin.jobs.index') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">💼</div>
            <div class="min-w-0">
              <h3 class="font-semibold">求人一覧</h3>
              <p class="text-sm text-gray-500">求人の作成・編集・公開管理</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- 応募一覧 --}}
        <a href="{{ route('admin.applications.index') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">📨</div>
            <div class="min-w-0">
              <h3 class="font-semibold">応募一覧</h3>
              <p class="text-sm text-gray-500">全求人の応募状況を横断表示</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

      </div>
    @else
      <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 p-4">
        権限がありません。
      </div>
    @endif

  </div>
</div>
@endsection
