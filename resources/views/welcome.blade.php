<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>nibiにようこそ！（TEST）</title>

  {{-- ★ 本番でも必ず効くフォールバックCSS（Tailwindビルド済み） --}}
  <link rel="stylesheet" href="/build/tw.css?v={{ now()->timestamp }}">

  {{-- (任意) Vite が使える環境ならこちらも効く --}}
  @vite(['resources/css/app.css','resources/js/app.js'])

  {{-- Tailwindが万一読めなくても最低限崩れないための保険 --}}
  <style>
    .fallback-center { min-height: 100svh; display: grid; place-items: center; }
    .fallback-top-right { position: absolute; top: 1rem; right: 1rem; }
    .logo { max-height: 6rem; width: auto; }
    @media (min-width: 640px) {.logo { max-height: 7rem; }}
    @media (min-width: 768px) {.logo { max-height: 8rem; }}
  </style>
</head>
<body class="min-h-screen relative grid place-items-start antialiased bg-white dark:bg-gray-900">

  {{-- 右上：ログイン系（Tailwindでも保険でも右上固定） --}}
  @if (Route::has('login'))
    <div class="absolute top-4 right-6 space-x-4 text-right fallback-top-right">
      @auth
        <a href="{{ url('/dashboard') }}" class="text-sm text-gray-700 dark:text-gray-300 underline">Dashboard</a>
      @else
        <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-300 underline">Log in</a>
        @if (Route::has('register'))
          <a href="{{ route('register') }}" class="text-sm text-gray-700 dark:text-gray-300 underline">Register</a>
        @endif
      @endauth
    </div>
  @endif

  {{-- 画面ど真ん中のメイン（ヒーロー） --}}
  <main class="w-full">
<div class="mt-20" style="display:flex; justify-content:center;">
  <div class="text-center">
    <img src="{{ asset('logo.svg') }}" alt="nibi logo" class="mx-auto logo">
    <h1 class="mt-6 text-2xl md:text-3xl font-bold text-gray-800 dark:text-gray-100">
      nibiにようこそ！
    </h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400">ここから開発を始めましょう。</p>
  </div>
</div>


    {{-- === LATEST_POSTS_BLOCK: 最新記事（管理一覧と同じレイアウト / 最新10件） === --}}
    @php
      use Illuminate\Support\Facades\DB;
      use Illuminate\Support\Facades\Schema;
      use Illuminate\Support\Facades\Storage;

      // コントローラから $posts が来ていない場合のフォールバック取得（10件）
      $useCatMap = false;
      if (!isset($posts)) {
          try {
              $q = DB::table('posts');
              if (Schema::hasColumn('posts','published_at')) {
                  $q->orderByDesc('published_at');
              }
              $posts = $q->select([
                  'id','title','slug',
                  DB::raw('COALESCE(published_at, NULL) as published_at'),
                  DB::raw('COALESCE(thumbnail_path, NULL) as thumbnail_path'),
                  DB::raw('COALESCE(category_id, NULL) as category_id'),
                  DB::raw('COALESCE(reading_time, NULL) as reading_time'),
                  DB::raw('COALESCE(is_featured, 0) as is_featured'),
              ])->orderByDesc('id')->limit(10)->get();
              $useCatMap = true;
          } catch (\Throwable $e) { $posts = collect(); }
      }

      // 単一カテゴリ型（posts.category_id）がある場合のカテゴリ名マップ
      $catMap = [];
      if ($useCatMap && Schema::hasTable('categories')) {
          try {
              $ids = collect($posts)->pluck('category_id')->filter()->unique();
              if ($ids->count()) {
                  $catMap = DB::table('categories')->whereIn('id',$ids)->pluck('name','id')->toArray();
              }
          } catch (\Throwable $e) { $catMap = []; }
      }
    @endphp

    @if($posts && count($posts))
      <section style="width:100%; max-width:1100px; margin:40px auto 0; padding:0 16px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
          <h2 style="font-weight:700; font-size:18px;">最新記事</h2>
          <a href="{{ route('front.posts.index') }}" style="font-size:14px; color:#4f46e5; text-decoration:none;">記事一覧へ</a>
        </div>

        {{-- 管理の一覧に寄せる：サムネ左・テキスト右の縦リスト --}}
        <ul style="list-style:none; margin:0; padding:0; background:#fff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
          @foreach($posts as $post)
            @php
              $thumb = !empty($post->thumbnail_path) ? Storage::url($post->thumbnail_path) : null;

              $dateText = '';
              try {
                  $dateText = $post->published_at
                      ? \Illuminate\Support\Carbon::parse($post->published_at)->format('Y-m-d H:i')
                      : '';
              } catch (\Throwable $e) {}

              // Eloquent で来ていれば $post->category?->name、フォールバック時は $catMap
              $catName = $post->category->name ?? ($catMap[$post->category_id] ?? 'カテゴリなし');

              $reading = (!is_null($post->reading_time) && (int)$post->reading_time > 0) ? (int)$post->reading_time : null;
              $isFeatured = (bool)($post->is_featured ?? false);
            @endphp

            <li style="padding:12px 16px; border-top:1px solid #e5e7eb;">
              <div style="display:flex; align-items:flex-start; gap:10px; flex-wrap:wrap;">
                <a href="{{ !empty($post->slug) ? route('front.posts.show',$post->slug) : '#' }}" style="display:block; flex-shrink:0;">
                  <div style="width:60px; height:60px; border:1px solid #ddd; border-radius:4px; overflow:hidden; background:#f8fafc; display:grid; place-items:center;">
                    @if($thumb)
                      <img src="{{ $thumb }}" alt="" style="width:60px; height:60px; object-fit:cover;">
                    @else
                      <span style="font-size:11px; color:#94a3b8;">no image</span>
                    @endif
                  </div>
                </a>

                <div style="min-width:240px; flex:1;">
                  <a href="{{ !empty($post->slug) ? route('front.posts.show',$post->slug) : '#' }}"
                     style="font-weight:600; color:#1d4ed8; text-decoration:none;">
                    {{ $post->title ?: '(無題)' }}
                  </a>

                  <div style="margin-top:4px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; font-size:12px; color:#555;">
                    <small style="color:#666;">{{ $dateText }}</small>

                    {{-- カテゴリ（単一想定） --}}
                    <span style="margin-left:6px; padding:2px 8px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px;">
                      {{ $catName }}
                    </span>

                    {{-- 読む時間 --}}
                    @if($reading)
                      <span style="margin-left:6px; padding:2px 8px; background:#eef2ff; border:1px solid #c7d2fe; border-radius:999px;">
                        {{ $reading }}分
                      </span>
                    @endif

                    {{-- おすすめ --}}
                    @if($isFeatured)
                      <span style="margin-left:6px; padding:2px 8px; background:#fff7ed; border:1px solid #fed7aa; border-radius:999px; color:#9a3412;">
                        おすすめ
                      </span>
                    @endif
                  </div>
                </div>
              </div>
            </li>
          @endforeach
        </ul>

        <div style="margin-top:16px; text-align:center;">
          <a href="{{ route('front.posts.index') }}"
             style="display:inline-block; padding:8px 14px; background:#059669; color:#fff; border-radius:8px; text-decoration:none;">
            すべての記事を見る
          </a>
        </div>
      </section>
    @endif
    {{-- === /LATEST_POSTS_BLOCK === --}}
  </main>

</body>
</html>
