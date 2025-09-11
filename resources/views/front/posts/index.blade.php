@extends('front.layout')
@section('title','記事一覧')

@section('content')
<div style="display:grid; grid-template-columns: 1fr 260px; gap:24px;">

  {{-- メインカラム --}}
  <section>
    <h1 style="font-size:22px; font-weight:700; margin-bottom:12px;">記事一覧</h1>

    {{-- ▼ 検索・絞り込みフォーム（フロント用） --}}
    <form method="GET" action="{{ route('front.posts.index') }}" style="margin:0 0 16px; display:flex; gap:8px; flex-wrap:wrap;">
      <input type="search" name="q" value="{{ request('q') }}"
             placeholder="キーワード（スペースでAND）"
             style="flex:1; min-width:200px; padding:6px 10px; border:1px solid #bbb; border-radius:6px;">

      <?php $cats = collect($categories ?? []); ?>
      <select name="category" style="padding:6px 10px; border:1px solid #bbb; border-radius:6px;">
        <option value="">カテゴリ：すべて</option>
        @foreach($cats as $cat)
          <option value="{{ $cat->id }}" @selected((string)request('category')===(string)$cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>

      <button type="submit"
              style="padding:6px 10px; border:1px solid #4f46e5; background:#4f46e5; color:#fff; border-radius:6px;">
        検索
      </button>
      <a href="{{ route('front.posts.index') }}"
         style="padding:6px 10px; border:1px solid #999; border-radius:6px; text-decoration:none; color:#333;">
        クリア
      </a>
    </form>

    {{-- ▼ 一覧 --}}
    <ul style="list-style:none; padding-left:0; display:grid; gap:12px;">
      @forelse ($posts as $post)
        <?php
          $slugOrId = (!empty($post->slug)) ? $post->slug : $post->id;   // 詳細リンク: slug→id
          $date     = $post->published_at ?? $post->created_at ?? null;  // 表示日付
        ?>

        <li style="border:1px solid #eee; border-radius:8px; overflow:hidden;">
          <a href="{{ route('front.posts.show', $slugOrId) }}"
             style="display:flex; gap:12px; align-items:flex-start; padding:10px; text-decoration:none; color:inherit;">

            @if (!empty($post->thumbnail_path))
              <?php
                $thumb = \Illuminate\Support\Str::startsWith($post->thumbnail_path, ['http://','https://','/'])
                  ? $post->thumbnail_path
                  : \Illuminate\Support\Facades\Storage::url($post->thumbnail_path);
              ?>
              <img src="{{ $thumb }}" alt="{{ $post->title }}"
                   style="width:96px; height:96px; object-fit:cover; border:1px solid #ddd; border-radius:6px;">
            @endif

            <div style="min-width:0;">
              <h2 style="font-weight:600; color:#111; margin:0 0 2px;">
                {{ $post->title ?: '(無題)' }}
              </h2>

              <div style="font-size:12px; color:#666;">
                {{ $date ? \Illuminate\Support\Carbon::parse($date)->format('Y-m-d') : '' }}
              </div>

              {{-- 複数カテゴリ（$catsByPost） --}}
              <?php $catsOfPost = collect(isset($catsByPost) ? ($catsByPost[$post->id] ?? []) : []); ?>
              @if($catsOfPost->count())
                <div style="margin-top:6px; display:flex; gap:6px; flex-wrap:wrap;">
                  @foreach($catsOfPost as $c)
                    @if(!empty($c->slug))
                      <a href="{{ route('front.category.show', ['slug'=>$c->slug]) }}"
                         style="font-size:12px; padding:2px 8px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px; text-decoration:none; color:#333;">
                        {{ $c->name }}
                      </a>
                    @else
                      <span style="font-size:12px; padding:2px 8px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px; color:#666;">
                        {{ $c->name }}
                      </span>
                    @endif
                  @endforeach
                </div>
              @endif

              @if(!empty($post->body))
                <p style="margin-top:6px; font-size:13px; color:#555;">
                  {{ \Illuminate\Support\Str::limit(strip_tags($post->body), 100) }}
                </p>
              @endif
            </div>
          </a>
        </li>

      @empty
        <li><p>投稿がありません。</p></li>
      @endforelse
    </ul>

    {{-- ▼ ページネーション（LengthAwarePaginator のときだけ） --}}
    @if (method_exists($posts, 'links'))
      <div style="margin-top:16px;">
        {{ $posts->links() }}
      </div>
    @endif
  </section>

  {{-- サイドバー：タグ一覧 --}}
  <aside>
    <div style="border:1px solid #eee; border-radius:8px; padding:12px;">
      <h3 style="font-weight:600; margin-bottom:8px;">タグ</h3>
      <?php $sidebarTags = collect($tags ?? []); ?>
      <ul style="display:flex; flex-wrap:wrap; gap:8px; list-style:none; padding-left:0;">
        @foreach($sidebarTags as $tag)
          @if(!empty($tag->slug))
            <li>
              <a href="{{ route('front.tag.show', ['slug'=>$tag->slug]) }}"
                 style="font-size:13px; padding:4px 8px; border:1px solid #ddd; border-radius:999px; text-decoration:none; color:#333;">
                #{{ $tag->name }}
              </a>
            </li>
          @else
            <li>
              <span style="font-size:13px; padding:4px 8px; border:1px solid #eee; border-radius:999px; color:#999;">
                #{{ $tag->name }}
              </span>
            </li>
          @endif
        @endforeach
      </ul>
    </div>
  </aside>

</div>
@endsection
