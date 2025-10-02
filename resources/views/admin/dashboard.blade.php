@extends('layouts.app')
@section('title','ダッシュボード')

@section('content')
<div class="py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <h1 class="text-2xl font-semibold mb-2">ダッシュボード</h1>
    <p class="text-sm text-gray-500 mb-6">ここから各機能へ移動できます。</p>

    {{-- 管理者リンク群（admin ガード想定） --}}
    @php
      // 念のためのガードチェック（このページは auth:admin で守られているはずだけど二重で保険）
      $isAdmin = auth()->guard('admin')->check() || (method_exists(auth()->user() ?? null,'hasRole') && auth()->user()->hasRole('admin'));
    @endphp

    @if($isAdmin)
      <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">

        {{-- 記事一覧（フロント/バックを統合して「投稿」管理へ） --}}
        <a href="{{ route('admin.posts.index') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">
              📄
            </div>
            <div class="min-w-0">
              <h3 class="font-semibold">記事一覧</h3>
              <p class="text-sm text-gray-500">サイト内に公開された記事の管理</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- ユーザー管理 --}}
        <a href="{{ route('admin.users.index') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">
              👤
            </div>
            <div class="min-w-0">
              <h3 class="font-semibold">ユーザー管理</h3>
              <p class="text-sm text-gray-500">アカウント管理・権限</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

        {{-- 企業一覧 --}}
        <a href="{{ route('admin.companies.index') }}"
           class="group rounded-2xl border border-gray-200 bg-white p-6 shadow-sm hover:-translate-y-0.5 hover:shadow-md hover:border-indigo-200 hover:bg-indigo-50/40 transition">
          <div class="flex items-start gap-4">
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">
              🏢
            </div>
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
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">
              💼
            </div>
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
            <div class="h-12 w-12 grid place-items-center rounded-xl bg-indigo-100 text-indigo-600">
              📨
            </div>
            <div class="min-w-0">
              <h3 class="font-semibold">応募一覧</h3>
              <p class="text-sm text-gray-500">全求人の応募状況を横断表示</p>
            </div>
          </div>
          <div class="mt-3 text-right text-xs text-gray-400">→</div>
        </a>

      </div>
    @else
      {{-- 非管理者のときは何も出さない or 別UI --}}
      <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-800 p-4">
        権限がありません。
      </div>
    @endif

  </div>
</div>
@endsection
