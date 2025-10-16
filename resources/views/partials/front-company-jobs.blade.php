{{-- resources/views/partials/front-company-jobs.blade.php --}}
@once
@php
  $companies = collect($companiesTop ?? []);
  $jobs      = collect($jobsTop ?? []);

  // デバッグ表示トグル（URL に ?dbg=1 を付けると表示）
  $DBG = request()->boolean('dbg');

  /**
   * /storage 配下の公開URLを「安全に」組み立てる（日本語/スペースを rawurlencode）
   */
  $storage_url_enc = function (string $rel): string {
    $rel   = str_replace('\\', '/', trim($rel, '/'));
    $parts = array_map('rawurlencode', array_filter(explode('/', $rel), 'strlen'));
    return asset('storage/'.implode('/', $parts));
  };

  // ===== URL正規化（強化版） =====
  $normalize = function (?string $p) use ($storage_url_enc): ?string {
    if (!$p) return null;
    $p = trim($p);

    // 完全URL
    if (preg_match('#^https?://#i', $p)) return $p;

    // 物理パス → /storage/...
    if (preg_match('#/storage/app/public/(.+)$#iu', $p, $m)) {
      return $storage_url_enc($m[1]);
    }

    // ルート相対
    if (\Illuminate\Support\Str::startsWith($p, '/')) return $p;

    // 区切り統一
    $p = str_replace('\\', '/', $p);

    // 明示的に recruit_jobs/... は /storage/recruit_jobs/... に（exists() に頼らない）
    if (\Illuminate\Support\Str::startsWith($p, 'recruit_jobs/')) {
      return $storage_url_enc($p);
    }

    // public/... → /storage/...
    if (\Illuminate\Support\Str::startsWith($p, 'public/')) {
      $rel = ltrim(\Illuminate\Support\Str::after($p, 'public/'), '/');
      return $storage_url_enc($rel);
    }

    // storage/...（既に公開パス相対）→ そのまま asset
    if (\Illuminate\Support\Str::startsWith($p, 'storage/')) {
      return asset($p);
    }

    // public ディスクに存在する相対パス（日本語名にも対応）
    try {
      if (\Illuminate\Support\Facades\Storage::disk('public')->exists($p)) {
        return $storage_url_enc($p);
      }
    } catch (\Throwable $e) {
      // exists() が失敗した場合は下の楽観フォールバックへ
    }

    // 画像拡張子っぽい相対パスは楽観的に /storage/... を返す
    if (preg_match('#\.(png|jpe?g|webp|gif|svg)$#iu', $p)) {
      return $storage_url_enc($p);
    }

    // /public 直置き
    if (file_exists(public_path($p))) return asset($p);

    // ドメイン付きの /storage/... を抽出
    if (preg_match('#https?://[^/]+/(storage/.+)$#iu', $p, $m)) return '/'.$m[1];

    return null;
  };

  // ===== 本文などから最初の画像URLを抜く（強化版） =====
  $extractFirstImg = function ($raw) {
    if (!$raw) return null;
    $s = is_string($raw) ? $raw : (is_array($raw) ? json_encode($raw, JSON_UNESCAPED_UNICODE) : strval($raw));
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (preg_match('#<img[^>]+(?:data-src|src)=["\']([^"\']+)["\']#i', $s, $m)) return $m[1];      // img src/data-src
    if (preg_match('#<(?:source|img)[^>]+srcset=["\']([^"\']+)["\']#i', $s, $m)) {                 // srcset
      $first = preg_split('/\s|,/', trim($m[1]))[0] ?? null; if ($first) return $first;
    }
    if (preg_match('#!\[[^\]]*\]\(([^)]+)\)#', $s, $m)) return $m[1];                               // Markdown
    if (preg_match('#"src"\s*:\s*"([^"]+\.(?:png|jpe?g|gif|webp|svg))"#i', $s, $m)) return $m[1];  // JSON
    if (preg_match('#background-image\s*:\s*url\((["\']?)([^)\'"]+)\1\)#i', $s, $m)) return $m[2]; // CSS
    return null;
  };

  // ===== Companies フォールバック =====
  if ($companies->isEmpty()) {
    try {
      if (class_exists(\App\Models\Company::class) && \Schema::hasTable('companies')) {
        $q = \App\Models\Company::query();
        if (\Schema::hasColumn('companies','is_published')) $q->where('is_published', 1);
        if (\Schema::hasColumn('companies','published'))    $q->where('published', 1);
        if (\Schema::hasColumn('companies','status'))       $q->where('status', 'published');
        if (\Schema::hasColumn('companies','deleted_at'))   $q->whereNull('deleted_at');
        if (\Schema::hasColumn('companies','name'))         $q->where('name','not like','%デモ%')->where('name','not like','%demo%');
        $companies = $q->orderByDesc('id')->limit(6)->get();
      }
    } catch (\Throwable $e) { $companies = collect(); }
  }

  // ===== Jobs フォールバック =====
  if ($jobs->isEmpty()) {
    try {
      $jtable = \Schema::hasTable('jobs') ? 'jobs' : (\Schema::hasTable('recruit_jobs') ? 'recruit_jobs' : null);
      if ($jtable) {
        $jq = \DB::table($jtable);
        if (\Schema::hasColumn($jtable,'is_published')) $jq->where('is_published', 1);
        if (\Schema::hasColumn($jtable,'published'))    $jq->where('published', 1);
        if (\Schema::hasColumn($jtable,'status'))       $jq->where('status', 'published');
        if (\Schema::hasColumn($jtable,'deleted_at'))   $jq->whereNull('deleted_at');
        $jobs = $jq->orderByDesc('id')->limit(12)->get();
      }
    } catch (\Throwable $e) { $jobs = collect(); }
  }

  $companyIndexUrl = \Route::has('front.company.index') ? route('front.company.index') : url('/company');
  $jobIndexUrl     = \Route::has('front.jobs.index')    ? route('front.jobs.index')    : url('/recruit_jobs');
@endphp

<div style="max-width: 920px; margin: 24px auto 0;">
  <div style="background:#fff;border:1px solid #eef2f7;border-radius:6px;overflow:hidden;">

    {{-- 企業 --}}
    <div style="padding:16px 16px 8px; display:flex; justify-content:space-between; align-items:center;">
      <h2 style="margin:0;font-weight:600;">企業</h2>
      <a href="{{ $companyIndexUrl }}" style="font-size:12px;color:#6366f1;text-decoration:none;">企業一覧へ</a>
    </div>

    <ul style="list-style:none;margin:0;padding:0 0 8px;">
      @forelse($companies as $c)
        @php
          $name   = $c->name ?? $c->company_name ?? '(名称未設定)';
          $param  = $c->slug ?? $c->id ?? null;
          $showUrl= $param ? (\Route::has('front.company.show') ? route('front.company.show',$param) : url('/company/'.$param)) : '#';

          $logo = null; $logoFrom = null;
          foreach (['logo','logo_url','image','image_url','thumbnail','thumbnail_url'] as $col) {
            if (!empty($c->$col)) { $logo = $normalize($c->$col); $logoFrom = 'company.'.$col; if ($logo) break; }
          }
          if (!$logo) {
            $initial = strtoupper(mb_substr($name,0,1,'UTF-8'));
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" rx="8" fill="#E5E7EB"/><text x="50%" y="58%" text-anchor="middle" font-family="system-ui" font-size="22" fill="#6B7280">'.$initial.'</text></svg>';
            $logo = 'data:image/svg+xml;utf8,'.rawurlencode($svg);
            $logoFrom = 'fallback:svg';
          }
        @endphp
        <li style="border-top:1px solid #eef2f7;">
          <a href="{{ $showUrl }}" style="display:flex;gap:12px;align-items:center;padding:12px 16px;text-decoration:none;color:inherit;">
            <div style="width:48px;height:48px;background:#f1f5f9;border-radius:8px;display:grid;place-items:center;overflow:hidden;">
              <img src="{{ $logo }}" alt="{{ $name }}" style="width:100%;height:100%;object-fit:contain">
            </div>
            <div style="min-width:0;">
              <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $name }}</div>
              @if($DBG)<div style="font-size:11px;color:#64748b;">logoFrom: {{ $logoFrom }}</div>@endif
            </div>
          </a>
        </li>
      @empty
        <li style="padding:0 16px 16px;color:#64748b;font-size:13px;">企業データは準備中です。</li>
      @endforelse
    </ul>

    {{-- 求人 --}}
    <div style="padding:8px 16px; display:flex; justify-content:space-between; align-items:center;">
      <h2 style="margin:0;font-weight:600;">求人</h2>
      <a href="{{ $jobIndexUrl }}" style="font-size:12px;color:#6366f1;text-decoration:none;">求人一覧へ</a>
    </div>

    <ul style="list-style:none;margin:0;padding:0 0 16px;">
      @forelse($jobs as $j)
        @php
          // 詳細URL
          $slugRaw = (string)($j->slug ?? '');
          if (\Illuminate\Support\Str::startsWith($slugRaw, ['/jobs/','jobs/'])) {
            $slugRaw = ltrim(preg_replace('#^/?jobs/#','',$slugRaw),'/');
          }
          $param   = $slugRaw !== '' ? $slugRaw : ($j->id ?? null);
          $showUrl = $param ? (\Route::has('front.jobs.show') ? route('front.jobs.show',$param) : url('/recruit_jobs/'.$param)) : '#';

          // ===== サムネ選定順 =====
          $thumb = null; $thumbFrom = null;

          // 1) 本文から最初の画像
          foreach (['description','body','content_html','content','html','markdown','text'] as $cf) {
            if (!empty($j->$cf)) { $tmp = $extractFirstImg((string)$j->$cf); if ($tmp) { $thumb=$tmp; $thumbFrom='body:'.$cf; break; } }
          }
          $thumb = $normalize($thumb);

          // 2) サムネ候補カラム
          if (!$thumb) {
            foreach (['thumbnail_url','thumbnail_path','thumbnail','image','image_url','image_path','cover_image','cover_image_url'] as $col) {
              if (!empty($j->$col)) { $tmp = $normalize($j->$col); if ($tmp) { $thumb=$tmp; $thumbFrom='column:'.$col; break; } }
            }
          }

          // 3) 会社ロゴ
          if (!$thumb) {
            $company = null;
            if ($j instanceof \Illuminate\Database\Eloquent\Model) {
              $company = $j->getRelation('company') ?? null;
            }
            if (!$company && !empty($j->company_id) && class_exists(\App\Models\Company::class)) {
              try { $company = \App\Models\Company::find($j->company_id); } catch (\Throwable $e) {}
            }
            if ($company) {
              foreach (['logo','logo_url','image','image_url','thumbnail','thumbnail_url'] as $col) {
                if (!empty($company->$col)) { $tmp = $normalize($company->$col); if ($tmp) { $thumb=$tmp; $thumbFrom='company:'.$col; break; } }
              }
            }
          }

          // 4) 最終フォールバック：レコード全体走査
          if (!$thumb) {
            $blob = json_encode($j, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
              if ($try) { $thumb = $try; $thumbFrom = 'scan'; break; }
            }
          }

          // 5) No image（SVG）
          if (!$thumb) {
            $initial = strtoupper(mb_substr((string)($j->title ?? 'J'),0,1,'UTF-8'));
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="60" viewBox="0 0 96 60"><rect width="96" height="60" rx="6" fill="#F1F5F9"/><text x="50%" y="60%" text-anchor="middle" font-size="22" fill="#94A3B8">'.$initial.'</text></svg>';
            $thumb = 'data:image/svg+xml;utf8,'.rawurlencode($svg);
            $thumbFrom = 'fallback:svg';
          }

          // デバッグ用サマリ
          $debugSummary = '';
          if ($DBG) {
            $firstBody = '';
            foreach (['description','body','content_html','content','html','markdown','text'] as $cf) {
              if (!empty($j->$cf)) { $firstBody = mb_substr(strip_tags((string)$j->$cf), 0, 200, 'UTF-8'); break; }
            }
            $debugSummary = "from={$thumbFrom} | thumb={$thumb} | bodySnippet=".($firstBody ?: '(none)');
          }
        @endphp

        <li style="border-top:1px solid #eef2f7;">
          <a href="{{ $showUrl }}" style="display:flex;gap:12px;align-items:center;padding:12px 16px;text-decoration:none;color:inherit;">
            <div style="width:64px;height:40px;background:#f1f5f9;border-radius:6px;overflow:hidden;flex:0 0 auto;">
              <img src="{{ $thumb }}" alt="" style="width:100%;height:100%;object-fit:cover;object-position:center">
            </div>
            <div style="min-width:0;">
              <div style="font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $j->title ?? '(無題)' }}</div>
              @if($DBG)<div style="font-size:11px;color:#64748b;">{{ $debugSummary }}</div>@endif
            </div>
          </a>
        </li>
      @empty
        <li style="padding:0 16px 16px;color:#64748b;font-size:13px;">求人データは準備中です。</li>
      @endforelse
    </ul>

  </div>
</div>
@endonce
