@extends('layouts.app')
@section('title','応募詳細')

@section('content')
<div class="py-10">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
      <a href="{{ route('users.applicants.index') }}" class="text-sm text-indigo-600 hover:underline">&larr; 応募者一覧に戻る</a>
      <h1 class="text-2xl font-bold mt-2">応募詳細 #{{ $application->id }}</h1>
      <p class="text-gray-500 text-sm mt-1">受信日時：{{ optional($application->created_at)->format('Y/m/d H:i') }}</p>
    </div>

    <div class="grid gap-6">
      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 class="font-semibold mb-4">応募者情報</h2>
        @php
          $name = $application->name ?? ($application->full_name ?? ($application->applicant_name ?? '（氏名未登録）'));
          $email = $application->email ?? '';
          $tel = $application->tel ?? ($application->phone ?? '');
        @endphp
        <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
          <div><dt class="text-gray-500">氏名</dt><dd class="font-medium text-gray-900">{{ $name }}</dd></div>
          <div><dt class="text-gray-500">メール</dt><dd>@if($email)<a class="text-indigo-600 hover:underline" href="mailto:{{ $email }}">{{ $email }}</a>@else — @endif</dd></div>
          <div><dt class="text-gray-500">電話</dt><dd>@if($tel)<a class="text-indigo-600 hover:underline" href="tel:{{ $tel }}">{{ $tel }}</a>@else — @endif</dd></div>
          <div><dt class="text-gray-500">対象求人</dt><dd>@if($application->job)[#{{ $application->job->id }}] {{ $application->job->title ?? '（タイトル未設定）' }}@else — @endif</dd></div>

          @if(Schema::hasColumn($application->getTable(),'resume_url') && filled($application->resume_url))
            <div class="sm:col-span-2"><dt class="text-gray-500">履歴書</dt><dd><a class="text-indigo-600 hover:underline" href="{{ $application->resume_url }}" target="_blank" rel="noopener">ファイルを開く</a></dd></div>
          @endif
          @if(Schema::hasColumn($application->getTable(),'message') && filled($application->message))
            <div class="sm:col-span-2"><dt class="text-gray-500">メッセージ</dt><dd class="whitespace-pre-wrap">{{ $application->message }}</dd></div>
          @endif
        </dl>
      </div>

      <div class="bg-white border border-gray-200 rounded-xl p-5">
        <h2 class="font-semibold mb-4">対応状況</h2>
        <form method="POST" action="{{ route('users.applicants.status', $application) }}" class="grid gap-4">
          @csrf @method('PATCH')

          @php $hasStatus = Schema::hasColumn($application->getTable(),'status'); @endphp
          @if($hasStatus)
            <div>
              <label class="block text-xs text-gray-500 mb-1">ステータス</label>
              <select name="status" class="rounded-md border-gray-300">
                @foreach(($statusOptions ?? ['pending'=>'未対応','reviewing'=>'確認中','passed'=>'合格','rejected'=>'見送り']) as $val=>$label)
                  <option value="{{ $val }}" @selected(($application->status ?? '') === $val)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          @else
            <p class="text-sm text-gray-500">ステータス列が無いため更新はできません。</p>
          @endif

          @if(Schema::hasColumn($application->getTable(),'note'))
            <div>
              <label class="block text-xs text-gray-500 mb-1">メモ</label>
              <textarea name="note" rows="4" class="w-full rounded-md border-gray-300" placeholder="社内向けメモ">{{ old('note', $application->note ?? '') }}</textarea>
            </div>
          @endif

          <div>
            <button class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">更新する</button>
          </div>

          @if(session('ok'))
            <p class="text-sm text-emerald-600">{{ session('ok') }}</p>
          @endif
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
