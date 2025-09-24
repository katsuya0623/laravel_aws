@extends('layouts.app')

@section('title', '企業一覧')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">企業一覧</h1>
  </div>

  <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">ID</th>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">企業名</th>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">Slug</th>
          <th class="px-4 py-3 text-left font-semibold text-gray-600">更新日</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-100">
        @forelse ($companies as $c)
          <tr>
            <td class="px-4 py-3 text-gray-700">{{ $c->id }}</td>
            <td class="px-4 py-3 text-gray-900">{{ $c->company_name }}</td>
            <td class="px-4 py-3 text-gray-700">{{ $c->slug }}</td>
            <td class="px-4 py-3 text-gray-700">{{ optional($c->updated_at)->format('Y-m-d') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="px-4 py-10 text-center text-gray-500">
              まだ企業がありません。ダッシュボードの「企業情報」から登録してください。
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-6">
    {{ $companies->links() }}
  </div>
</div>
@endsection
