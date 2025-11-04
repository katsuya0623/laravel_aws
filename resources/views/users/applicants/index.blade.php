{{-- 応募者一覧（企業側）: Breeze共通ヘッダーに見出しを出す版 --}}
<x-app-layout>
  {{-- ページタイトル（ブラウザタブ用） --}}
  @section('title', '応募者一覧')

  {{-- 共通ヘッダー帯の中に表示する見出し --}}
  <x-slot name="header">
    <div class="flex flex-col gap-1">
      <h1 class="font-semibold text-xl leading-tight">応募者一覧</h1>
      <p class="text-sm text-gray-500">あなたの求人に対する応募が表示されます。</p>
    </div>
  </x-slot>

  <div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      {{-- フィルター --}}
      <form method="GET" class="card bg-base-100 border border-base-200 shadow-sm mb-6">
        <div class="card-body grid gap-4 sm:grid-cols-3">
          <div>
            <label class="label py-0">
              <span class="label-text text-xs text-base-content/60">求人で絞り込み</span>
            </label>
            <select name="job_id" class="select select-bordered w-full">
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
              <label class="label py-0">
                <span class="label-text text-xs text-base-content/60">ステータス</span>
              </label>
              <select name="status" class="select select-bordered w-full">
                @foreach($statusOptions as $val=>$label)
                  <option value="{{ $val }}" @selected((string)($status ?? '') === (string)$val)>{{ $label }}</option>
                @endforeach
              </select>
            </div>
          @endif

          <div>
            <label class="label py-0">
              <span class="label-text text-xs text-base-content/60">キーワード（氏名 / メール / 電話）</span>
            </label>
            <input
              type="text"
              name="q"
              value="{{ $keyword ?? '' }}"
              class="input input-bordered w-full"
              placeholder="例）山田 / example@nibi.co.jp"
            >
          </div>

          <div class="sm:col-span-3">
            <div class="flex items-center gap-3">
              <button class="btn btn-primary">検索</button>
              <a href="{{ route('users.applicants.index') }}" class="btn btn-ghost btn-sm">リセット</a>
            </div>
          </div>
        </div>
      </form>

      {{-- 一覧テーブル --}}
      <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th>応募日時</th>
                <th>応募者</th>
                <th>メール / 電話</th>
                <th>対象求人</th>
                @if(!empty($statusOptions ?? []))<th>ステータス</th>@endif
                <th class="w-24">操作</th>
              </tr>
            </thead>
            <tbody>
              @forelse(($applications ?? []) as $a)
                @php
                  $job  = $a->job ?? null;
                  $name = $a->name ?? ($a->full_name ?? ($a->applicant_name ?? '（氏名未登録）'));
                  $mail = $a->email ?? '';
                  $tel  = $a->tel ?? ($a->phone ?? '');
                  $statusText = $a->status ?? '';
                  $badgeClass = [
                    'pending'   => 'badge-warning',
                    'reviewing' => 'badge-info',
                    'passed'    => 'badge-success',
                    'rejected'  => 'badge-error',
                  ][$statusText] ?? 'badge-ghost';
                @endphp
                <tr>
                  <td class="align-top">#{{ $a->id }}</td>
                  <td class="align-top">{{ optional($a->created_at)->format('Y/m/d H:i') ?? '—' }}</td>
                  <td class="align-top font-medium text-base-content">{{ $name }}</td>
                  <td class="align-top">
                    @if($mail)
                      <a class="link" href="mailto:{{ $mail }}">{{ $mail }}</a>
                    @endif
                    @if($mail && $tel) <span class="opacity-40 mx-1">/</span> @endif
                    @if($tel)
                      <a class="link" href="tel:{{ $tel }}">{{ $tel }}</a>
                    @endif
                  </td>
                  <td class="align-top">
                    @if($job)
                      [#{{ $job->id }}] {{ $job->title ?? '（タイトル未設定）' }}
                    @else
                      —
                    @endif
                  </td>
                  @if(!empty($statusOptions ?? []))
                    <td class="align-top">
                      <span class="badge {{ $badgeClass }}">{{ $statusText !== '' ? $statusText : '—' }}</span>
                    </td>
                  @endif
                  <td class="align-top">
                    <a href="{{ route('users.applicants.show', $a->id) }}" class="btn btn-ghost btn-xs">詳細</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7">
                    <div class="p-10 text-center text-base-content/60">応募はまだありません。</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-footer border-t bg-base-200/30">
          {{ ($applications ?? null)?->links() }}
        </div>
      </div>
    </div>
  </div>
</x-app-layout>
