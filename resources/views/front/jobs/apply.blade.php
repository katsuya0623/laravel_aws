@extends('front.layout')
@section('title', ($job->title ?? '応募フォーム').'｜応募')

@section('content')
@php
use Illuminate\Support\Carbon;

$thumb = $job->thumb_url ?? $job->image_url ?? null;

// 任意：ユーザー基本情報（無ければ null）
$u = $user ?? null;
$p = $profile ?? null;

/* ========= 補完/正規化 ========= */
// 性別
$genderVal   = $p->gender ?? $u->gender ?? null;
$genderLabel = $p->gender_label
    ?? $u->gender_label
    ?? ([ 'male' => '男性', 'female' => '女性', 'other' => 'その他' ][$genderVal] ?? null);

// 生年月日（birthdate|birthday → 表示/hidden）
$birthRaw  = $p->birthdate ?? $p->birthday ?? $u->birthdate ?? null;
$birthDisp = $birthRaw ? Carbon::parse($birthRaw)->isoFormat('YYYY年M月D日') : null;

// 住所（address_full 無ければパーツ結合）
$addressFull =
    $p->address_full
    ?? implode('', array_filter([
        $p->prefecture ?? null,
        $p->city ?? $p->city_name ?? null,
        $p->address_line ?? $p->address1 ?? null,
        $p->building ?? $p->address2 ?? null,
    ]))
    ?: ($u->address_full ?? null);
$addressRaw = $addressFull;

// 学歴（summary が無ければ education）
$educationSummary = $p->education_summary
    ?? $p->education
    ?? $u->education_summary
    ?? $u->education
    ?? null;

// 電話（プロフ優先）
$phoneVal = $p->phone ?? $u->phone ?? '';

// 表示ヘルパ
$displayOrUnset = function($v){
    return $v ? e($v) : '<span style="color:#ef4444">未設定（基本情報修正から設定してください）</span>';
};
@endphp

<style>
  .apply-wrap{max-width:920px;margin:40px auto;padding:0 16px}
  .apply-head{display:flex;gap:16px;align-items:flex-start;margin-bottom:24px}
  .apply-thumb{width:120px;height:80px;border-radius:10px;overflow:hidden;background:#f3f4f6;flex:0 0 auto}
  .apply-thumb img{width:100%;height:100%;object-fit:cover}
  .apply-title{font-size:22px;font-weight:800;margin:0}
  .muted{color:#6b7280}
  .note{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:12px 14px;margin:8px 0 20px}
  .form-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04);padding:26px}
  .fm-row{display:grid;grid-template-columns:220px 1fr;gap:16px;align-items:center;padding:14px 0;border-top:1px dashed #eef}
  .fm-row:first-of-type{border-top:none}
  .fm-row .label{font-weight:700}
  .req{color:#ef4444;margin-left:6px;font-weight:700}
  .fm-input{width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px}
  .fm-select{width:100%;border:1px solid #e5e7eb;border-radius:10px;padding:10px 12px;background:#fff}
  .fm-textarea{min-height:140px}
  @media (max-width:860px){.fm-row{grid-template-columns:1fr;align-items:unset}}
  .btn-primary{display:inline-flex;align-items:center;gap:8px;padding:12px 16px;border-radius:10px;border:1px solid #111;background:#111;color:#fff;font-weight:800}
  .agree{display:flex;gap:8px;align-items:flex-start}
  .err{color:#dc2626;font-size:12px;margin-top:6px}
  .readonly{padding:8px 0}
</style>

<div class="apply-wrap">
  {{-- ヘッダー --}}
  <div class="apply-head">
    <div class="apply-thumb">
      @if($thumb)
        <img src="{{ $thumb }}" alt="thumb" loading="lazy">
      @endif
    </div>
    <div>
      <h1 class="apply-title">この求人に応募する</h1>
      <div class="muted" style="margin-top:4px;">
        募集職種：<strong>{{ $job->title ?? '（タイトル未設定）' }}</strong>
      </div>
      <div class="note">
        入力後、<strong>「応募を送信する」</strong>を押してください。<br>
        ※ 本フォームの「性別 / 生年月日 / 住所 / 学歴」は表示のみです。未設定の方はマイページの基本情報を更新してください。
      </div>
    </div>
  </div>

  {{-- 応募フォーム --}}
  <div class="form-card">
    <form method="POST" action="{{ route('front.jobs.apply_store', ['job' => $job->slug ?? $job->id]) }}">
      @csrf

      {{-- 名前（必須） --}}
      <div class="fm-row">
        <div class="label">名前 <span class="req">※</span></div>
        <div>
          <input type="text" name="name" class="fm-input"
                 value="{{ old('name', $u->name ?? '') }}" placeholder="例）山田 太郎" required>
          @error('name')<div class="err">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- フリガナ --}}
      <div class="fm-row">
        <div class="label">フリガナ <span class="req">※</span></div>
        <div>
          <input type="text" name="kana" class="fm-input" value="{{ old('kana') }}" placeholder="ヤマダ タロウ">
        </div>
      </div>

      {{-- 性別（表示のみ）＋ hidden --}}
      <div class="fm-row">
        <div class="label">性別 <span class="req">※</span></div>
        <div class="readonly row-gender">
          {!! $displayOrUnset($genderLabel) !!}
          <input type="hidden" name="gender" value="{{ $genderVal ?? '' }}">
        </div>
      </div>

      {{-- 生年月日（表示のみ）＋ hidden --}}
      <div class="fm-row">
        <div class="label">生年月日 <span class="req">※</span></div>
        <div class="readonly row-birth">
          {!! $displayOrUnset($birthDisp) !!}
          <input type="hidden" name="birthday" value="{{ $birthRaw }}">
        </div>
      </div>

      {{-- 住所（表示のみ）＋ hidden --}}
      <div class="fm-row">
        <div class="label">住所 <span class="req">※</span></div>
        <div class="readonly row-addr">
          {!! $displayOrUnset($addressFull) !!}
          <input type="hidden" name="address" value="{{ $addressRaw }}">
        </div>
      </div>

      {{-- メール --}}
      <div class="fm-row">
        <div class="label">メールアドレス</div>
        <div>
          <input type="email" name="email" class="fm-input"
                 value="{{ old('email', $u->email ?? '') }}" placeholder="taro@example.com" @guest required @endguest>
          @error('email')<div class="err">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- 電話番号 --}}
      <div class="fm-row">
        <div class="label">電話番号 <span class="req">※</span></div>
        <div>
          <input type="text" name="phone" class="fm-input" value="{{ old('phone', $phoneVal) }}" placeholder="090-1234-5678">
          @error('phone')<div class="err">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- 学歴（表示のみ）＋ hidden --}}
      <div class="fm-row">
        <div class="label">学歴 <span class="req">※</span></div>
        <div class="readonly row-edu">
          {!! $displayOrUnset($educationSummary) !!}
          <input type="hidden" name="education" value="{{ $educationSummary }}">
        </div>
      </div>

      {{-- 現在の状況 --}}
      <div class="fm-row">
        <div class="label">現在の状況 <span class="req">※</span></div>
        <div>
          <select name="current_status" class="fm-select" required>
            <option value="">選択してください</option>
            @foreach (['在職中（正社員）','在職中（契約・派遣）','在職中（フリーランス）','退職予定','離職中','学生','その他'] as $opt)
              <option value="{{ $opt }}" @selected(old('current_status')===$opt)>{{ $opt }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- 希望する雇用形態 --}}
      <div class="fm-row">
        <div class="label">希望する雇用形態 <span class="req">※</span></div>
        <div>
          <select name="desired_type" class="fm-select" required>
            <option value="">選択してください</option>
            @foreach (['正社員','契約社員','業務委託','アルバイト','インターン','いずれでも可'] as $opt)
              <option value="{{ $opt }}" @selected(old('desired_type')===$opt)>{{ $opt }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- 志望動機 --}}
      <div class="fm-row">
        <div class="label">志望動機 <span class="req">※</span></div>
        <div>
          <textarea name="motivation" class="fm-input fm-textarea" placeholder="応募の動機をご記入ください。">{{ old('motivation') }}</textarea>
        </div>
      </div>

      {{-- 自己PR --}}
      <div class="fm-row">
        <div class="label">自己PR / 自由記述欄 <span class="req">※</span></div>
        <div>
          <textarea name="message" class="fm-input fm-textarea"
            placeholder="スキル・実績・自己PRなど自由にご記入ください。">{{ old('message') }}</textarea>
          @error('message')<div class="err">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- 同意 --}}
      <div class="fm-row">
        <div class="label">個人情報の取扱い</div>
        <div class="agree">
          <input id="agree" type="checkbox" required style="margin-top:3px">
          <label for="agree" class="muted">応募にあたり、個人情報の取扱いに同意します。</label>
        </div>
      </div>

      {{-- 送信 --}}
      <div style="margin-top:24px">
        <button type="submit" class="btn-primary">応募を送信する</button>
        <a href="{{ route('front.jobs.show', ['slugOrId' => $job->slug ?? $job->id]) }}"
           class="muted" style="margin-left:12px">募集要項に戻る</a>
      </div>

      @if ($errors->has('apply'))
        <div class="err" style="margin-top:12px;">{{ $errors->first('apply') }}</div>
      @endif
      @if (session('status'))
        <div style="color:#16a34a;margin-top:12px;">{{ session('status') }}</div>
      @endif
    </form>
  </div>
</div>

<script>
(function(){
  const form = document.querySelector('form');
  if(!form) return;

  function v(name){
    const el = form.querySelector(`[name="${name}"]`);
    return el ? el.value.trim() : '';
  }

  form.addEventListener('submit', function(){
    const meta = [];
    const readText = (sel) => (form.querySelector(sel)?.innerText || '').trim();

    const gender = readText('.row-gender');
    const birth  = readText('.row-birth');
    const addr   = readText('.row-addr');
    const edu    = readText('.row-edu');

    if(v('kana'))           meta.push(`フリガナ：${v('kana')}`);
    if(gender)              meta.push(`性別：${gender}`);
    if(birth)               meta.push(`生年月日：${birth}`);
    if(addr)                meta.push(`住所：${addr}`);
    if(edu)                 meta.push(`学歴：${edu}`);
    if(v('current_status')) meta.push(`現在の状況：${v('current_status')}`);
    if(v('desired_type'))   meta.push(`希望する雇用形態：${v('desired_type')}`);

    if(v('motivation')){
      meta.push('');
      meta.push('【志望動機】');
      meta.push(v('motivation'));
    }

    const msg = form.querySelector('[name="message"]'); // ← クオート修正
    if(meta.length){
      msg.value = (msg.value ? msg.value + "\n\n" : '') + meta.join('\n');
    }
  });
})();
</script>
@endsection
