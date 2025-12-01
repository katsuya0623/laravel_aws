@extends('front.layout')

@section('title','求人一覧')

@section('content')


<style>
  /* ====== layout ====== */
  .jobs-wrap {
    max-width: 1060px;
    margin: 24px auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 20px
  }

  @media (max-width: 1000px) {
    .jobs-wrap {
      grid-template-columns: 1fr
    }
  }

  .card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fff
  }

  .shadow {
    box-shadow: 0 1px 2px rgba(0, 0, 0, .04)
  }

  .section-h {
    font-weight: 700;
    margin: 0 0 10px
  }

  .muted {
    color: #6b7280
  }

  .badge {
    display: inline-flex;
    gap: 6px;
    align-items: center;
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: .25rem .6rem;
    background: #f9fafb;
    font-size: 12px
  }

  .btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: .5rem .8rem;
    background: #111827;
    color: #fff;
    text-decoration: none
  }

  .btn.secondary {
    background: #fff;
    color: #111827
  }

  .input,
  .select,
  textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: .55rem .7rem
  }

  .toolbar {
    display: flex;
    gap: 8px;
    flex-wrap: wrap
  }

  .toolbar .input {
    min-width: 260px;
    flex: 1
  }

  .list {
    display: grid;
    gap: 12px
  }

  .meta {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    font-size: 12px;
    color: #6b7280
  }

  .item {
    display: flex;
    gap: 12px;
    padding: 12px;
    border-radius: 12px
  }

  /* ====== thumb ====== */
  .thumb {
    width: 68px;
    height: 68px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #f8fafc;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    overflow: hidden
  }

  .thumb span {
    font-size: 11px;
    color: #94a3b8;
    line-height: 1.2;
    text-align: center
  }

  .thumb-img {
    width: 100%;
    height: 100%;
    display: block;
    border-radius: 10px;
    background: #fff
  }

  .thumb-img.cover {
    object-fit: cover;
  }

  /* 求人画像はトリミング */
  .thumb-img.contain {
    object-fit: contain;
  }

  /* ロゴは全体表示 */

  .item-head {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap
  }

  .item-title {
    font-weight: 700;
    color: #111827;
    text-decoration: none
  }

  .item-desc {
    color: #475569;
    line-height: 1.6;
    margin-top: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden
  }

  .item-foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px
  }

  .sidebar .box {
    padding: 12px
  }

  .chips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px
  }

  .chips a {
    font-size: 12px;
    text-decoration: none
  }

  .pag {
    margin-top: 16px
  }
</style>

<div class="jobs-wrap">

  {{-- ===== Left: List ===== --}}
  <div>

    {{-- 検索ツールバー --}}
    <div class="card shadow" style="padding:12px;margin-bottom:12px;">
      <form method="GET" action="{{ route('front.jobs.index') }}" class="toolbar">
        <input class="input" type="search" name="q" value="{{ $q ?? '' }}" placeholder="キーワード（スペースでAND）">
        <select class="select" name="status">
          <option value="">ステータス: すべて</option>
          <option value="published" @selected(($status ?? '' )==='published' )>published</option>
          <option value="draft" @selected(($status ?? '' )==='draft' )>draft</option>
        </select>
        <button class="btn" type="submit">検索</button>
        <a class="btn secondary" href="{{ route('front.jobs.index') }}">クリア</a>
        @auth
        @if(in_array(auth()->user()->role, ['company', 'admin']))
        <a class="btn" href="{{ route('front.jobs.create') }}">＋ 新規作成</a>
        @endif
        @endauth


      </form>
    </div>

    {{-- リスト --}}
    @if(isset($jobs) && $jobs->count())
    <div class="list">
      @foreach($jobs as $job)
      @php
      $slug = $job->slug ?? $job->id;
      $url = route('front.jobs.show', $slug);
      $date = optional($job->created_at)->format('Y-m-d');
      $salary = null;
      if(!empty($job->salary_from) || !empty($job->salary_to)){
      $salary = trim(($job->salary_from ? number_format($job->salary_from) : '').'〜'.($job->salary_to ? number_format($job->salary_to) : ''))
      .' '.($job->salary_unit ?? '');
      }
      @endphp

      <article class="card shadow item">
        <a class="thumb" href="{{ $url }}">
          @if($job->image_url)
          {{-- 求人にセットした画像（最優先）：cover でトリミング --}}
          <img class="thumb-img cover" src="{{ $job->image_url }}" alt="{{ $job->title ?? 'サムネイル' }}" loading="lazy">
          @elseif($job->company?->logo_url)
          {{-- 会社ロゴ：contain で全体表示 --}}
          <img class="thumb-img contain" src="{{ $job->company->logo_url }}" alt="{{ $job->company_name ?? '企業ロゴ' }}" loading="lazy">
          @else
          {{-- どちらも無い：従来の NO IMG を維持 --}}
          <span>NO<br>IMG</span>
          @endif
        </a>

        <div style="flex:1;">
          <div class="item-head">
            <a class="item-title" href="{{ $url }}">{{ $job->title ?? '(無題)' }}</a>
            @if(!empty($job->employment_type))
            <span class="badge">{{ $job->employment_type }}</span>
            @endif
            @if(!empty($job->status))
            <span class="badge">{{ $job->status }}</span>
            @endif
          </div>

          <div class="meta" style="margin-top:6px;">
            @if(!empty($job->company_name))<span>{{ $job->company_name }}</span>@endif
            @if(!empty($job->location))<span>📍 {{ $job->location }}</span>@endif
            @if($salary)<span>💰 {{ $salary }}</span>@endif
            @if(!empty($job->work_style))<span>🏠 {{ $job->work_style }}</span>@endif
          </div>

          @if(!empty($job->description))
          <p class="item-desc">{{ strip_tags($job->description) }}</p>
          @endif

          <div class="item-foot">
            <div class="muted">投稿日: {{ $date ?? '-' }}</div>
            <div style="display:flex;gap:8px;">
              @if(!empty($job->apply_url))
              <a class="btn secondary" href="{{ $job->apply_url }}" target="_blank" rel="noopener">応募ページ</a>
              @endif
              <a class="btn" href="{{ $url }}">詳細を見る</a>
            </div>
          </div>
        </div>
      </article>
      @endforeach
    </div>

    <div class="pag">
      {{ $jobs->links() }}
    </div>
    @else
    <div class="card shadow" style="padding:16px;">
      <p class="muted">該当する求人はありません。</p>
    </div>
    @endif
  </div>

  {{-- ===== Right: Sidebar ===== --}}
  <aside class="sidebar">
    <div class="card shadow box">
      <h3 class="section-h">タグ</h3>

      <div class="chips">
        @php
        // 今ページの求人に「実際に存在するタグ」だけを抽出（カンマ/スペース区切り対応）
        $allTags = $jobs->pluck('tags')
        ->filter()
        ->flatMap(function ($t) {
        $t = trim($t);
        if ($t === '') return [];
        return preg_split('/[\s,　]+/u', $t, -1, PREG_SPLIT_NO_EMPTY);
        })
        ->map(fn($t) => trim($t))
        ->filter()
        ->unique()
        ->values();
        @endphp

        @forelse ($allTags as $tag)
        <a class="badge" href="?q={{ urlencode($tag) }}">#{{ $tag }}</a>
        @empty
        <span class="muted" style="font-size:12px;">タグはまだ登録されていません。</span>
        @endforelse
      </div>
    </div>

    <div class="card shadow box" style="margin-top:12px;">
      <h3 class="section-h">クイックリンク</h3>
      <ul style="margin:0;padding-left:1rem;line-height:1.9;">
        <li><a href="{{ route('front.jobs.index',['status'=>'published']) }}">公開中のみ</a></li>
        <li><a href="{{ route('front.company.index') }}">企業一覧</a></li>
      </ul>
    </div>
  </aside>

</div>
@endsection