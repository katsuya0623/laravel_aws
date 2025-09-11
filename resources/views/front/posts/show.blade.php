@extends('front.layout')

@section('title', $post->title ?? 'Post')

@section('content')
@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Carbon;

  // 日付（published_at > created_at の順で存在する方を使う／文字列でもOK）
  $rawDate = $post->published_at ?? $post->created_at ?? null;
  $dateStr = $rawDate ? Carbon::parse($rawDate)->format('Y-m-d') : '';

  // サムネイルのURL（http/https or / ならそのまま、相対なら Storage::url）
  $thumbUrl = null;
  if (!empty($post->thumbnail_path)) {
      $thumbUrl = (Str::startsWith($post->thumbnail_path, ['http://', 'https://', '/']))
        ? $post->thumbnail_path
        : Storage::url($post->thumbnail_path);
  }

  // 本文HTML：storage系の相対パスを絶対化（<img src="storage/...">等を asset('storage') に）
  $body = $post->body ?? '';
  $body = preg_replace('#(src=([\'"]))/?(?:public/)?storage/#i', 'src=$2' . asset('storage') . '/', $body);
@endphp

<article class="max-w-3xl">

  {{-- タイトル --}}
  <h1 class="text-3xl font-bold mb-2">{{ $post->title ?? '(無題)' }}</h1>

  {{-- 日付 + カテゴリ --}}
  <p class="text-sm text-gray-500 mb-6">
    {{ $dateStr }}

    @if(isset($postCats) && $postCats->count())
      ・
      @foreach($postCats as $c)
        @if(!empty($c->slug))
          <a class="hover:underline" href="{{ route('front.category.show', ['slug' => $c->slug]) }}">{{ $c->name }}</a>@if(!$loop->last)、@endif
        @else
          {{ $c->name }}@if(!$loop->last)、@endif
        @endif
      @endforeach
    @endif
  </p>

  {{-- サムネイル（任意） --}}
  @if($thumbUrl)
    <img src="{{ $thumbUrl }}" class="w-full rounded mb-6" alt="{{ $post->title }}">
  @endif

  {{-- 本文（HTML保存想定：信頼できる入力前提でそのまま描画） --}}
  <div class="prose max-w-none">{!! $body !!}</div>

  {{-- タグ --}}
  @if(isset($postTags) && $postTags->count())
    <div class="mt-8">
      @foreach($postTags as $t)
        @if(!empty($t->slug))
          <a href="{{ route('front.tag.show', ['slug' => $t->slug]) }}"
             class="inline-block mr-2 mb-2 px-2 py-1 text-sm rounded border hover:bg-gray-50">#{{ $t->name }}</a>
        @else
          <span class="inline-block mr-2 mb-2 px-2 py-1 text-sm rounded border text-gray-500">#{{ $t->name }}</span>
        @endif
      @endforeach
    </div>
  @endif

  {{-- 前後記事 --}}
  @if(!empty($prev) || !empty($next))
    <nav class="mt-10 flex items-center justify-between gap-3">
      <div>
        @isset($prev)
          @php $prevKey = $prev->slug ?? $prev->id; @endphp
          <a href="{{ route('front.posts.show', $prevKey) }}"
             class="inline-flex items-center text-sm text-indigo-600 hover:underline">
            ← 前の記事：{{ \Illuminate\Support\Str::limit($prev->title ?? '', 24) }}
          </a>
        @endisset
      </div>
      <div>
        @isset($next)
          @php $nextKey = $next->slug ?? $next->id; @endphp
          <a href="{{ route('front.posts.show', $nextKey) }}"
             class="inline-flex items-center text-sm text-indigo-600 hover:underline">
            次の記事：{{ \Illuminate\Support\Str::limit($next->title ?? '', 24) }} →
          </a>
        @endisset
      </div>
    </nav>
  @endif

  {{-- 一覧へ戻る --}}
  <div class="mt-8">
    <a href="{{ route('front.posts.index') }}" class="text-sm text-gray-600 hover:underline">記事一覧へ戻る</a>
  </div>
</article>

{{-- 関連記事 --}}
@if(!empty($related) && $related->count())
  <section class="max-w-5xl mt-12">
    <h2 class="text-xl font-semibold mb-4">関連記事</h2>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      @foreach($related as $rp)
        @php
          $rpKey   = $rp->slug ?? $rp->id;
          $rpDate  = $rp->published_at ?? null;
          $rpDateS = $rpDate ? \Illuminate\Support\Carbon::parse($rpDate)->format('Y-m-d') : '';
        @endphp
        <article class="bg-white rounded-xl p-4 border">
          <h3 class="font-medium">
            <a href="{{ route('front.posts.show', $rpKey) }}" class="hover:underline">
              {{ $rp->title }}
            </a>
          </h3>
          <p class="text-xs text-gray-500 mt-1">{{ $rpDateS }}</p>
        </article>
      @endforeach
    </div>
  </section>
@endif
@endsection
