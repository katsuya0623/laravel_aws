<x-app-layout>
  <x-slot name="header">
    <div class="text-center">
      <h2 class="font-semibold text-2xl tracking-tight">求人一覧</h2>
      <p class="text-gray-500 text-sm mt-1">ここから求人の閲覧・作成ができます。</p>
    </div>
  </x-slot>

  <div class="max-w-6xl mx-auto px-4 py-8">
    @php $role = auth()->check() ? \App\Support\RoleResolver::resolve(auth()->user()) : null; @endphp
    @if($role === 'company')
      <div class="mb-6 text-right">
        <a href="{{ route('front.jobs.create') }}"
           class="inline-block bg-indigo-600 text-white font-semibold px-5 py-2 rounded-lg hover:bg-indigo-500 transition">
          ＋ 新しい求人を作成
        </a>
      </div>
    @endif

    @if (session('success'))
      <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
        {{ session('success') }}
      </div>
    @endif

    @forelse($jobs as $job)
      <a href="{{ route('front.jobs.show', $job->slug ?? $job->id) }}"
         class="block border rounded-2xl p-5 mb-4 bg-white shadow-sm hover:shadow-md transition">
        <div class="flex items-center gap-4">
          <div class="h-14 w-14 grid place-items-center rounded-xl bg-gray-100 text-gray-500 text-sm">NO IMAGE</div>
          <div class="flex-1">
            <h3 class="font-semibold text-lg">{{ $job->title }}</h3>
            <p class="text-gray-600 text-sm mt-1">
              {{ \Illuminate\Support\Str::limit($job->description ?? ($job->body ?? ''), 80) }}
            </p>
            @if(($job->status ?? null) === 'published')
              <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700">published</span>
            @endif
          </div>
        </div>
      </a>
    @empty
      <p class="text-gray-500">求人がまだありません。</p>
    @endforelse
  </div>
</x-app-layout>
