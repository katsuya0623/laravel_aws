@extends('layouts.app')

@section('content')
<div class="py-8">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold mb-6">スポンサー記事一覧</h1>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      @forelse($posts as $post)
        @php
          // サムネ取得（存在しない時はダミー）
          $thumb = $post->thumbnail_url
              ?? ($post->thumbnail ? Storage::url($post->thumbnail) : '/images/noimage.jpg');
        @endphp
        <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-md transition">
          <a href="{{ route('front.posts.show', $post->slug ?? $post->id) }}">
            <img class="w-full h-48 object-cover" src="{{ $thumb }}" alt="{{ $post->title }}">
            <div class="p-4">
              <h2 class="text-lg font-bold line-clamp-2">{{ $post->title }}</h2>
              <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ $post->excerpt ?? '' }}</p>
            </div>
          </a>
        </div>
      @empty
        <p>スポンサー記事はまだありません。</p>
      @endforelse
    </div>

    <div class="mt-6">
      {{ $posts->links() }}
    </div>
  </div>
</div>
@endsection
