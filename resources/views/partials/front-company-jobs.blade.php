{{-- resources/views/partials/front-company-jobs.blade.php --}}
@once
@php
  // Controllerから来ていれば優先。無ければ下でフォールバック取得。
  $companies = collect($companiesTop ?? []);
  $jobs      = collect($jobsTop ?? []);

  /* ===== Companies fallback (Eloquent優先) ===== */
  if ($companies->isEmpty()) {
    try {
      if (class_exists(\App\Models\Company::class) && \Schema::hasTable('companies')) {
        // ★ Eloquent 経由で取る → getLogoUrlAttribute が効く
        $q = \App\Models\Company::query();
        if (\Schema::hasColumn('companies','is_published')) $q->where('is_published', 1);
        if (\Schema::hasColumn('companies','published'))    $q->where('published', 1);
        if (\Schema::hasColumn('companies','status'))       $q->where('status', 'published');
        if (\Schema::hasColumn('companies','deleted_at'))   $q->whereNull('deleted_at');
        // デモ除外（あれば）
        if (\Schema::hasColumn('companies','name')) {
          $q->where('name','not like','%デモ%')->where('name','not like','%demo%');
        }
        if (\Schema::hasColumn('companies','email')) $q->where('email','not like','%@example.%');
        if (\Schema::hasColumn('companies','is_demo')) {
          $q->where(function($qq){ $qq->whereNull('is_demo')->orWhere('is_demo',0); });
        }
        $companies = $q->orderByDesc('id')->limit(6)->get();
      } elseif (\Schema::hasTable('company_profiles')) {
        // 互換: プロファイルテーブルのみの場合はQBで
        $q = \DB::table('company_profiles');
        if (\Schema::hasColumn('company_profiles','deleted_at')) $q->whereNull('deleted_at');
        $companies = $q->orderByDesc('id')->limit(6)->get();
      }
    } catch (\Throwable $e) { $companies = collect(); }
  }

  /* ===== Jobs fallback ===== */
  if ($jobs->isEmpty()) {
    try {
      $jtable = \Schema::hasTable('jobs') ? 'jobs'
              : (\Schema::hasTable('recruit_jobs') ? 'recruit_jobs' : null);
      if ($jtable) {
        $jq = \DB::table($jtable);
        if (\Schema::hasColumn($jtable,'is_published')) $jq->where('is_published', 1);
        if (\Schema::hasColumn($jtable,'published'))    $jq->where('published', 1);
        if (\Schema::hasColumn($jtable,'status'))       $jq->where('status', 'published');
        if (\Schema::hasColumn($jtable,'deleted_at'))   $jq->whereNull('deleted_at');
        if (\Schema::hasColumn($jtable,'title')) {
          $jq->where('title','not like','%デモ%')->where('title','not like','%demo%');
        }
        if (\Schema::hasColumn($jtable,'is_demo')) {
          $jq->where(function($qq){ $qq->whereNull('is_demo')->orWhere('is_demo',0); });
        }
        $jobs = $jq->orderByDesc('id')->limit(6)->get();
      }
    } catch (\Throwable $e) { $jobs = collect(); }
  }

  // 一覧URL（名前付きルートが無い環境でも壊れないように）
  $companyIndexUrl = \Route::has('front.company.index') ? route('front.company.index') : url('/company');
  $jobIndexUrl     = \Route::has('front.jobs.index')     ? route('front.jobs.index')     : url('/recruit_jobs');
@endphp

<div style="max-width: 920px; margin: 24px auto 0;">
  <div style="background:#fff;border:1px solid #eef2f7;border-radius:6px;overflow:hidden;">

    {{-- 企業 --}}
    <div style="padding:16px 16px 8px; display:flex; justify-content:space-between; align-items:center;">
      <h2 style="margin:0;font-weight:600;">企業</h2>
      <a href="{{ $companyIndexUrl }}" style="font-size:12px;color:#6366f1;text-decoration:none;">企業一覧へ</a>
    </div>

    @if($companies->isEmpty())
      <div style="padding:0 16px 16px; color:#64748b; font-size:13px;">企業データは準備中です。</div>
    @else
      <ul style="list-style:none;margin:0;padding:0 0 8px;">
        @foreach($companies as $c)
          @php
            $name = $c->name ?? $c->company_name ?? '(名称未設定)';
            $slug = $c->slug ?? null;

            // 詳細URL（slug優先→id）
            $param   = ($c->slug ?? null) ?: ($c->id ?? null);
            $showUrl = $param
              ? ( \Route::has('front.company.show') ? route('front.company.show', $param) : url('/company/'.$param) )
              : '#';

            /* --- ロゴURL：まずは Eloquent アクセサの値を最優先（= 詳細ページと同じ結果） --- */
            $logoUrl = null;
            if ($c instanceof \Illuminate\Database\Eloquent\Model) {
              // getLogoUrlAttribute があれば $c->logo_url で取得できる
              $logoUrl = $c->getAttribute('logo_url') ?? null;
              // プロパティ直参照（snake/camel どちらも見る）
              if (!$logoUrl) $logoUrl = $c->logo_url ?? $c->logoUrl ?? null;
            }

            // アクセサが無い/効かない場合は既存カラムから推測
            if (!$logoUrl) {
              $candidates = [
                'logo','logo_path','logo_url',
                'image','image_path','image_url',
                'thumbnail','thumbnail_path','thumbnail_url',
                'thumb','avatar','avatar_path','avatar_url',
                'icon','icon_path','icon_url','cover','cover_image'
              ];
              foreach ($candidates as $col) {
                if (!empty($c->$col ?? null)) { $logoUrl = (string)$c->$col; break; }
              }
              // パス正規化
              $normalize = function (?string $p): ?string {
                if (!$p) return null;
                $p = trim($p);
                if (preg_match('#^https?://#i',$p) || str_starts_with($p,'/')) return $p;
                if (str_starts_with($p,'public/')) {
                  $rel = substr($p,7);
                  return \Storage::disk('public')->url($rel);
                }
                if (\Storage::disk('public')->exists($p)) return \Storage::disk('public')->url($p);
                if (file_exists(public_path($p))) return asset($p);
                return $p;
              };
              $logoUrl = $normalize($logoUrl);
            }

            // それでも無ければ slug から推測
            if (!$logoUrl && $slug) {
              $dirs = ['logos','company_logos','images/logos','images/company','uploads/logos'];
              $exts = ['png','jpg','jpeg','webp','svg'];
              foreach ($dirs as $d) {
                foreach ($exts as $ext) {
                  $p = "$d/$slug.$ext";
                  if (\Storage::disk('public')->exists($p)) { $logoUrl = \Storage::disk('public')->url($p); break 2; }
                  if (file_exists(public_path($p)))         { $logoUrl = asset($p);                         break 2; }
                }
              }
            }

            // 最終フォールバック：頭文字SVG
            if (!$logoUrl) {
              $initial = strtoupper(mb_substr($name, 0, 1, 'UTF-8'));
              $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">'
                   . '<rect width="48" height="48" rx="8" fill="#E5E7EB"/>'
                   . '<text x="50%" y="58%" text-anchor="middle" font-family="system-ui,-apple-system,Segoe UI,Roboto" '
                   . 'font-size="22" fill="#6B7280">'.$initial.'</text></svg>';
              $logoUrl = 'data:image/svg+xml;utf8,'.rawurlencode($svg);
            }
          @endphp

          <li style="border-top:1px solid #eef2f7;">
            <a href="{{ $showUrl }}" style="display:flex;gap:12px;align-items:center;padding:12px 16px;text-decoration:none;color:inherit;">
              <div style="width:48px;height:48px;background:#f1f5f9;border-radius:8px;display:grid;place-items:center;overflow:hidden;flex:0 0 auto;">
                <img src="{{ $logoUrl }}" alt="{{ $name }}" style="max-width:100%;max-height:100%;object-fit:contain;" loading="lazy" onerror="this.style.display='none'">
              </div>
              <div style="min-width:0;">
                <div style="font-weight:600;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $name }}</div>
              </div>
            </a>
          </li>
        @endforeach
      </ul>
    @endif

    {{-- 求人 --}}
    <div style="padding:8px 16px; display:flex; justify-content:space-between; align-items:center;">
      <h2 style="margin:0;font-weight:600;">求人</h2>
      <a href="{{ $jobIndexUrl }}" style="font-size:12px;color:#6366f1;text-decoration:none;">求人一覧へ</a>
    </div>

    @if($jobs->isEmpty())
      <div style="padding:0 16px 16px; color:#64748b; font-size:13px;">求人データは準備中です。</div>
    @else
      <ul style="list-style:none;margin:0;padding:0 0 16px;">
        @foreach($jobs as $j)
          @php
            $slugRaw = (string)($j->slug ?? '');
            if (\Illuminate\Support\Str::startsWith($slugRaw, ['/jobs/','jobs/'])) {
              $slugRaw = ltrim(preg_replace('#^/?jobs/#','',$slugRaw),'/');
            }
            $p = $slugRaw !== '' ? $slugRaw : ($j->id ?? null);
            $showUrl = $p
              ? ( \Route::has('front.jobs.show') ? route('front.jobs.show', $p) : url('/recruit_jobs/'.$p) )
              : '#';

            $thumb = $j->thumbnail_url ?? $j->thumbnail_path ?? $j->thumbnail ?? null;
            if ($thumb && !\Illuminate\Support\Str::startsWith($thumb, ['http://','https://','/'])) {
              if (str_starts_with($thumb,'public/')) {
                $thumb = \Storage::disk('public')->url(substr($thumb,7));
              } elseif (\Storage::disk('public')->exists($thumb)) {
                $thumb = \Storage::disk('public')->url($thumb);
              } elseif (file_exists(public_path($thumb))) {
                $thumb = asset($thumb);
              }
            }
          @endphp

          <li style="border-top:1px solid #eef2f7;">
            <a href="{{ $showUrl }}" style="display:flex;gap:12px;align-items:center;padding:12px 16px;text-decoration:none;color:inherit;">
              <div style="width:64px;height:40px;background:#f1f5f9;border-radius:6px;overflow:hidden;flex:0 0 auto;">
                @if($thumb)
                  <img src="{{ $thumb }}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy" onerror="this.style.display='none'">
                @endif
              </div>
              <div style="min-width:0;">
                <div style="font-weight:600;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $j->title ?? '(無題)' }}
                </div>
              </div>
            </a>
          </li>
        @endforeach
      </ul>
    @endif

  </div>
</div>
@endonce
