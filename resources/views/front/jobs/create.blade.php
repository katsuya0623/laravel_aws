<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>求人の新規作成</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;background:#f7f7f9;margin:0}
    .wrap{max-width:980px;margin:32px auto;padding:24px;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
    h1{margin:0 0 20px}
    .card{border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:16px 0;background:#fff}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .field{display:flex;flex-direction:column;margin-bottom:12px}
    label{font-size:14px;color:#374151;margin-bottom:4px}
    input,select,textarea{font:inherit;border:1px solid #d1d5db;border-radius:8px;padding:10px;background:#fff}
    textarea{min-height:140px;resize:vertical}
    .help{font-size:12px;color:#6b7280}
    .err{background:#fff3f3;color:#b30000;border:1px solid #f3c4c4;padding:10px;border-radius:8px;margin:12px 0}
    .btns{display:flex;gap:10px;margin-top:16px}
    .btn{appearance:none;border:0;border-radius:10px;padding:12px 16px;font-weight:600;cursor:pointer}
    .primary{background:#4f46e5;color:#fff}
    .ghost{background:#eef2ff;color:#1f2a44}

    /* ==== 必須マーク（赤い※） ==== */
    label.req::after{
      content:"※";
      color:#ef4444;
      margin-left:4px;
      font-weight:700;
    }
    /* フィールドエラー表示 */
    .fe{color:#b30000;font-size:12px;margin-top:6px}
    /* 読み取り専用の見た目 */
    .muted-input{background:#f9fafb;color:#111827}
    .muted-input[readonly]{pointer-events:none}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>求人の新規作成</h1>

    @if ($errors->any())
      <div class="err">
        <strong>入力内容を確認してください。</strong>
        <ul style="margin:6px 0 0 18px">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- 画像を送るので enctype が必須 --}}
    <form method="POST" action="{{ route('front.jobs.store') }}" enctype="multipart/form-data">
      @csrf

      {{-- 基本情報 --}}
      <div class="card">
        <h2 style="margin:0 0 12px">基本情報</h2>
        <div class="grid">
          <div class="field">
            <label for="title" class="req">タイトル</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required>
            @error('title')<div class="fe">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="company_id" class="req">企業</label>
            @php
              $fixedCompanyId = $companyId ?? (auth()->check() ? (auth()->user()->company_id ?? null) : null);
              $companies = $companies ?? collect();
              $fixedCompany = $fixedCompanyId ? $companies->firstWhere('id', $fixedCompanyId) : null;
            @endphp

            @if($fixedCompanyId && $fixedCompany)
              <input type="hidden" name="company_id" value="{{ $fixedCompanyId }}">
              <input type="text" class="muted-input" value="{{ $fixedCompany->name }}" readonly>
            @else
              <select id="company_id" name="company_id" required>
                <option value="">オプションを選択</option>
                @foreach ($companies as $c)
                  <option value="{{ $c->id }}" @selected(old('company_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
              </select>
              @if($companies->isEmpty())
                <div class="help" style="color:#b30000">企業プロフィールで会社を設定してください。</div>
              @endif
            @endif

            @error('company_id')<div class="fe">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="field">
          <label for="summary" class="req">概要</label>
          <textarea id="summary" name="summary" placeholder="イントロダクションや要点" required>{{ old('summary') }}</textarea>
          @error('summary')<div class="fe">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <label for="body" class="req">本文（仕事内容・必須スキル・歓迎・福利厚生など）</label>
          <textarea id="body" name="body" required>{{ old('body') }}</textarea>
          @error('body')<div class="fe">{{ $message }}</div>@enderror
        </div>

        <div class="field">
          <label for="image">求人画像</label>
          <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp">
          <div class="help">対応：jpg / jpeg / png / webp、2MB以内</div>
          @error('image')<div class="fe">{{ $message }}</div>@enderror
        </div>
      </div>

      {{-- 詳細 --}}
      <div class="card">
        <h2 style="margin:0 0 12px">詳細</h2>

        <div class="grid">
          {{-- 勤務地（必須） --}}
          <div class="field">
            <label for="location" class="req">勤務地</label>
            <input id="location" name="location" type="text" value="{{ old('location') }}" required maxlength="255">
            @error('location')<div class="fe">{{ $message }}</div>@enderror
          </div>

          {{-- 雇用形態（既存必須） --}}
          <div class="field">
            <label for="employment_type" class="req">雇用形態</label>
            <select id="employment_type" name="employment_type" required>
              @php $employment = ['正社員','契約社員','アルバイト','業務委託','インターン']; @endphp
              <option value="">オプションを選択</option>
              @foreach ($employment as $opt)
                <option value="{{ $opt }}" @selected(old('employment_type')===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
            @error('employment_type')<div class="fe">{{ $message }}</div>@enderror
          </div>

          {{-- 働き方（必須化） --}}
          <div class="field">
            <label for="work_style" class="req">働き方</label>
            <select id="work_style" name="work_style" required>
              @php $styles = ['出社','フルリモート','ハイブリッド']; @endphp
              <option value="">オプションを選択</option>
              @foreach ($styles as $opt)
                <option value="{{ $opt }}" @selected(old('work_style')===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
            @error('work_style')<div class="fe">{{ $message }}</div>@enderror
          </div>
        </div>

        <div class="grid">
          {{-- 単位（既存必須） --}}
          <div class="field">
            <label for="salary_unit" class="req">単位</label>
            <select id="salary_unit" name="salary_unit" required>
              @foreach (['月収','年収','時給'] as $opt)
                <option value="{{ $opt }}" @selected(old('salary_unit')===$opt)>{{ $opt }}</option>
              @endforeach
            </select>
            @error('salary_unit')<div class="fe">{{ $message }}</div>@enderror
          </div>

          {{-- タグ（必須化＋制約） --}}
          <div class="field">
            <label for="tags" class="req">タグ（スペース区切り）</label>
            <input id="tags" name="tags" type="text"
                   placeholder="例）Laravel Vue AWS"
                   value="{{ old('tags') }}" required maxlength="255"
                   pattern="[^\s,、。|]{1,20}(?:\s[^\s,、。|]{1,20}){0,9}"
                   title="タグはスペース区切り、各20文字以内・最大10個。カンマや句読点は不可。">
            @error('tags')<div class="fe">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      {{-- 公開設定 --}}
      <div class="card">
        <h2 style="margin:0 0 12px">公開設定</h2>

        <div class="field">
          <label>公開状態</label>
          @php $status = old('status','draft'); @endphp
          <div style="display:flex;gap:8px;flex-wrap:wrap">
            <label><input type="radio" name="status" value="draft"      {{ $status==='draft'?'checked':'' }}> 下書き</label>
            <label><input type="radio" name="status" value="published" {{ $status==='published'?'checked':'' }}> 公開</label>
          </div>
          @error('status')<div class="fe">{{ $message }}</div>@enderror
        </div>

        <div class="grid">
          <div class="field">
            <label for="publish_at">公開日時</label>
            <input id="publish_at" name="publish_at" type="datetime-local" value="{{ old('publish_at') }}">
            @error('publish_at')<div class="fe">{{ $message }}</div>@enderror
          </div>

          <div class="field">
            <label for="slug">スラッグ（未入力なら自動生成）</label>
            <input id="slug" name="slug" type="text" value="{{ old('slug') }}">
            <div class="help">保存時に空なら title から自動生成します。</div>
            @error('slug')<div class="fe">{{ $message }}</div>@enderror
          </div>
        </div>
      </div>

      <div class="btns">
        <button class="btn primary" type="submit" name="action" value="create">作成</button>
        <button class="btn ghost" type="submit" name="action" value="create_and_continue">保存して、続けて作成</button>
        <a class="btn" style="background:#f3f4f6;color:#111827;text-decoration:none;padding:12px 16px;border-radius:10px"
           href="{{ route('front.jobs.index') }}">キャンセル</a>
      </div>
    </form>
  </div>

  {{-- タグ入力の空白正規化 --}}
  <script>
    document.addEventListener('input', (e) => {
      if (e.target && e.target.id === 'tags') {
        e.target.value = e.target.value.replace(/\s+/g, ' ');
      }
    });
  </script>
</body>
</html>
