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
         応募セクション
       ========================= --}}
    <section style="margin-top:24px;border:1px solid #e5e7eb;border-radius:8px;padding:16px;">
      <h2 style="font-weight:700;margin-bottom:12px;">この求人に応募する</h2>

      @auth('web')
        {{-- 既存の応募フォーム（ログイン済みのときだけ表示） --}}
        <form method="POST" action="{{ route('front.jobs.apply', $job) }}">
          @csrf

          <div style="margin-bottom:10px;">
            <label>お名前</label><br>
            <input name="name" type="text" value="{{ old('name') }}" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            @error('name') <div style="color:#b91c1c;font-size:12px;">{{ $message }}</div> @enderror
          </div>

          <div style="margin-bottom:10px;">
            <label>メールアドレス</label><br>
            <input name="email" type="email" value="{{ old('email') }}" required style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            @error('email') <div style="color:#b91c1c;font-size:12px;">{{ $message }}</div> @enderror
          </div>

          <div style="margin-bottom:10px;">
            <label>電話番号（任意）</label><br>
            <input name="phone" type="text" value="{{ old('phone') }}" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">
            @error('phone') <div style="color:#b91c1c;font-size:12px;">{{ $message }}</div> @enderror
          </div>

          <div style="margin-bottom:16px;">
            <label>メッセージ（任意）</label><br>
            <textarea name="message" rows="4" style="width:100%;padding:10px;border:1px solid #d1d5db;border-radius:6px;">{{ old('message') }}</textarea>
            @error('message') <div style="color:#b91c1c;font-size:12px;">{{ $message }}</div> @enderror
          </div>

          <button type="submit"
            style="display:inline-block;background:#111827;color:#fff;padding:10px 16px;border-radius:8px;border:none;cursor:pointer;">
            応募する
          </button>
        </form>

      @else
        {{-- 未ログイン：既存の「ログイン/新規登録 → 応募画面へ復帰」を維持 --}}
        {{-- ログインは保護ルートへ直接飛ばす（intendedはLaravel標準に任せる） --}}
        <a href="{{ route('front.jobs.apply.gate', ['job' => $job->slug, 'autofav' => 1]) }}"
           style="display:inline-block;background:#111827;color:#fff;padding:10px 16px;border-radius:8px;text-decoration:none;">
          ログインして応募する
        </a>
        <div style="margin-top:8px;color:#6b7280;font-size:13px;">
          はじめての方は
          {{-- 新規登録は intended を明示して apply.gate?autofav=1 へ戻す --}}
          <a href="{{ route('register.intended', ['redirect' => route('front.jobs.apply.gate', ['job' => $job->slug, 'autofav' => 1])]) }}"
             style="color:#4f46e5;text-decoration:underline;">
            新規登録
          </a>
          へ
        </div>
      @endauth
    </section>
  </article>
@endsection
