@extends('front.layout')
@section('title', 'カテゴリ: '.$category->name)
@section('content')
  <h1 class="text-2xl font-bold mb-4">カテゴリ: {{ $category->name }}</h1>
  @foreach($posts as $post)
    <article class="bg-white rounded-lg shadow p-4 mb-4">
      <h2 class="font-semibold text-xl">
        <a href="{{ route('front.posts.show',$post->slug ?? '') }}" class="hover:underline">{{ $post->title }}</a>
      </h2>
      <p class="text-xs text-gray-500 mt-1">{{ $post->published_at ?? '' }}</p>
      <div class="mt-2 text-sm">{{ \Illuminate\Support\Str::limit(strip_tags($post->body ?? ''), 180) }}</div>
    </article>
  @endforeach
  <div class="mt-6">{{ $posts->links() }}</div>
@endsection
