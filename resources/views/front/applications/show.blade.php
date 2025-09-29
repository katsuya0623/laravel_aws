@extends('layouts.front')
@section('title', '応募詳細')

@section('content')
<x-front.page-header title="応募詳細" subtitle="応募の状況と応募先を確認できます" />

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 my-8">
  <div class="grid gap-6 md:grid-cols-3">

    {{-- 左：応募情報 --}}
    <section class="md:col-span-2 bg-white border rounded-lg p-6">
      <h2 class="text-lg font-semibold mb-4">応募情報</h2>

      @php
        $labelMap = $statusLabels ?? [];
        $statusKey = $app->status ?? 'applied';
        $label = $labelMap[$statusKey] ?? $statusKey ?? '—';
        $badge = [
          'applied'   => 'bg-gray-100 text-gray-700 border-gray-300',
          'reviewing' => 'bg-blue-50 text-blue-700 border-blue-200',
          'interview' => 'bg-amber-50 text-amber-700 border-amber-200',
          'offer'     => 'bg-green-50 text-green-700 border-green-200',
          'rejected'  => 'bg-rose-50 text-rose-700 border-rose-200',
        ][$statusKey] ?? 'bg-gray-100 text-gray-700 border-gray-300';
      @endphp

      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
        <div>
          <dt class="text-sm text-gray-500">応募日時</dt>
          <dd class="mt-1">{{ optional($app->created_at)->format('Y/m/d H:i') }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">ステータス</dt>
          <dd class="mt-1">
            <span class="inline-flex items-center rounded-md border px-2 py-0.5 text-sm {{ $badge }}">
              {{ $label }}
            </span>
          </dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">氏名</dt>
          <dd class="mt-1">{{ $app->name ?? '—' }}</dd>
        </div>
        <div>
          <dt class="text-sm text-gray-500">メール</dt>
          <dd class="mt-1">{{ $app->email ?? '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-sm text-gray-500">メッセージ</dt>
          <dd class="mt-1 whitespace-pre-line">{{ $app->message ?? '—' }}</dd>
        </div>
      </dl>
    </section>

    {{-- 右：応募先求人 --}}
    <aside class="bg-white border rounded-lg p-6">
      <h3 class="text-sm font-semibold text-gray-500 mb-2">応募先の求人</h3>
      <div class="space-y-1">
        <div class="text-base font-medium">
          {{ optional($app->job)->title ?? '(求人が見つかりません)' }}
        </div>

        @if(optional($app->job)->location)
          <div class="text-sm text-gray-500">勤務地：{{ $app->job->location }}</div>
        @endif
        @php
          $min = optional($app->job)->salary_min;
          $max = optional($app->job)->salary_max;
          $ccy = optional($app->job)->salary_currency;
          $unit= optional($app->job)->salary_unit;
        @endphp
        @if($min || $max)
          <div class="text-sm text-gray-500">
            給与：{{ $min ? number_format($min) : '—' }} 〜 {{ $max ? number_format($max) : '—' }} {{ $ccy }} / {{ $unit }}
          </div>
        @endif

        @if(optional($app->job)->slug || $app->job_id)
          <a href="{{ route('front.jobs.show', optional($app->job)->slug ?? $app->job_id) }}"
             class="inline-flex items-center rounded-md border border-indigo-600 text-indigo-600 px-3 py-1.5 text-sm hover:bg-indigo-50 mt-3">
            求人詳細を見る
          </a>
        @endif
      </div>
    </aside>
  </div>

  {{-- 進捗タイムライン（簡易） --}}
  <section class="bg-white border rounded-lg p-6 mt-6">
    <h2 class="text-lg font-semibold mb-4">進捗</h2>
    <ol class="relative border-s pl-6 space-y-4">
      <li>
        <div class="absolute -left-1.5 top-1.5 h-3 w-3 rounded-full bg-gray-400"></div>
        <time class="text-sm text-gray-500">{{ optional($app->created_at)->format('Y/m/d H:i') }}</time>
        <p class="mt-1">応募を受け付けました。</p>
      </li>
      @if($app->status && $app->status !== 'applied')
      <li>
        <div class="absolute -left-1.5 top-1.5 h-3 w-3 rounded-full bg-indigo-500"></div>
        <time class="text-sm text-gray-500">{{ optional($app->updated_at)->format('Y/m/d H:i') }}</time>
        <p class="mt-1">現在のステータス：{{ $label }}</p>
      </li>
      @endif
    </ol>
  </section>

  <div class="mt-8">
    <a href="{{ route('mypage.applications.index') }}"
       class="inline-flex items-center rounded-md border bg-white px-4 py-2 text-sm hover:bg-gray-50">
      一覧に戻る
    </a>
  </div>
</div>
@endsection
