@extends('layouts.app')
@section('title','会社担当者の割り振り')

@section('content')
<div class="max-w-5xl mx-auto py-10 space-y-8">

  <div>
    <h1 class="text-2xl font-semibold">会社担当者の割り振り</h1>
    <p class="text-gray-600 mt-1">対象会社：{{ $company->company_name ?? ('ID:'.$company->id) }}</p>
    @if(session('status'))
      <div class="mt-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">{{ session('status') }}</div>
    @endif
    @error('user_id')<div class="mt-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ $message }}</div>@enderror
    @error('user')<div class="mt-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ $message }}</div>@enderror
  </div>

  {{-- 現在の担当者 --}}
  <div class="bg-white border rounded-xl p-6">
    <h2 class="font-semibold mb-4">現在の担当者</h2>
    <div class="space-y-2">
      @forelse($assigned as $u)
        <div class="flex items-center justify-between gap-4 border rounded-lg p-3">
          <div>
            <div class="font-medium">{{ $u->name }} <span class="text-gray-500 text-sm">&lt;{{ $u->email }}&gt;</span></div>
            @if($company->user && $company->user->id === $u->id)
              <div class="text-xs text-indigo-600">代表担当者</div>
            @endif
          </div>
          <div class="flex items-center gap-2">
            <form method="post" action="{{ route('admin.companies.assign_user.primary', [$company,$u]) }}">
              @csrf @method('patch')
              <button class="px-3 py-1 border rounded hover:bg-gray-50">代表に設定</button>
            </form>
            <form method="post" action="{{ route('admin.companies.assign_user.delete', [$company,$u]) }}" onsubmit="return confirm('解除しますか？')">
              @csrf @method('delete')
              <button class="px-3 py-1 border rounded text-red-600 hover:bg-red-50">解除</button>
            </form>
          </div>
        </div>
      @empty
        <p class="text-sm text-gray-500">まだ割り振られていません。</p>
      @endforelse
    </div>
  </div>

  {{-- 既存ユーザーを割り振る --}}
  <div class="bg-white border rounded-xl p-6">
    <h2 class="font-semibold mb-4">既存ユーザーを割り振る</h2>
    <form method="post" action="{{ route('admin.companies.assign_user.post', $company) }}" class="flex flex-wrap items-end gap-3">
      @csrf
      <div class="grow min-w-[280px]">
        <label class="block text-sm mb-1">候補（company / enduser）</label>
        <select name="user_id" class="w-full border rounded px-3 py-2">
          @foreach($candidates as $c)
            <option value="{{ $c->id }}">{{ $c->email }}（{{ $c->name }} / {{ $c->role ?? 'enduser' }}）</option>
          @endforeach
        </select>
      </div>
      <button class="px-4 py-2 rounded bg-gray-800 text-white hover:bg-gray-900">割り振る</button>
    </form>
  </div>

  {{-- 新規ユーザーを作成して割り振る --}}
  <div class="bg-white border rounded-xl p-6">
    <h2 class="font-semibold mb-4">新規ユーザーを作成して割り振る</h2>
    <form method="post" action="{{ route('admin.companies.assign_user.create', $company) }}" class="grid gap-4 md:grid-cols-3">
      @csrf
      <div>
        <label class="block text-sm mb-1">名前</label>
        <input name="name" class="w-full border rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm mb-1">メールアドレス</label>
        <input type="email" name="email" class="w-full border rounded px-3 py-2" required>
      </div>
      <div>
        <label class="block text-sm mb-1">パスワード（未入力なら自動生成）</label>
        <input type="text" name="password" class="w-full border rounded px-3 py-2" placeholder="自動生成可">
      </div>
      <div class="md:col-span-3">
        <button class="px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700">作成して割り振る</button>
      </div>
    </form>
  </div>

</div>
@endsection
