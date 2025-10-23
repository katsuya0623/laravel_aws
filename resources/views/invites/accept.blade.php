@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto py-12">
  <h1 class="text-2xl font-bold mb-4">企業アカウントの有効化</h1>
  <p class="mb-6">
    {{ $company_name }} へのご招待です。<br>
    招待先メール：<strong>{{ $email }}</strong>
  </p>

  <form method="POST" action="{{ route('invites.complete', ['token' => $token]) }}">
    @csrf
    <div class="mb-4">
      <label class="block text-sm font-medium mb-1">パスワード</label>
      <input type="password" name="password" class="w-full border rounded p-2" required minlength="8">
      @error('password')<div class="text-red-600 text-sm mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-6">
      <label class="block text-sm font-medium mb-1">パスワード（確認）</label>
      <input type="password" name="password_confirmation" class="w-full border rounded p-2" required minlength="8">
    </div>

    <button class="bg-indigo-600 text-white px-4 py-2 rounded">有効化する</button>
  </form>
</div>
@endsection
