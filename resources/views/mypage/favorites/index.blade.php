{{-- resources/views/mypage/favorites/index.blade.php --}}
@extends('layouts.app')

@section('title','お気に入り一覧')

@section('content')
<div class="py-8 sm:py-10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ヘッダー（ダッシュボード準拠） --}}
    <div class="bg-base-100 border border-base-200 rounded-2xl px-6 py-5 shadow-sm mb-6">
      <h1 class="text-2xl font-semibold tracking-tight text-base-content">お気に入り一覧</h1>
      <p class="text-sm text-base-content/60 mt-1">保存した求人の一覧です</p>
    </div>

    @php
      /** @var \Illuminate\Support\Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator $favorites */
      $list = $favorites ?? collect();
      $jobUrl = function ($job) {
        return \Illuminate\Support\Facades\Route::has('front.jobs.show')
          ? route('front.jobs.show', $job->id)
          : url('/recruit_jobs/'.$job->id);
      };
    @endphp

    @if($list->isEmpty())
      <div class="bg-base-100 border border-base-200 rounded-xl p-8 text-center text-base-content/60">
        お気に入りはまだありません。
      </div>
    @else
      <ul class="grid gap-4 sm:gap-5 md:grid-cols-2">
        @foreach($list as $job)
          @php
            $company = $job->company ?? null;
          @endphp
          <li class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body gap-3">
              {{-- タイトル --}}
              <a href="{{ $jobUrl($job) }}" class="card-title text-lg leading-snug hover:underline">
                {{ $job->title ?? '（タイトル未設定）' }}
              </a>

              {{-- 会社名 --}}
              @if(!empty($company?->name))
                <div class="text-sm text-base-content/60">
                  {{ $company->name }}
                </div>
              @endif

              {{-- 下段：アクション --}}
              <div class="mt-2 flex items-center justify-between">
                <a href="{{ $jobUrl($job) }}" class="btn btn-sm btn-outline">詳細を見る</a>
                {{-- 既存のお気に入りトグル（コンポーネント） --}}
                <x-favorite-toggle :job="$job" />
              </div>
            </div>
          </li>
        @endforeach
      </ul>

      {{-- ページネーション（LengthAwarePaginator のときのみ） --}}
      @if(method_exists($list, 'links'))
        <div class="mt-6">
          {{ $list->links() }}
        </div>
      @endif
    @endif

  </div>
</div>
@endsection

@push('scripts') {{-- component側の @once を受け取る用。ここは空でOK --}} @endpush
