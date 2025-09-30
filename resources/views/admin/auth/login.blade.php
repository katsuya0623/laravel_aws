@extends('layouts.admin-guest')
@section('title','管理者ログイン')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center py-10">
  <div class="w-full max-w-md bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
    <h1 class="text-xl font-semibold mb-6">管理者ログイン</h1>

    <form method="post" action="{{ route('admin.login.post') }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm text-gray-700 mb-1">メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}" required
               class="w-full rounded-md border-gray-300 focus:ring focus:ring-indigo-200">
        @error('email')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
      </div>
      <div>
        <label class="block text-sm text-gray-700 mb-1">パスワード</label>
        <input type="password" name="password" required
               class="w-full rounded-md border-gray-300 focus:ring focus:ring-indigo-200">
      </div>
      <label class="inline-flex items-center gap-2 text-sm">
        <input type="checkbox" name="remember" value="1"> 次回から自動的にログイン
      </label>
      <button class="w-full rounded-md bg-indigo-600 text-white py-2 hover:bg-indigo-700">
        ログイン
      </button>
    </form>

    <p class="text-xs text-gray-500 mt-6">
      ※ 管理者専用ページです。一般/企業の方は通常の
      <a href="{{ url('/login') }}" class="text-indigo-600 underline">ログイン</a> からお入りください。
    </p>
  </div>
</div>
@endsection
