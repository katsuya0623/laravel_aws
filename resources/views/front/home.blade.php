@extends('front.layout')
@section('title','Home')

@section('content')
@php
  $items = collect($latest ?? ($posts ?? []));

  // URL正規化
  $normalize = function (?string $p): ?string {
    if (!$p) return null;
    $p = trim($p);

    if (preg_match('#^https?://#i', $p)) return $p;                                   // 完全URL
    if (preg_match('#/storage/app/public/(.+)$#', $p, $m)) return asset('storage/'.$m[1]); // 物理→公開
    if (\Illuminate\Support\Str::startsWith($p, '/')) return $p;                      // ルート相対
    if (\Illuminate\Support\Str::startsWith($p, 'storage/')) return asset($p);        // /storage/...
    if (\Illuminate\Support\Str::startsWith($p, 'public/')) {                          // public → /storage
      $rel = ltrim(\Illuminate\Support\Str::after($p, 'public/'), '/');
      return asset('storage/'.$rel);
    }
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($p))
      return asset('storage/'.ltrim($p,'/'));
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
      $first = preg_split('/\s|,/', trim($m[1]))[0] ?? null;
      if ($first) return $first;
    }
    if (preg_match('#!\[[^\]]*\]\(([^)]+)\)#', $s, $m)) return $m[1];
    if (preg_match('#"src"\s*:\s*"([^"]+\.(?:png|jpe?g|gif|webp|svg))"#i', $s, $m)) return $m[1];
    if (preg_match('#background-image\s*:\s*url\((["\']?)([^)\'"]+)\1\)#i', $s, $m)) return $m[2];
    return null;
  };
@endphp

<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">最新記事</h1>
  @if (\Route::has('front.posts.index'))
    <a class="text-indigo-600 hover:underline" href="{{ route('front.posts.index') }}">すべての記事を見る</a>
  @endif
</div>

@if ($items->count())
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($items as $post)
      @php
        $param   = $post->slug ?? $post->id ?? null;
        $postUrl = (\Route::has('front.posts.show') && $param) ? route('front.posts.show', $param) : '#';

        // 1) サムネ候補カラム
        $thumb = null;
        foreach ([
          'thumbnail_url','thumbnail_path','thumbnail',
          'cover_image','cover_image_path','cover_image_url',
          'image','image_path','image_url',
          'featured_image','featured_image_url','featured_image_path',
        ] as $col) {
          if (!empty($post->{$col})) { $thumb = $post->{$col}; break; }
        }

        // 2) 本文から抽出
        if (!$thumb) {
          foreach (['content_html','content','body','html','text','markdown','content_rendered'] as $cf) {
            if (!empty($post->{$cf})) { $thumb = $extractFirstImg((string)$post->{$cf}); if ($thumb) break; }
          }
        }

        // 3) 正規化
        $thumb = $normalize($thumb);

        // 4) 最終フォールバック：レコード全体走査
        if (!$thumb) {
          $blob = json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
          $cands = [];
          if (preg_match_all('#(?:data-src|src)\s*[:=]\s*["\']([^"\']+)["\']#i', $blob, $m)) $cands = array_merge($cands, $m[1]);
          if (preg_match_all('#url\((["\']?)([^)\'"]+)\1\)#i', $blob, $m))               $cands = array_merge($cands, $m[2]);
          if (preg_match_all('#https?://[^"\']+\.(?:png|jpe?g|webp|gif|svg)#i', $blob, $m)) $cands = array_merge($cands, $m[0]);
          if (preg_match_all('#/storage/[^"\']+\.(?:png|jpe?g|webp|gif|svg)#i', $blob, $m))  $cands = array_merge($cands, $m[0]);
          if (preg_match_all('#/storage/app/public/([^"\']+\.(?:png|jpe?g|webp|gif|svg))#i', $blob, $m)) {
            foreach ($m[1] as $rel) $cands[] = 'storage/'.$rel;
          }
          foreach ($cands as $cand) {
            $try = $normalize($cand);
            if ($try) { $thumb = $try; break; }
          }
        }

        // 5) 無ければSVG
        if (!$thumb) {
          $title = (string)($post->title ?? 'P');
          $initial = strtoupper(mb_substr($title, 0, 1, 'UTF-8'));
          $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360">'
               . '<rect width="640" height="360" rx="16" fill="#F1F5F9"/>'
               . '<text x="50%" y="58%" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,Roboto" '
               . 'font-size="160" fill="#94A3B8">'.$initial.'</text></svg>';
          $thumb = 'data:image/svg+xml;utf8,'.rawurlencode($svg);
        }

        // 日付
        $dateText = '';
        try {
          $raw = $post->published_at ?? $post->created_at ?? null;
          if ($raw) $dateText = \Illuminate\Support\Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable $e) {}

        // カテゴリ
        $cats = [];
        if (isset($post->categories) && (is_array($post->categories) || $post->categories instanceof \Illuminate\Support\Collection)) {
          foreach ($post->categories as $c) {
            $name  = $c->name ?? (is_array($c) ? ($c['name'] ?? null) : null);
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

{{-- 下の企業・求人 --}}
@includeIf('partials.front-company-jobs')
@endsection

<!-- HOMEMARK -->
