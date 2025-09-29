@extends('layouts.front')
@section('title','応募詳細')
@section('content')
  <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    @if (view()->exists('components.front.page-header'))
      <x-front.page-header title="応募詳細" subtitle="応募の内容を確認できます" />
    @else
      <h1 class="text-2xl font-bold mb-6">応募詳細</h1>
    @endif

    <div class="bg-white border rounded-lg shadow-sm p-6 space-y-3 text-sm">
      <div><span class="text-gray-500">応募日時：</span>{{ $app->created_at?->format('Y/m/d H:i') }}</div>
      <div><span class="text-gray-500">求人：</span>{{ $app->job->title ?? '-' }}</div>
      <div><span class="text-gray-500">会社：</span>{{ $app->job->company->name ?? '-' }}</div>
      <div><span class="text-gray-500">ステータス：</span>{{ $statusLabels[$app->status] ?? $app->status ?? '—' }}</div>
      @if(!empty($app->message))
        <div><span class="text-gray-500">メッセージ：</span>
          <pre class="whitespace-pre-wrap">{{ $app->message }}</pre>
        </div>
      @endif
    </div>

    <div class="mt-6">
      <a href="{{ route('mypage.applications.index') }}" class="text-indigo-600 hover:underline">応募履歴に戻る</a>
    </div>
  </div>
@endsection
