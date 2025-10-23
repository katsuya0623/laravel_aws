@extends('layouts.app')
@section('content')
  <div class="max-w-lg mx-auto py-16">
    <h1 class="text-2xl font-bold mb-4">招待の有効期限が切れています</h1>
    <p class="mb-6">お手数ですが、管理者に再送をご依頼ください。</p>
    <a href="{{ route('login') }}" class="text-blue-600 underline">ログインへ戻る</a>
  </div>
@endsection
