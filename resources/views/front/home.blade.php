@extends('layouts.front')
@section('title','Home')
@section('content')
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">最新記事</h1>
    <a class="text-indigo-600 hover:underline" href="{{ route('front.posts.index') }}">すべての記事を見る</a>
  </div>

  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($latest as $post)
      <article class="bg-white rounded-xl p-4 shadow">
        <h2 class="font-semibold text-lg">
          <a href="{{ route('front.posts.show',$post->slug) }}" class="hover:underline">{{ $post->title }}</a>
        </h2>
        <p class="text-sm text-gray-500 mt-1">{{ optional($post->published_at)->format('Y-m-d') }}</p>
        @if($post->categories?->count())
          <div class="mt-2 text-xs flex gap-2 flex-wrap">
            @foreach($post->categories as $c)
              <a class="px-2 py-0.5 bg-gray-100 rounded" href="{{ route('front.categories.show',$c->slug) }}">{{ $c->name }}</a>
            @endforeach
          </div>
        @endif
      </article>
    @endforeach
  </div>
@endsection
