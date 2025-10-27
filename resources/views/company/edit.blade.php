<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">企業情報の編集</h2>
  </x-slot>

  @php
    $contactUrl = \Illuminate\Support\Facades\Route::has('contact')
      ? route('contact')
      : url('/contact');
  @endphp

  <div class="p-6 max-w-4xl space-y-6">
    @if (session('status'))
      <p class="text-emerald-600 text-sm">{{ session('status') }}</p>
    @endif

    <form method="POST"
          action="{{ route('user.company.update') }}"
          enctype="multipart/form-data"
          class="space-y-6">
      @csrf
      @method('PATCH') {{-- ルートがPATCH/PUTの場合に対応 --}}

      <div class="grid md:grid-cols-2 gap-4">
        {{-- 会社名（視覚＆操作ロック。送信もしない） --}}
        <div>
          <x-input-label for="company_name">
            会社名 <span class="text-red-500">*</span>
          </x-input-label>
          <div class="relative">
            <input id="company_name"
                   type="text"
                   value="{{ old('company_name', $company->company_name) }}"
                   class="mt-1 block w-full border-gray-300 rounded-md bg-gray-50 text-gray-600 pointer-events-none"
                   disabled
                   aria-readonly="true"
                   aria-disabled="true">
            <div class="pointer-events-none absolute inset-0 rounded-md bg-white/40 flex items-center justify-center">
              <span class="text-xs text-gray-600 border border-gray-300 rounded-full px-2 py-0.5 bg-white">🔒 変更できません</span>
            </div>
          </div>
          <p class="mt-1 text-sm text-red-500">
            会社名の変更はできません。変更をご希望の場合は
            <a href="{{ $contactUrl }}" class="underline text-indigo-600">お問い合わせ</a>
            ください。
          </p>
        </div>

        {{-- 会社名（カナ） --}}
        <div>
          <x-input-label for="company_name_kana">
            会社名（カナ） <span class="text-red-500">*</span>
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
            :value="old('company_name_kana', $company->company_name_kana)" />
          <x-input-error :messages="$errors->get('company_name_kana')" class="mt-2" />
        </div>
      </div>

      {{-- 事業内容 / 紹介 --}}
      <div>
        <x-input-label for="description">
          事業内容 / 紹介 <span class="text-red-500">*</span>
        </x-input-label>
        <textarea id="description" name="description" rows="5" maxlength="2000" required
                  class="mt-1 block w-full border-gray-300 rounded-md">{{ old('description', $company->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
        <p class="text-xs text-gray-500 mt-1">最大 2000 文字</p>
      </div>

      {{-- ロゴ（DBは logo_path に保存） --}}
      <div class="flex items-start gap-6">
        <div class="grow">
          <x-input-label for="logo" value="ロゴ画像（最大10MB / SVG, PNG, JPG, WebP）" />
          <input id="logo" name="logo" type="file"
                 accept=".svg,.svgz,.png,.jpg,.jpeg,.webp"
                 class="mt-1 block w-full">
          <x-input-error :messages="$errors->get('logo')" class="mt-2" />

          @if(!empty($company->logo_path))
            <label class="inline-flex items-center gap-2 mt-3 text-sm">
              <input type="checkbox" name="remove_logo" value="1">
              ロゴを削除する
            </label>
          @endif
        </div>

        @php
          $path = $company->logo_path ?? null;
          $logoUrl = $path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)
                        ? \Illuminate\Support\Facades\Storage::disk('public')->url($path)
                        : asset('images/noimage.svg');
        @endphp
        <div class="shrink-0">
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
                        :value="old('website_url', $company->website_url)" />
          <x-input-error :messages="$errors->get('website_url')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="email" value="代表メール" />
          <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                        inputmode="email" maxlength="255" placeholder="info@example.com"
                        :value="old('email', $company->email)" />
          <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="tel" value="電話番号" />
          <x-text-input id="tel" name="tel" type="text" class="mt-1 block w-full"
                        inputmode="tel" maxlength="20"
                        placeholder="03-1234-5678 / +81-3-1234-5678"
                        :value="old('tel', $company->tel)" />
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
                        required :value="old('postal_code', $company->postal_code)" />
          <x-input-error :messages="$errors->get('postal_code')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="prefecture">
            都道府県 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="prefecture" name="prefecture" type="text" class="mt-1 block w-full"
                        maxlength="255" required :value="old('prefecture', $company->prefecture)" />
          <x-input-error :messages="$errors->get('prefecture')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="city">
            市区町村 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                        maxlength="255" required :value="old('city', $company->city)" />
          <x-input-error :messages="$errors->get('city')" class="mt-2" />
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <x-input-label for="address1">
            番地・建物 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="address1" name="address1" type="text" class="mt-1 block w-full"
                        maxlength="255" required :value="old('address1', $company->address1)" />
          <x-input-error :messages="$errors->get('address1')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="address2" value="部屋番号など" />
          <x-text-input id="address2" name="address2" type="text" class="mt-1 block w-full"
                        maxlength="255" :value="old('address2', $company->address2)" />
          <x-input-error :messages="$errors->get('address2')" class="mt-2" />
        </div>
      </div>

      {{-- 会社情報（必須） --}}
      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <x-input-label for="industry">
            業種 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="industry" name="industry" type="text" class="mt-1 block w-full"
                        maxlength="255" required :value="old('industry', $company->industry)" />
          <x-input-error :messages="$errors->get('industry')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="employees">
            従業員数 <span class="text-red-500">*</span>
          </x-input-label>
          <x-text-input id="employees" name="employees" type="number" min="1" max="1000000" step="1"
                        class="mt-1 block w-full" required :value="old('employees', $company->employees)" />
          <x-input-error :messages="$errors->get('employees')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="founded_on" value="設立日" />
          <x-text-input id="founded_on" name="founded_on" type="date" class="mt-1 block w-full"
                        :value="old('founded_on', optional($company->founded_on)->format('Y-m-d'))" />
          <x-input-error :messages="$errors->get('founded_on')" class="mt-2" />
          <p class="text-xs text-gray-500 mt-1">未来日は不可</p>
        </div>
      </div>

      <div class="flex justify-end">
        <x-primary-button>保存する</x-primary-button>
      </div>
    </form>
  </div>

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
