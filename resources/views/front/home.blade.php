@extends('front.layout')
@section('title','Home')

@section('content')
  @php
    use Illuminate\Support\Facades\Storage;
    $items = collect($latest ?? ($posts ?? [])); // 必ず Collection 化
  @endphp

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">最新記事</h1>
    @if (Route::has('front.posts.index'))
      <a class="text-indigo-600 hover:underline" href="{{ route('front.posts.index') }}">すべての記事を見る</a>
    @endif
  </div>

  @if ($items->count())
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      @foreach ($items as $post)
        @php
          $slug    = $post->slug ?? null;
          $postUrl = ($slug && Route::has('front.posts.show')) ? route('front.posts.show', $slug) : '#';

          $thumb = null;
          if (!empty($post->thumbnail_url))       $thumb = $post->thumbnail_url;
          elseif (!empty($post->thumbnail_path))  $thumb = Storage::url($post->thumbnail_path);
          elseif (!empty($post->thumbnail))       $thumb = Storage::url($post->thumbnail);

          $dateText = '';
          try {
            $raw = $post->published_at ?? $post->created_at ?? null;
            if ($raw) { $dateText = \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d'); }
          } catch (\Throwable $e) {}
          $cats = [];
          if (isset($post->categories) && (is_array($post->categories) || $post->categories instanceof \Illuminate\Support\Collection)) {
            foreach ($post->categories as $c) {
              $name = $c->name ?? (is_array($c) ? ($c['name'] ?? null) : null);
              $slugC = $c->slug ?? (is_array($c) ? ($c['slug'] ?? null) : null);
              if ($name) $cats[] = ['name'=>$name,'slug'=>$slugC];
            }
          }
        @endphp

        <article class="rounded-xl overflow-hidden border bg-white">
          <a href="{{ $postUrl }}" class="block">
            @if ($thumb)
              <div class="aspect-[16/9] bg-gray-100">
                <img src="{{ $thumb }}" alt="{{ $post->title ?? '' }}" class="w-full h-full object-cover" loading="lazy">
              </div>
            @endif
            <div class="p-4">
              <h2 class="font-semibold text-lg line-clamp-2">{{ $post->title ?? '(無題)' }}</h2>
              @if ($dateText)
                <p class="text-sm text-gray-500 mt-1">{{ $dateText }}</p>
              @endif
              @if ($cats)
                <div class="mt-2 text-xs flex gap-2 flex-wrap">
                  @foreach ($cats as $c)
                    @php
                      $catUrl = ($c['slug'] ?? null) && Route::has('front.categories.show')
                        ? route('front.categories.show', $c['slug'])
                        : '#';
                    @endphp
                    <a class="px-2 py-0.5 bg-gray-100 rounded" href="{{ $catUrl }}">{{ $c['name'] }}</a>
                  @endforeach
                </div>
              @endif
            </div>
          </a>
        </article>
      @endforeach
    </div>
  @endif

  {{-- 下の企業・求人（これが正解のブロック） --}}
  @includeIf('partials.front-company-jobs')
@endsection

<!-- HOMEMARK -->
