@extends('layouts.app') {{-- Breezeなら x-app-layout に置換OK --}}
@section('title','応募者一覧')

@section('content')
<div class="py-10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="mb-6">
      <h1 class="text-2xl font-bold">応募者一覧</h1>
      <p class="text-gray-500 text-sm mt-1">あなたの求人に対する応募が表示されます。</p>
    </div>

    {{-- フィルター --}}
    <form method="GET" class="bg-white border border-gray-200 rounded-xl p-4 mb-6 grid gap-3 sm:grid-cols-3">
      <div>
        <label class="block text-xs text-gray-500 mb-1">求人で絞り込み</label>
        <select name="job_id" class="w-full rounded-md border-gray-300">
          <option value="">すべて</option>
          @foreach(($ownedJobs ?? []) as $j)
            <option value="{{ $j->id }}" @selected((int)($jobId ?? 0) === (int)$j->id)>
              [#{{ $j->id }}] {{ $j->title ?? '（タイトル未設定）' }}
            </option>
          @endforeach
        </select>
      </div>

      @if(!empty($statusOptions ?? []))
      <div>
        <label class="block text-xs text-gray-500 mb-1">ステータス</label>
        <select name="status" class="w-full rounded-md border-gray-300">
          @foreach($statusOptions as $val=>$label)
            <option value="{{ $val }}" @selected((string)($status ?? '') === (string)$val)>{{ $label }}</option>
          @endforeach
        </select>
      </div>
      @endif

      <div>
        <label class="block text-xs text-gray-500 mb-1">キーワード（氏名 / メール / 電話）</label>
        <input type="text" name="q" value="{{ $keyword ?? '' }}" class="w-full rounded-md border-gray-300" placeholder="例）山田 / example@nibi.co.jp">
      </div>

      <div class="sm:col-span-3">
        <button class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">検索</button>
        <a href="{{ route('users.applicants.index') }}" class="ml-3 text-sm text-gray-500 hover:underline">リセット</a>
      </div>
    </form>

    {{-- 一覧テーブル --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-2 text-left text-gray-500">ID</th>
              <th class="px-4 py-2 text-left text-gray-500">応募日時</th>
              <th class="px-4 py-2 text-left text-gray-500">応募者</th>
              <th class="px-4 py-2 text-left text-gray-500">メール / 電話</th>
              <th class="px-4 py-2 text-left text-gray-500">対象求人</th>
              @if(!empty($statusOptions ?? []))<th class="px-4 py-2 text-left text-gray-500">ステータス</th>@endif
              <th class="px-4 py-2 text-left text-gray-500">操作</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            @forelse(($applications ?? []) as $a)
              @php
                $job  = $a->job ?? null;
                $name = $a->name ?? ($a->full_name ?? ($a->applicant_name ?? '（氏名未登録）'));
                $mail = $a->email ?? '';
                $tel  = $a->tel ?? ($a->phone ?? '');
                $statusText = $a->status ?? '';
              @endphp
              <tr>
                <td class="px-4 py-2 text-gray-700">#{{ $a->id }}</td>
                <td class="px-4 py-2 text-gray-700">{{ optional($a->created_at)->format('Y/m/d H:i') ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-900 font-medium">{{ $name }}</td>
                <td class="px-4 py-2 text-gray-700">
                  @if($mail)<a class="text-indigo-600 hover:underline" href="mailto:{{ $mail }}">{{ $mail }}</a>@endif
                  @if($mail && $tel) <span class="text-gray-300 mx-1">/</span> @endif
                  @if($tel)<a class="text-indigo-600 hover:underline" href="tel:{{ $tel }}">{{ $tel }}</a>@endif
                </td>
                <td class="px-4 py-2 text-gray-700">
                  @if($job)[#{{ $job->id }}] {{ $job->title ?? '（タイトル未設定）' }}@else — @endif
                </td>
                @if(!empty($statusOptions ?? []))
                <td class="px-4 py-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                    @class([
                      'bg-yellow-50 text-yellow-800' => $statusText==='pending',
                      'bg-blue-50 text-blue-800'     => $statusText==='reviewing',
                      'bg-emerald-50 text-emerald-800'=> $statusText==='passed',
                      'bg-rose-50 text-rose-800'     => $statusText==='rejected',
                      'bg-gray-100 text-gray-700'    => !in_array($statusText,['pending','reviewing','passed','rejected']),
                    ])
                  ">{{ $statusText !== '' ? $statusText : '—' }}</span>
                </td>
                @endif
                <td class="px-4 py-2">
                  <a href="{{ route('users.applicants.show', $a->id) }}" class="text-indigo-600 hover:underline text-sm">詳細</a>
                </td>
              </tr>
            @empty
              <tr>
                <td class="px-4 py-6 text-center text-gray-500" colspan="7">応募はまだありません。</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="px-4 py-3 border-t bg-gray-50">
        {{ ($applications ?? null)?->links() }}
      </div>
    </div>

  </div>
</div>
@endsection
