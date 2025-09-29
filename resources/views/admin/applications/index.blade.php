<x-app-layout>
  <x-slot name="header">
    <div class="flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-semibold">応募一覧（管理者）</h1>
        <p class="text-sm text-gray-500 mt-1">全求人の応募を横断して確認できます。</p>
      </div>
      <a href="{{ route('admin.applications.export', request()->query()) }}"
         class="inline-flex items-center px-3 py-2 rounded-md bg-gray-800 text-white hover:bg-gray-900 text-sm">
        CSVエクスポート
      </a>
    </div>
  </x-slot>

  @php
    // コントローラから $statusOptions が来ていれば、それで有無判定
    $hasStatus = !empty($statusOptions ?? []);
    $cols = 6 + ($hasStatus ? 1 : 0); // ID,日時,応募者,メール,求人,求人ID,(+ステータス)
  @endphp

  <div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      {{-- 絞り込み --}}
      <form method="get" class="mb-4 grid gap-3 md:grid-cols-5">
        <div class="md:col-span-2">
          <label class="block text-sm text-gray-600 mb-1">キーワード</label>
          <input type="text" name="q" value="{{ request('q') }}"
                 class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                 placeholder="応募者名 / メール / 求人タイトル">
        </div>

        @if($hasStatus)
          <div>
            <label class="block text-sm text-gray-600 mb-1">ステータス</label>
            <select name="status"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
              @foreach($statusOptions as $val => $label)
                <option value="{{ $val }}"
                        @selected((string)request('status', '') === (string)$val)>
                  {{ $label }}
                </option>
              @endforeach
            </select>
          </div>
        @endif

        <div>
          <label class="block text-sm text-gray-600 mb-1">求人ID</label>
          <input type="number" name="job_id" value="{{ request('job_id') }}"
                 class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div>
          <label class="block text-sm text-gray-600 mb-1">会社ID</label>
          <input type="number" name="company_id" value="{{ request('company_id') }}"
                 class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <div class="md:col-span-5">
          <button class="inline-flex items-center px-4 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
            絞り込み
          </button>
        </div>
      </form>

      {{-- 一覧 --}}
      <div class="bg-white border border-gray-200 rounded-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="px-4 py-3">ID</th>
              <th class="px-4 py-3">応募日時</th>
              <th class="px-4 py-3">応募者</th>
              <th class="px-4 py-3">メール</th>
              <th class="px-4 py-3">求人</th>
              <th class="px-4 py-3">求人ID</th>
              @if($hasStatus)
                <th class="px-4 py-3">ステータス</th>
              @endif
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            @forelse ($applications as $ap)
              <tr class="text-sm">
                <td class="px-4 py-3">{{ $ap->id }}</td>
                <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                  {{ optional($ap->created_at)->format('Y-m-d H:i') }}
                </td>
                <td class="px-4 py-3 font-medium">{{ $ap->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $ap->email ?? '—' }}</td>
                <td class="px-4 py-3">{{ optional($ap->job)->title ?? '（削除済み求人）' }}</td>
                <td class="px-4 py-3">{{ $ap->job_id ?? '—' }}</td>

                @if($hasStatus)
                  @php
                    $status = (string)($ap->status ?? '');
                    $badge = match($status){
                      'new'        => 'bg-blue-50 text-blue-700 ring-blue-200',
                      'viewed'     => 'bg-gray-50 text-gray-700 ring-gray-200',
                      'interview'  => 'bg-yellow-50 text-yellow-800 ring-yellow-200',
                      'offer'      => 'bg-green-50 text-green-700 ring-green-200',
                      'rejected'   => 'bg-red-50 text-red-700 ring-red-200',
                      'withdrawn'  => 'bg-amber-50 text-amber-800 ring-amber-200',
                      default      => 'bg-slate-50 text-slate-700 ring-slate-200',
                    };
                  @endphp
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium ring-1 {{ $badge }}">
                      {{ $status ?: '—' }}
                    </span>
                  </td>
                @endif
              </tr>
            @empty
              <tr>
                <td colspan="{{ $cols }}" class="px-4 py-8 text-center text-gray-500">
                  応募がありません。
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-4">
        {{ $applications->links() }}
      </div>
    </div>
  </div>
</x-app-layout>
