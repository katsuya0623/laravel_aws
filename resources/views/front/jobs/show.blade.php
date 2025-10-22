@extends('front.layout')
@section('title', $job->title ?? '求人詳細')

@section('content')
  <article class="job-detail" style="max-width:720px;margin:24px auto;">
    <h1 style="font-size:24px;font-weight:700;margin-bottom:8px;">
      {{ $job->title ?? '求人詳細' }}
    </h1>

    {{-- =========================
         お気に入り（未ログイン対応）
       ========================= --}}
    <div style="margin-bottom:16px;display:flex;gap:10px;align-items:center;">
      @auth('web')
        {{-- ログイン済み：既存トグルそのまま --}}
        <x-favorite-toggle :job="$job" />
      @else
        {{-- 未ログイン：favorite-apply に POSTして応募ゲートへ（ログイン後にAutoFavorite発火） --}}
        <form method="POST"
              action="{{ route('front.jobs.favorite_apply', ['slugOrId' => $job->slug ?? $job->id]) }}">
          @csrf
          <button type="submit"
                  class="btn btn-link p-0 align-baseline"
                  style="background:none;border:none;color:#4f46e5;cursor:pointer;">
            ☆ 追加（ログイン）
          </button>
        </form>
      @endauth

      <span style="font-size:12px;color:#6b7280;">
        ★ {{ $job->favored_by_count ?? $job->favoredBy()->count() }}
      </span>
    </div>

    @if (session('status'))
      <div style="background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46;padding:10px;border-radius:6px;margin-bottom:16px;">
        {{ session('status') }}
      </div>
    @endif

    {{-- =========================
         ▼ 求人詳細（本文・概要・画像・メタ）
       ========================= --}}
    @php
      // 画像フィールド名の候補（存在するものを使う）
      $image = $job->image_url
              ?? (isset($job->image) ? (\Illuminate\Support\Str::startsWith($job->image, ['http://','https://']) ? $job->image : asset($job->image)) : null);

      // カラム名のゆらぎ対策（summary/body が無ければ代替を探す）
      $summary = $job->summary ?? $job->overview ?? $job->description_short ?? null;
      $body    = $job->body    ?? $job->description ?? $job->content ?? null;

      // メタの表示
      $rows = [
        '勤務地'   => $job->location    ?? null,
        '雇用形態' => $job->employment_type ?? null,
        '働き方'   => $job->work_style  ?? null,   // 例: フルリモート
        '給与単位' => $job->salary_unit ?? null,  // 例: 月収
        'タグ'     => $job->tags        ?? null,   // スペース区切り想定
      ];
    @endphp

    @if($image)
      <div style="margin:12px 0 20px;">
        <img src="{{ $image }}" alt="{{ $job->title }}" style="max-width:100%;height:auto;border-radius:8px;">
      </div>
    @endif

    @if(!empty($summary))
      <p style="margin:8px 0 16px; color:#374151; line-height:1.7;">
        {{ $summary }}
      </p>
    @endif

    @if(!empty($body))
      <div style="margin:0 0 24px; color:#111827; line-height:1.9; white-space:pre-wrap;">
        {!! nl2br(e($body)) !!}
      </div>
    @endif

    <ul style="margin:0 0 24px; padding:0; list-style:none; border-top:1px solid #e5e7eb;">
      @foreach($rows as $label => $val)
        @if(!empty($val))
          <li style="display:flex; gap:12px; padding:10px 0; border-bottom:1px solid #f3f4f6;">
            <span style="width:90px; color:#6b7280;">{{ $label }}</span>
            <span style="flex:1;">{{ $val }}</span>
          </li>
        @endif
      @endforeach
    </ul>

    {{-- =========================
         応募セクション（別ページに遷移）
       ========================= --}}
    <section style="margin-top:24px;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
      <h2 style="font-weight:700;margin-bottom:12px;">この求人に応募する</h2>

      <p style="color:#6b7280;margin-bottom:12px;">
        応募は専用フォーム（別ページ）から受付します。
      </p>

      <div>
        {{-- ★ ここを正しいルート名＆パラメータに変更 --}}
        <a href="{{ route('front.jobs.apply_form', ['job' => $job->slug ?? $job->id]) }}"
           class="btn"
           style="display:inline-block;background:#111827;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;">
          応募フォームへ進む
        </a>
      </div>
    </section>
  </article>
@endsection
