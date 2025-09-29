{{-- resources/views/partials/front-company-jobs.blade.php --}}
@php
  // controller から渡される前提
  $companies = collect($companiesTop ?? []);
  $jobs      = collect($jobsTop ?? []);

  $companyIndexUrl = \Illuminate\Support\Facades\Route::has('front.company.index')
      ? route('front.company.index') : url('/company');
  $jobIndexUrl     = \Illuminate\Support\Facades\Route::has('front.jobs.index')
      ? route('front.jobs.index') : url('/jobs');
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
            // 表示用リンクキー
            $param   = ($c->slug ?? null) ?: ($c->id ?? null);
            $showUrl = $param
              ? ( \Illuminate\Support\Facades\Route::has('front.company.show')
                  ? route('front.company.show', ['company' => $param])
                  : url('/company/'.$param) )
              : '#';

            // ★ ロゴURLの決定：controller 付与の logoUrl を最優先
            $logoUrl = is_array($c) ? ($c['logoUrl'] ?? null) : ($c->logoUrl ?? null);

            // 保険：logoUrl が無い環境でも既存カラムから解決（/storage・public直下対応）
            if (empty($logoUrl)) {
              $raw = null;
              foreach (['logo','logo_path','image','thumbnail','thumb','main_image','cover','cover_image'] as $col) {
                if (!empty($c->$col ?? null)) { $raw = $c->$col; break; }
              }
              if ($raw) {
                if (preg_match('#^https?://#', $raw) || str_starts_with($raw, '/')) {
                  $logoUrl = $raw;
                } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($raw)) {
                  $logoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($raw);
                } elseif (file_exists(public_path($raw))) {
                  $logoUrl = asset($raw);
                }
              }
            }

            // フォールバック
            if (empty($logoUrl)) {
              $logoUrl = asset('images/noimage.svg');
            }
          @endphp

          <li style="border-top:1px solid #eef2f7;">
            <a href="{{ $showUrl }}" style="display:flex;gap:12px;align-items:center;padding:12px 16px;text-decoration:none;color:inherit;">
              <div style="width:48px;height:48px;background:#f1f5f9;border-radius:8px;display:grid;place-items:center;overflow:hidden;flex:0 0 auto;">
                <img src="{{ $logoUrl }}" alt="" style="max-width:100%;max-height:100%;object-fit:contain;" loading="lazy">
              </div>
              <div style="min-width:0;">
                <div style="font-weight:600;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $c->name ?? '(名称未設定)' }}
                </div>
                @if(!empty($c->location))
                  <div style="margin-top:2px;font-size:12px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    {{ $c->location }}
                  </div>
                @endif
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
            if (\Illuminate\Support\Str::startsWith($slugRaw, ['/jobs/', 'jobs/'])) {
              $slugRaw = ltrim(preg_replace('#^/?jobs/#', '', $slugRaw), '/');
            }
            $p = $slugRaw !== '' ? $slugRaw : ($j->id ?? null);
            $showUrl = $p
              ? ( \Illuminate\Support\Facades\Route::has('front.jobs.show')
                  ? route('front.jobs.show', $p)
                  : url('/jobs/'.$p) )
              : '#';

            $thumb = $j->thumbnail_path ?? null;
            if ($thumb && !\Illuminate\Support\Str::startsWith($thumb, ['http://','https://','/'])) {
              $thumb = asset($thumb);
            }
          @endphp
          <li style="border-top:1px solid #eef2f7;">
            <a href="{{ $showUrl }}" style="display:flex;gap:12px;align-items:center;padding:12px 16px;text-decoration:none;color:inherit;">
              <div style="width:64px;height:40px;background:#f1f5f9;border-radius:6px;overflow:hidden;flex:0 0 auto;">
                @if($thumb)
                  <img src="{{ $thumb }}" alt="" style="width:100%;height:100%;object-fit:cover;" loading="lazy"
                       onerror="this.style.display='none'">
                @endif
              </div>
              <div style="min-width:0;">
                <div style="font-weight:600;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  {{ $j->title ?? '(無題)' }}
                </div>
                <div style="margin-top:2px;display:flex;gap:6px;flex-wrap:wrap;font-size:12px;color:#64748b;">
                  @if(!empty($j->location))        <span>{{ $j->location }}</span>@endif
                  @if(!empty($j->employment_type)) <span>{{ $j->employment_type }}</span>@endif
                  @if(!empty($j->salary_label))    <span>{{ $j->salary_label }}</span>@endif
                </div>
              </div>
            </a>
          </li>
        @endforeach
      </ul>
    @endif

  </div>
</div>
