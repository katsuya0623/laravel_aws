<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">企業情報の編集</h2>
  </x-slot>

  @php
  $contactUrl = \Illuminate\Support\Facades\Route::has('contact')
  ? route('contact')
  : url('/contact');
  @endphp

  {{-- ✅ 追加：Trix（WYSIWYG） --}}
  <link rel="stylesheet" href="https://unpkg.com/trix@2.1.8/dist/trix.css">
  <style>
  /* ===== Trix toolbar active state fix (daisyUI/Tailwind対策) ===== */

  /* ボタンの見た目を明確に */
  trix-toolbar .trix-button {
    border: 1px solid #d1d5db !important;  /* gray-300 */
    background: #fff !important;
    color: #111827 !important;             /* gray-900 */
  }

  /* 押されてる状態（これが見えない問題の本体） */
  trix-toolbar .trix-button.trix-active {
    background: #e5e7eb !important;        /* gray-200 */
    border-color: #6b7280 !important;      /* gray-500 */
    box-shadow: inset 0 0 0 1px #6b7280 !important;
  }

  /* hoverも少しわかりやすく */
  trix-toolbar .trix-button:hover:not(.trix-active) {
    background: #f3f4f6 !important;        /* gray-100 */
  }

  /* ===== ここから「文章側」見やすさ強化 ===== */

  /* 全体の読みやすさ */
  trix-editor {
    line-height: 1.8;
    font-size: 16px;
  }

  /* 段落間の余白（Trixはdivで入ることが多い） */
  trix-editor div,
  trix-editor p {
    margin: 0.5rem 0;
  }

  /* フォーカス時の枠（編集してる感） */
  trix-editor:focus {
    outline: 2px solid #93c5fd;            /* blue-300 */
    outline-offset: 2px;
  }

  /* --- List（番号・黒丸・ネスト） --- */
  trix-editor ul,
  trix-editor ol {
    list-style: revert !important;     /* markerを復活 */
    padding-left: 1.5rem !important;   /* インデントを戻す */
    margin: 0.75rem 0 !important;
  }

  trix-editor li {
    display: list-item !important;     /* markerを確実に出す */
    margin: 0.25rem 0 !important;
  }

  /* ネストしたリストも見えるように */
  trix-editor ul ul,
  trix-editor ol ol,
  trix-editor ul ol,
  trix-editor ol ul {
    margin-top: 0.5rem !important;
    margin-bottom: 0.5rem !important;
    padding-left: 1.5rem !important;
  }

  /* --- 見出し（h2/h3）--- */
  trix-editor h2 {
    font-size: 1.25rem;
    font-weight: 800;
    margin: 1rem 0 0.5rem;
    padding-bottom: 0.25rem;
    border-bottom: 1px solid #e5e7eb;
  }

  trix-editor h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0.9rem 0 0.4rem;
  }

  /* --- 引用（blockquote）--- */
  trix-editor blockquote {
    margin: 0.9rem 0;
    padding: 0.75rem 1rem;
    border-left: 4px solid #cbd5e1;
    background: #f8fafc;
    color: #334155;
    border-radius: 8px;
  }

  /* --- リンク（a）--- */
  trix-editor a {
    color: #2563eb;
    text-decoration: underline;
    text-underline-offset: 2px;
  }
  trix-editor a:hover {
    opacity: 0.85;
  }

  /* --- 太字/斜体/下線/取り消し線 --- */
  trix-editor strong { font-weight: 800; }
  trix-editor em { font-style: italic; }
  trix-editor u { text-decoration: underline; text-underline-offset: 2px; }
  trix-editor s { text-decoration-thickness: 2px; }

  /* --- インラインコード --- */
  trix-editor code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    font-size: 0.95em;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    padding: 0.1rem 0.35rem;
    border-radius: 6px;
  }

  /* --- 区切り線 --- */
  trix-editor hr {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 1rem 0;
  }
</style>



  {{-- ▼ このページは翻訳させない --}}
  <div class="p-6 max-w-4xl space-y-6 notranslate" translate="no">
    {{-- 成功メッセージ（フラッシュ） --}}
    @if (session('status'))
    <div x-data="{ show: true }"
      x-show="show"
      x-transition
      class="alert alert-success shadow-sm mb-4">
      <div class="flex-1">
        <span class="font-medium">{{ session('status') }}</span>
      </div>
      <button type="button" class="btn btn-sm btn-ghost" @click="show=false">×</button>
    </div>
    @endif


    <form method="POST"
      action="{{ route('user.company.update') }}"
      enctype="multipart/form-data"
      class="space-y-6 notranslate"
      translate="no"
      autocomplete="off">
      @csrf

      <div class="grid md:grid-cols-2 gap-4">
        {{-- 企業名（視覚＆操作ロック。送信もしない） --}}
        <div>
          <x-input-label for="company_name">
            企業名 <span class="text-red-500">*</span>
          </x-input-label>
          <div class="relative">
            <input id="company_name"
              type="text"
              value="{{ $company?->name ?? $company?->company_name ?? '' }}"
              class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-gray-600 pointer-events-none"
              disabled
              aria-readonly="true"
              aria-disabled="true"
              translate="no"
              autocapitalize="off" autocorrect="off" spellcheck="false">
            <div class="pointer-events-none absolute inset-0 rounded-md bg-white/40 flex items-center justify-center">
              <span class="text-xs text-gray-600 border border-gray-300 rounded-full px-2 py-0.5 bg-white">🔒 変更できません</span>
            </div>
          </div>
          <p class="mt-1 text-sm text-red-500">
            企業名の変更はできません。変更をご希望の場合は
            <a href="{{ $contactUrl }}" class="underline text-indigo-600">お問い合わせ</a>
            ください。
          </p>
        </div>

        {{-- 企業名（カナ） --}}
        <div>
          <x-input-label for="company_name_kana">
            企業名（カナ） <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input
            id="company_name_kana"
            name="company_name_kana"
            type="text"
            class="mt-1 block w-full"
            maxlength="255"
            pattern="^[ァ-ヶー－\s　]+$"
            required
            placeholder="カタカナ"
            :value="old('company_name_kana', $company->company_name_kana)"
            translate="no"
            autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('company_name_kana')" class="mt-2" />
        </div>
      </div>

      {{-- ✅ 修正：事業内容 / 紹介（WYSIWYG/Trix） --}}
      <div>
        <x-input-label for="description">
          事業内容 / 紹介 <span class="text-red-500">*</span>
        </x-input-label>

        {{-- Trix は hidden input にHTMLを入れて送信する --}}
        <input id="description" type="hidden" name="description"
          value="{{ old('description', $company->description) }}">

        <trix-editor input="description"
          class="mt-1 block w-full border-gray-300 rounded-md bg-white"
          translate="no"></trix-editor>

        <x-input-error :messages="$errors->get('description')" class="mt-2" />
        <p class="text-xs text-gray-500 mt-1">最大 20000 文字（WYSIWYG）</p>
      </div>

      {{-- ロゴ（DBは logo_path に保存） --}}
      <div class="flex items-start gap-6">
        <div class="grow">
          <x-input-label for="logo" value="ロゴ画像（最大10MB / SVG, PNG, JPG, WebP）" />
          <input id="logo" name="logo" type="file"
            accept=".svg,.svgz,.png,.jpg,.jpeg,.webp"
            class="mt-1 block w-full"
            translate="no">
          <x-input-error :messages="$errors->get('logo')" class="mt-2" />

          @if(!empty($company->logo_path))
          <label class="inline-flex items-center gap-2 mt-3 text-sm">
            <input type="checkbox" name="remove_logo" value="1" translate="no">
            ロゴを削除する
          </label>
          @endif
        </div>

        @php
        $path = $company->logo_path ?? null;
        $logoUrl = $path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($path)
        : asset('images/no-image.svg');
        @endphp
        <div class="shrink-0" translate="no">
          <div class="text-xs text-gray-500 mb-1">プレビュー</div>
          <img id="logoPreview"
            src="{{ $logoUrl }}"
            alt="logo preview"
            class="w-24 h-24 rounded object-contain border bg-white">
          <div class="text-[10px] text-gray-500 mt-1 break-all max-w-[10rem]">
            {{ $path ?: '（なし）' }}
          </div>
        </div>
      </div>

      {{-- 連絡先 --}}
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <x-input-label for="website_url" value="Webサイト" />
          <x-text-input id="website_url" name="website_url" type="url" class="mt-1 block w-full"
            inputmode="url" maxlength="255" placeholder="https://example.com"
            :value="old('website_url', $company->website_url)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('website_url')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="email" value="代表メール" />
          <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
            inputmode="email" maxlength="255" placeholder="info@example.com"
            :value="old('email', $company->email)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="tel" value="電話番号" />
          <x-text-input id="tel" name="tel" type="text" class="mt-1 block w-full"
            inputmode="tel" maxlength="20"
            placeholder="03-1234-5678 / +81-3-1234-5678"
            :value="old('tel', $company->tel)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('tel')" class="mt-2" />
        </div>
      </div>

      {{-- 住所（必須） --}}
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <x-input-label for="postal_code">
            郵便番号 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full"
            inputmode="numeric" pattern="^\d{3}-?\d{4}$" placeholder="123-4567"
            required :value="old('postal_code', $company->postal_code)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="prefecture">
            都道府県 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="prefecture" name="prefecture" type="text" class="mt-1 block w-full"
            maxlength="255" required :value="old('prefecture', $company->prefecture)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('prefecture')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="city">
            市区町村 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
            maxlength="255" required :value="old('city', $company->city)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('city')" class="mt-2" />
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <x-input-label for="address1">
            番地・建物 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="address1" name="address1" type="text" class="mt-1 block w-full"
            maxlength="255" required :value="old('address1', $company->address1)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('address1')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="address2" value="部屋番号など" />
          <x-text-input id="address2" name="address2" type="text" class="mt-1 block w-full"
            maxlength="255" :value="old('address2', $company->address2)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('address2')" class="mt-2" />
        </div>
      </div>

      {{-- 企業情報（必須） --}}
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <x-input-label for="industry">
            業種 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="industry" name="industry" type="text" class="mt-1 block w-full"
            maxlength="255" required :value="old('industry', $company->industry)"
            translate="no" autocapitalize="off" autocorrect="off" spellcheck="false" />
          <x-input-error :messages="$errors->get('industry')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="employees">
            従業員数 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="employees" name="employees" type="number" min="1" max="1000000" step="1"
            class="mt-1 block w-full" required :value="old('employees', $company->employees)"
            translate="no" />
          <x-input-error :messages="$errors->get('employees')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="founded_on" value="設立日" />
          <x-text-input id="founded_on" name="founded_on" type="date" class="mt-1 block w-full"
            value="{{ old('founded_on', is_string($company->founded_on) ? $company->founded_on : optional($company->founded_on)->format('Y-m-d')) }}"
            translate="no" />
          <x-input-error :messages="$errors->get('founded_on')" class="mt-2" />
          <p class="text-xs text-gray-500 mt-1">未来日は不可</p>
        </div>
      </div>

      <div class="flex justify-end">
        <x-primary-button translate="no">保存する</x-primary-button>
      </div>
    </form>
  </div>

  {{-- ✅ 追加：Trix --}}
  <script src="https://unpkg.com/trix@2.1.8/dist/trix.umd.min.js"></script>

  {{-- 画像即時プレビュー --}}
  <script>
    const input = document.getElementById('logo');
    const img = document.getElementById('logoPreview');
    if (input && img) {
      input.addEventListener('change', (e) => {
        const f = e.target.files && e.target.files[0];
        if (!f) return;
        const url = URL.createObjectURL(f);
        img.src = url;
        img.onload = () => URL.revokeObjectURL(url);
      });
    }
  </script>
</x-app-layout>
