{{-- resources/views/mypage/applications/index.blade.php --}}
@extends('layouts.app')
@section('title','応募履歴')

@section('content')
<div class="py-8 sm:py-10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- ヘッダー（ダッシュボード準拠） --}}
    <div class="bg-base-100 border border-base-200 rounded-2xl px-6 py-5 shadow-sm mb-6">
      <h1 class="text-2xl font-semibold tracking-tight text-base-content">応募履歴</h1>
      <p class="text-sm text-base-content/60 mt-1">あなたの応募の一覧です</p>
    </div>

    @php
      /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection $apps */
      $list = $apps ?? collect();
    @endphp

    @if($list->isEmpty())
      <div class="bg-base-100 border border-base-200 rounded-xl p-8 text-center text-base-content/60">
        応募履歴はまだありません。
      </div>
    @else
      <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="table">
            <thead>
              <tr>
                <th>応募日時</th>
                <th>求人</th>
                <th>会社</th>
                <th>ステータス</th>
                <th class="w-24 text-right">詳細</th>
              </tr>
            </thead>
            <tbody>
              @forelse($list as $a)
                @php
                  $job     = $a->job ?? null;
                  $company = $job?->company ?? null;
                  $status  = $a->status ?? '';

                  $badgeClass = [
                    'pending'   => 'badge-warning',
                    'reviewing' => 'badge-info',
                    'passed'    => 'badge-success',
                    'rejected'  => 'badge-error',
                  ][$status] ?? 'badge-ghost';
                @endphp
                <tr>
                  <td class="align-top whitespace-nowrap">
                    {{ optional($a->created_at)->format('Y/m/d H:i') ?? '—' }}
                  </td>
                  <td class="align-top">{{ $job->title ?? '—' }}</td>
                  <td class="align-top">{{ $company->name ?? '—' }}</td>
                  <td class="align-top">
                    <span class="badge {{ $badgeClass }}">
                      {{ ($statusLabels[$status] ?? $status) ?: '—' }}
                    </span>
                  </td>
                  <td class="align-top text-right">
                    <a href="{{ route('mypage.applications.show', $a->id) }}" class="btn btn-ghost btn-xs">見る</a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5">
                    <div class="p-10 text-center text-base-content/60">応募履歴はまだありません。</div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-footer border-t bg-base-200/30">
          {{-- ページネーション（LengthAwarePaginator のときのみ表示） --}}
          @if(method_exists($list, 'links'))
            {{ $list->links() }}
          @endif
        </div>
      </div>
    @endif

  </div>
</div>
@endsection
