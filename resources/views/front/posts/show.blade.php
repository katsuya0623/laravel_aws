@extends('front.layout')

@section('title', $post->title ?? 'Post')

@section('content')
@php
  use Illuminate\Support\Str;
  use Illuminate\Support\Facades\Storage;
  use Illuminate\Support\Carbon;

  // 日付（published_at > created_at）
  $rawDate = $post->published_at ?? $post->created_at ?? null;
  $dateStr = $rawDate ? Carbon::parse($rawDate)->format('Y-m-d') : '';

  // サムネイルURL（http/https or / ならそのまま、相対なら Storage::url）
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

  {{-- 本文（WYSIWYGのHTMLをそのまま活かす） --}}
  <div class="post-body prose max-w-none">
    {!! $body !!}
  </div>

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

{{-- 本文用の強制スタイル：daisyUI/tailwindのリセットで潰されてもWYSIWYGを成立させる --}}
<style>
  /* 本文の基本（pとか） */
  .post-body p { margin: 0.75em 0; line-height: 1.8; }

  /* 見出し */
  .post-body h1 { font-size: 2rem; font-weight: 800; margin: 1.2em 0 0.6em; line-height: 1.25; }
  .post-body h2 { font-size: 1.6rem; font-weight: 800; margin: 1.2em 0 0.6em; line-height: 1.3; }
  .post-body h3 { font-size: 1.3rem; font-weight: 800; margin: 1.1em 0 0.55em; line-height: 1.35; }

  /* リスト（番号/黒丸が消える問題の決定打） */
  .post-body ol { list-style: decimal !important; padding-left: 1.6em !important; margin: 0.9em 0; }
  .post-body ul { list-style: disc !important; padding-left: 1.6em !important; margin: 0.9em 0; }
  .post-body li { margin: 0.35em 0; }
  .post-body ol > li::marker,
  .post-body ul > li::marker { font-weight: 700; }

  /* 引用 */
  .post-body blockquote {
    border-left: 4px solid #e5e7eb;
    padding-left: 1em;
    margin: 1em 0;
    color: #374151;
  }

  /* リンク */
  .post-body a { text-decoration: underline; }

  /* 画像（WYSIWYGで入れた画像が崩れないように） */
  .post-body img { max-width: 100%; height: auto; border-radius: 0.75rem; margin: 1em 0; }

  /* コード */
  .post-body code {
    background: #f3f4f6;
    padding: 0.2em 0.35em;
    border-radius: 0.35em;
    font-size: 0.95em;
  }
  .post-body pre {
    background: #111827;
    color: #f9fafb;
    padding: 1rem;
    border-radius: 0.75rem;
    overflow-x: auto;
    margin: 1em 0;
  }
  .post-body pre code { background: transparent; padding: 0; }

  /* 水平線 */
  .post-body hr { margin: 1.5em 0; border: 0; border-top: 1px solid #e5e7eb; }
</style>
@endsection
