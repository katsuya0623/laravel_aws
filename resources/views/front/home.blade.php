@extends('front.layout')
@section('title','Home')

@section('content')
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">最新記事</h1>
    <a class="text-indigo-600 hover:underline" href="{{ route('front.posts.index') }}">すべての記事を見る</a>
  </div>

  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($latest as $post)
      <article class="rounded-xl overflow-hidden border bg-white">
        <a href="{{ route('front.posts.show', $post->slug) }}" class="block">
          {{-- サムネがある時だけ表示 --}}
          @if ($post->has_thumbnail)
            <div class="aspect-[16/9] bg-gray-100">
              <img
                src="{{ $post->thumbnail_url }}"
                alt="{{ $post->title }}"
                class="w-full h-full object-cover"
                loading="lazy"
              >
            </div>
          @endif

          <div class="p-4">
            <h2 class="font-semibold text-lg line-clamp-2">{{ $post->title }}</h2>
            <p class="text-sm text-gray-500 mt-1">
              {{ optional($post->published_at ?? $post->created_at)->format('Y-m-d') }}
            </p>

            @if($post->categories?->count())
              <div class="mt-2 text-xs flex gap-2 flex-wrap">
                @foreach($post->categories as $c)
                  <a class="px-2 py-0.5 bg-gray-100 rounded" href="{{ route('front.categories.show', $c->slug) }}">
                    {{ $c->name }}
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        </a>
      </article>
    @endforeach
  </div>

  {{-- company/jobs injected --}}
  @include('partials.front-company-jobs')
@endsection

<!-- HOMEMARK -->
