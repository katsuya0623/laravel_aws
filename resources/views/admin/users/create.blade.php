<x-app-layout>
  <x-slot name="header"><h2 class="font-semibold text-xl">ユーザー追加</h2></x-slot>

  <div class="p-6">
    @if (session('status'))
      <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">
        {{ session('status') }}
      </div>
    @endif
    @if ($errors->any())
      <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
        @foreach ($errors->all() as $e) <div>{{ $e }}</div> @endforeach
      </div>
    @endif

    {{-- フォールバック：$companies が無ければここで取得（最小改修のため） --}}
    @php
      if (!isset($companies)) {
        $companies = \App\Models\CompanyProfile::orderBy('company_name')->get(['id','company_name']);
      }
    @endphp

    <form method="POST" action="{{ route('admin.users.store') }}">
      @csrf

      {{-- 既存の共通フォーム --}}
      @include('admin.users._form')

      {{-- ▼ 追加：役割 --}}
      <div class="mt-6">
        <label class="block text-sm text-gray-600 mb-1">役割</label>
        <select name="role" class="w-full border rounded px-3 py-2">
          <option value="enduser" @selected(old('role','enduser')==='enduser')>enduser</option>
          <option value="company" @selected(old('role')==='company')>company</option>
        </select>
        <p class="text-xs text-gray-500 mt-1">※ 会社に割り振る場合は自動で company に昇格します。</p>
      </div>

      {{-- ▼ 追加：初回会社割当（任意） --}}
      <div class="mt-6">
        <label class="block text-sm text-gray-600 mb-1">初回の会社割当（任意）</label>
        <select name="company_profile_id" class="w-full border rounded px-3 py-2">
          <option value="">— 割り振らない —</option>
          @foreach($companies as $c)
            <option value="{{ $c->id }}" @selected(old('company_profile_id')==$c->id)>{{ $c->company_name ?? ('ID:'.$c->id) }}</option>
          @endforeach
        </select>
        <label class="inline-flex items-center gap-2 text-sm mt-2">
          <input type="checkbox" name="set_primary" value="1" @checked(old('set_primary'))>
          代表にする（company_profiles.user_id にミラー）
        </label>
      </div>

      <x-primary-button class="mt-6">作成</x-primary-button>
    </form>
  </div>
</x-app-layout>
