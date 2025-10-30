@extends('front.layout')
@section('title','Home')

@section('content')
@php
  $items = collect($latest ?? ($posts ?? []));

  // URL正規化
  $normalize = function (?string $p): ?string {
    if (!$p) return null;
    $p = trim($p);
    if (preg_match('#^https?://#i', $p)) return $p;
    if (preg_match('#/storage/app/public/(.+)$#', $p, $m)) return asset('storage/'.$m[1]);
    if (\Illuminate\Support\Str::startsWith($p, '/')) return $p;
    if (\Illuminate\Support\Str::startsWith($p, 'storage/')) return asset($p);
    if (\Illuminate\Support\Str::startsWith($p, 'public/')) {
      $rel = ltrim(\Illuminate\Support\Str::after($p, 'public/'), '/');
      return asset('storage/'.$rel);
    }
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($p)) return asset('storage/'.ltrim($p,'/'));
    if (file_exists(public_path($p))) return asset($p);
    if (preg_match('#https?://[^/]+/(storage/.+)$#i', $p, $m)) return '/'.$m[1];
    return $p;
  };

  // 本文から画像抽出（強化版）
  $extractFirstImg = function ($raw) {
    if (!$raw) return null;
    $s = is_string($raw) ? $raw : (is_array($raw) ? json_encode($raw, JSON_UNESCAPED_UNICODE) : strval($raw));
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (preg_match('#<img[^>]+(?:data-src|src)=["\']([^"\']+)["\']#i', $s, $m)) return $m[1];
    if (preg_match('#<(?:source|img)[^>]+srcset=["\']([^"\']+)["\']#i', $s, $m)) {
      $first = preg_split('/\s|,/', trim($m[1]))[0] ?? null; if ($first) return $first;
    }
    if (preg_match('#!\[[^\]]*\]\(([^)]+)\)#', $s, $m)) return $m[1];
    if (preg_match('#"src"\s*:\s*"([^"]+\.(?:png|jpe?g|gif|webp|svg))"#i', $s, $m)) return $m[1];
    if (preg_match('#background-image\s*:\s*url\((["\']?)([^)\'"]+)\1\)#i', $s, $m)) return $m[2];
    return null;
  };

  // 日付
  $dateTextOf = function ($post) {
    try {
      $raw = $post->published_at ?? $post->created_at ?? null;
      return $raw ? \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d') : '';
    } catch (\Throwable $e) { return ''; }
  };

  // サムネ
  $thumbOf = function ($post) use ($normalize, $extractFirstImg) {
    $thumb = null;
    foreach ([
      'thumbnail_url','thumbnail_path','thumbnail',
      'cover_image','cover_image_path','cover_image_url',
      'image','image_path','image_url',
      'featured_image','featured_image_url','featured_image_path',
    ] as $col) {
      if (!empty($post->{$col})) { $thumb = $post->{$col}; break; }
    }
    if (!$thumb) {
      foreach (['content_html','content','body','html','text','markdown','content_rendered'] as $cf) {
        if (!empty($post->{$cf})) { $thumb = $extractFirstImg((string)$post->{$cf}); if ($thumb) break; }
      }
    }
    $thumb = $normalize($thumb);
    if ($thumb) return $thumb;

    // フォールバックSVG
    $title = (string)($post->title ?? 'P');
    $initial = strtoupper(mb_substr($title, 0, 1, 'UTF-8'));
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">'
         . '<rect width="640" height="360" rx="16" fill="#F1F5F9"/>'
         . '<text x="50%" y="58%" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,Roboto" '
         . 'font-size="160" fill="#94A3B8">'.$initial.'</text></svg>';
    return 'data:image/svg+xml;utf8,'.rawurlencode($svg);
  };

  // URL
  $postUrlOf = function ($post) {
    $param = $post->slug ?? $post->id ?? null;
    return (\Route::has('front.posts.show') && $param) ? route('front.posts.show', $param) : '#';
  };

  // HERO と残り
  $hero = $items->take(4);
  $rest = $items->slice(4)->values();
@endphp



{{-- =========================
     「最新記事」（HEROを除いた残り）
========================= --}}
<div class="flex items-center justify-between mt-6 mb-4">
  <h2 class="text-xl font-bold">最新記事</h2>
  @if (\Route::has('front.posts.index'))
    <a href="{{ \Route::has('front.posts.index') ? route('front.posts.index') : '#' }}"
       class="inline-flex items-center justify-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-200 shadow-sm hover:bg-gray-50 active:translate-y-[1px] transition">
      もっと見る
    </a>
  @endif
</div>



{{-- =========================
     HERO（記事4つ）＋右サイド説明
========================= --}}
@if ($hero->count())
<section class="mb-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
  {{-- 左：記事カード（2列） --}}
  <div class="lg:col-span-2 grid sm:grid-cols-2 gap-6">
    @foreach ($hero as $post)
      <a href="{{ $postUrlOf($post) }}"
         class="group block rounded-2xl bg-white border shadow-sm hover:shadow-md transition">
        <div class="aspect-[16/9] overflow-hidden rounded-t-2xl">
          <img src="{{ $thumbOf($post) }}" alt="{{ $post->title ?? '' }}"
               class="w-full h-full object-cover group-hover:scale-[1.02] transition">
        </div>
        <div class="p-4">
          <p class="text-xs text-gray-500">{{ $dateTextOf($post) }}</p>
          <h3 class="mt-1 font-semibold leading-snug line-clamp-2">{{ $post->title ?? '(無題)' }}</h3>
        </div>
      </a>
    @endforeach
  </div>

  {{-- 右：説明ボックス --}}
<aside class="rounded-2xl p-6 border text-white" style="background-color:#C23A41;">
  <h2 class="text-lg font-bold text-white">ドウソコ って何？</h2>
  <p class="mt-2 text-sm leading-relaxed text-white">
    地方発・地方で働く、“等身大の自分に合う生き方”を選ぶためのメディア。<br>
    仕事・暮らし・人間関係など、就職/転職だけじゃないライフデザインを応援します。
  </p>
  <a href="{{ \Route::has('front.posts.index') ? route('front.posts.index') : '#' }}"
     class="mt-4 inline-flex items-center justify-center rounded-full bg-white text-[#C23A41] px-3 py-1.5 text-xs font-semibold border border-white shadow-sm hover:opacity-90 active:translate-y-[1px] transition">
    もっと見る
  </a>
</aside>
</section>
@endif



@if ($rest->count())
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($rest as $post)
      @php
        $postUrl = $postUrlOf($post);
        $thumb   = $thumbOf($post);
        $dateText= $dateTextOf($post);
        $cats = [];
        if (isset($post->categories) && (is_array($post->categories) || $post->categories instanceof \Illuminate\Support\Collection)) {
          foreach ($post->categories as $c) {
            $name  = $c->name ?? (is_array($c) ? ($c['name'] ?? null) : null);
            $slugC = $c->slug ?? (is_array($c) ? ($c['slug'] ?? null) : null);
            if ($name) $cats[] = ['name'=>$name,'slug'=>$slugC];
          }
        }
      @endphp

      <article class="rounded-2xl overflow-hidden bg-white border shadow-sm hover:shadow-md transition">
        <a href="{{ $postUrl }}" class="block">
          <div class="aspect-[16/9] bg-gray-100 overflow-hidden">
            <img src="{{ $thumb }}" alt="{{ $post->title ?? '' }}"
                 class="w-full h-full object-cover hover:scale-[1.01] transition">
          </div>
          <div class="p-4">
            <h3 class="font-semibold text-base leading-snug line-clamp-2">{{ $post->title ?? '(無題)' }}</h3>
            @if ($dateText)
              <p class="text-xs text-gray-500 mt-1">{{ $dateText }}</p>
            @endif
            @if ($cats)
              <div class="mt-2 text-[11px] flex gap-2 flex-wrap">
                @foreach ($cats as $c)
                  @php
                    $catUrl = ($c['slug'] ?? null) && \Route::has('front.categories.show')
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

{{-- =========================
     新着の求人企業（ロゴグリッド）
========================= --}}
@php
  $newCompanies = collect();
  try {
      if (class_exists(\App\Models\Company::class)) {
          $logoCols = ['logo_url','logo','image','image_url','thumbnail','thumbnail_url','eyecatch_url'];
          $newCompanies = \App\Models\Company::query()
              ->orderByDesc('id')->limit(8)->get()->map(function($c) use ($logoCols, $normalize) {
                  $logo = null;
                  foreach ($logoCols as $col) if (!empty($c->{$col})) { $logo = $c->{$col}; break; }
                  return [
                      'id'   => $c->id,
                      'slug' => $c->slug ?? null,
                      'name' => $c->name ?? '企業名未設定',
                      'logo' => $normalize($logo),
                  ];
              });
      }
  } catch (\Throwable $e) {}
@endphp

@if ($newCompanies->count())
<section class="mt-12">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-bold">新着の求人企業</h2>
    @if (\Route::has('front.company.index'))
      <a href="{{ route('front.company.index') }}"
         class="inline-flex items-center justify-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-200 shadow-sm hover:bg-gray-50 active:translate-y-[1px] transition">
        もっと見る
      </a>
    @endif
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
    @foreach($newCompanies as $co)
      @php
        $coUrl = (\Route::has('front.company.show') && ($co['slug'] ?? $co['id']))
          ? route('front.company.show', $co['slug'] ?? $co['id'])
          : '#';
      @endphp
      <a href="{{ $coUrl }}" class="group rounded-2xl bg-white border shadow-sm hover:shadow-md transition p-5 flex items-center justify-center">
        @if($co['logo'])
          <img src="{{ $co['logo'] }}" alt="{{ $co['name'] }}" class="max-h-12 w-auto object-contain opacity-90 group-hover:opacity-100 transition">
        @else
          <span class="text-sm text-gray-500">{{ $co['name'] }}</span>
        @endif
      </a>
    @endforeach
  </div>
</section>
@endif

{{-- =========================
     新着の求人（横並びカード）
========================= --}}
@php
  $newJobs = collect();
  try {
      if (class_exists(\App\Models\Job::class)) {
          $imgCols = ['image_url','image','thumbnail_url','thumbnail','eyecatch_url','cover_image','cover_image_url'];
          $newJobs = \App\Models\Job::query()
              ->with('company')
              ->orderByDesc('id')->limit(8)->get()->map(function($j) use ($imgCols, $normalize) {
                  $img = null;
                  foreach ($imgCols as $col) if (!empty($j->{$col})) { $img = $j->{$col}; break; }
                  return [
                      'id'      => $j->id,
                      'slug'    => $j->slug ?? null,
                      'title'   => $j->title ?? '求人タイトル',
                      'company' => optional($j->company)->name ?? '株式会社○○○○',
                      'image'   => $normalize($img),
                  ];
              });
      }
  } catch (\Throwable $e) {}
@endphp

@if ($newJobs->count())
<section class="mt-12">
  <div class="flex items-center justify-between mb-4">
    <h2 class="text-xl font-bold">新着の求人</h2>
    @if (\Route::has('front.jobs.index'))
      <a href="{{ route('front.jobs.index') }}"
         class="inline-flex items-center justify-center rounded-full bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 border border-gray-200 shadow-sm hover:bg-gray-50 active:translate-y-[1px] transition">
        もっと見る
      </a>
    @endif
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @foreach ($newJobs as $job)
      @php
        $jobUrl = (\Route::has('front.jobs.show') && ($job['slug'] ?? $job['id']))
          ? route('front.jobs.show', $job['slug'] ?? $job['id'])
          : '#';
      @endphp
      <a href="{{ $jobUrl }}" class="group rounded-2xl bg-white border shadow-sm hover:shadow-md transition overflow-hidden">
        <div class="aspect-[16/9] bg-gray-100 overflow-hidden">
          @if ($job['image'])
            <img src="{{ $job['image'] }}" class="w-full h-full object-cover group-hover:scale-[1.01] transition" alt="">
          @else
            <div class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
          @endif
        </div>
        <div class="p-4">
          <p class="text-[11px] text-rose-600 mb-1">募集職種カテゴリ</p>
          <h3 class="font-semibold leading-snug line-clamp-2">{{ $job['title'] }}</h3>
          <div class="mt-2 text-xs text-gray-500">{{ $job['company'] }}</div>
        </div>
      </a>
    @endforeach
  </div>
</section>
@endif



{{-- 1) まず無条件で出す（条件が原因か確認） --}}
@include('front.partials.cta-ambassador')



@endsection

<!-- HOMEMARK -->
