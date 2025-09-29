@extends('layouts.front')

@section('title','応募履歴')

@section('content')
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    {{-- ページヘッダー（posts と同じ世界観） --}}
    <x-front.page-header title="応募履歴" subtitle="あなたの応募の一覧です" />

    @if($apps->isEmpty())
      <div class="mt-6 bg-white border rounded-lg shadow-sm p-8 text-center text-gray-600">
        応募履歴はまだありません。
      </div>
    @else
      <div class="mt-6 bg-white border rounded-lg shadow-sm overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="text-left px-4 py-3 font-medium text-gray-600">応募日時</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">求人</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">会社</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">ステータス</th>
              <th class="text-right px-4 py-3 font-medium text-gray-600">詳細</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @foreach($apps as $a)
              <tr>
                <td class="px-4 py-3 whitespace-nowrap">{{ $a->created_at?->format('Y/m/d H:i') }}</td>
                <td class="px-4 py-3">{{ $a->job->title ?? '-' }}</td>
                <td class="px-4 py-3">{{ $a->job->company->name ?? '-' }}</td>
                <td class="px-4 py-3">{{ $statusLabels[$a->status] ?? $a->status ?? '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <a href="{{ route('mypage.applications.show', $a->id) }}"
                     class="text-indigo-600 hover:underline">見る</a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <div class="mt-6">
        {{ $apps->links() }}
      </div>
    @endif
  </div>
@endsection
