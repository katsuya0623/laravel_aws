<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>求人一覧</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-slate-800">
  <!-- Top Nav -->
  <header class="bg-slate-900 text-white">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
      <div class="font-semibold tracking-wide">nibi</div>
      <nav class="space-x-6 text-sm">
        <a href="{{ url('/dashboard') }}" class="hover:underline">ダッシュボード</a>
        <a href="{{ route('admin.posts.index') ?? '#' }}" class="hover:underline">記事一覧</a>
        <a href="{{ route('admin.companies.index') ?? '#' }}" class="hover:underline">企業</a>
        <a class="font-bold underline">求人</a>
      </nav>
      @auth
      <form action="{{ route('logout') }}" method="POST" class="hidden md:block">@csrf
        <button class="text-sm hover:underline">ログアウト</button>
      </form>
      @endauth
    </div>
  </header>

  <main class="max-w-6xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-4">
      <h1 class="text-2xl font-bold">求人一覧</h1>
      <a href="{{ route('admin.jobs.create') }}"
         class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-2 text-white text-sm hover:bg-indigo-700">
        ＋ 新規作成
      </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <!-- 左：検索 + リスト -->
      <section class="lg:col-span-3">
        <form method="GET" class="flex flex-wrap items-end gap-3 mb-4">
          <div class="grow">
            <label class="block text-xs text-slate-500 mb-1">キーワード（スペースでAND）</label>
            <input type="text" name="q" value="{{ $q ?? '' }}"
              class="w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
              placeholder="例: フロントエンド リモート">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">ステータス</label>
            <select name="status" class="rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="">すべて</option>
              @foreach($statuses as $s)
                <option value="{{ $s }}" @selected(($status ?? '')===$s)>{{ $s }}</option>
              @endforeach
            </select>
          </div>
          <div class="flex gap-2">
            <button class="rounded-lg bg-indigo-600 text-white px-4 py-2 hover:bg-indigo-700">検索</button>
            <a href="{{ route('admin.jobs.index') }}" class="rounded-lg border px-4 py-2 hover:bg-slate-100">クリア</a>
          </div>
        </form>

        @if(session('status'))
          <div class="mb-3 rounded-lg bg-green-50 text-green-800 px-4 py-2 text-sm">
            {{ session('status') }}
          </div>
        @endif

        @if ($jobs->isEmpty())
          <p class="text-slate-500">求人はまだありません。</p>
        @else
          <ul class="space-y-3">
            @foreach ($jobs as $job)
              <li class="rounded-xl border bg-white p-4 shadow-sm hover:shadow transition">
                <div class="flex gap-4">
                  @php $thumb = $job->image_url ?? $job->thumbnail_url ?? null; @endphp
                  @if($thumb)
                    <img src="{{ $thumb }}" alt="" class="w-16 h-16 rounded-lg object-cover border">
                  @else
                    <div class="w-16 h-16 rounded-lg bg-slate-100 border flex items-center justify-center text-slate-400">
                      <span class="text-xs">NO IMAGE</span>
                    </div>
                  @endif

                  <div class="min-w-0 grow">
                    <div class="flex items-start justify-between gap-3">
                      <a href="{{ route('admin.jobs.edit', $job) }}"
                         class="font-semibold truncate hover:underline">{{ $job->title ?? '(無題)' }}</a>
                      <div class="shrink-0">
                        @php
                          $badge = $job->status ?? (($job->is_published ?? null) ? 'published' : 'draft');
                          $badgeClass = $badge === 'published' ? 'bg-green-100 text-green-800' :
                                        ($badge === 'draft' ? 'bg-slate-100 text-slate-700' : 'bg-indigo-100 text-indigo-800');
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs {{ $badgeClass }}">{{ $badge }}</span>
                      </div>
                    </div>

                    <div class="text-xs text-slate-500 mt-1 flex items-center gap-2">
                      <span>{{ optional($job->company)->name ?? ($job->company_name ?? '—') }}</span>
                      <span>・</span>
                      <span>{{ $job->published_at ? \Illuminate\Support\Carbon::parse($job->published_at)->format('Y-m-d') : '未公開' }}</span>
                      @if(!empty($job->slug))
                        <span>・</span>
                        <span class="truncate text-slate-400">/jobs/{{ $job->slug }}</span>
                      @endif
                    </div>

                    @if(!empty($job->excerpt))
                      <p class="text-sm text-slate-700 mt-2">
                        {{ \Illuminate\Support\Str::limit($job->excerpt, 120) }}
                      </p>
                    @endif

                    <div class="mt-3 flex flex-wrap gap-2">
                      @if(Route::has('admin.jobs.show'))
                        <a href="{{ route('admin.jobs.show', $job) }}" class="text-indigo-700 text-sm hover:underline">詳細</a>
                      @endif
                      <a href="{{ route('admin.jobs.edit', $job) }}" class="text-indigo-700 text-sm hover:underline">編集</a>
                      @if(Route::has('admin.jobs.destroy'))
                        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}"
                              onsubmit="return confirm('削除しますか？');" class="inline">
                          @csrf @method('DELETE')
                          <button class="text-rose-700 text-sm hover:underline" type="submit">削除</button>
                        </form>
                      @endif
                    </div>
                  </div>
                </div>
              </li>
            @endforeach
          </ul>

          <div class="mt-6">
            {{ $jobs->withQueryString()->links() }}
          </div>
        @endif
      </section>

      <!-- 右：タグ/フィルタ -->
      <aside class="lg:col-span-1">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between mb-2">
            <h2 class="font-semibold">タグ</h2>
            <span class="text-xs text-slate-500">（status）</span>
          </div>
          <div class="flex flex-wrap gap-2">
            @forelse($statuses as $s)
              <a href="{{ route('admin.jobs.index', array_filter(['q'=>$q ?? null,'status'=>$s])) }}"
                 class="px-2 py-1 rounded-full text-sm bg-slate-100 hover:bg-slate-200">
                 #{{ $s }}
              </a>
            @empty
              <span class="text-slate-400 text-sm">ステータスが未定義です</span>
            @endforelse
          </div>
        </div>
      </aside>
    </div>
  </main>

  <footer class="text-center text-xs text-slate-400 py-8">© {{ date('Y') }}</footer>
</body>
</html>
