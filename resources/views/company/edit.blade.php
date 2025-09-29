<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">企業情報の編集</h2>
  </x-slot>

  <div class="p-6 max-w-4xl space-y-6">
    @if (session('status'))
      <p class="text-emerald-600 text-sm">{{ session('status') }}</p>
    @endif

    <form method="POST"
          action="{{ route('user.company.update') }}"
          enctype="multipart/form-data"
          class="space-y-6">
      @csrf

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <x-input-label for="company_name" value="会社名 *" />
          <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full"
                        :value="old('company_name', $company->company_name)" required />
          <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="company_name_kana" value="会社名（カナ）" />
          <x-text-input id="company_name_kana" name="company_name_kana" type="text" class="mt-1 block w-full"
                        :value="old('company_name_kana', $company->company_name_kana)" />
          <x-input-error :messages="$errors->get('company_name_kana')" class="mt-2" />
        </div>
      </div>

      <div>
        <x-input-label for="description" value="事業内容 / 紹介" />
        <textarea id="description" name="description" rows="5"
                  class="mt-1 block w-full border-gray-300 rounded-md">{{ old('description', $company->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
      </div>

      {{-- ロゴ（DBは company_profiles.logo_path に保存） --}}
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
                        : asset('images/noimage.svg'); // 任意のフォールバック
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

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <x-input-label for="website_url" value="Webサイト" />
          <x-text-input id="website_url" name="website_url" type="url" class="mt-1 block w-full"
                        :value="old('website_url', $company->website_url)" />
          <x-input-error :messages="$errors->get('website_url')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="email" value="代表メール" />
          <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                        :value="old('email', $company->email)" />
          <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
          <x-input-label for="tel" value="電話番号" />
          <x-text-input id="tel" name="tel" type="text" class="mt-1 block w-full"
                        :value="old('tel', $company->tel)" />
          <x-input-error :messages="$errors->get('tel')" class="mt-2" />
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <x-input-label for="postal_code" value="郵便番号" />
          <x-text-input id="postal_code" name="postal_code" type="text" class="mt-1 block w-full"
                        :value="old('postal_code', $company->postal_code)" />
        </div>
        <div>
          <x-input-label for="prefecture" value="都道府県" />
          <x-text-input id="prefecture" name="prefecture" type="text" class="mt-1 block w-full"
                        :value="old('prefecture', $company->prefecture)" />
        </div>
        <div>
          <x-input-label for="city" value="市区町村" />
          <x-text-input id="city" name="city" type="text" class="mt-1 block w-full"
                        :value="old('city', $company->city)" />
        </div>
      </div>

      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <x-input-label for="address1" value="番地・建物" />
          <x-text-input id="address1" name="address1" type="text" class="mt-1 block w-full"
                        :value="old('address1', $company->address1)" />
        </div>
        <div>
          <x-input-label for="address2" value="部屋番号など" />
          <x-text-input id="address2" name="address2" type="text" class="mt-1 block w-full"
                        :value="old('address2', $company->address2)" />
        </div>
      </div>

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <x-input-label for="industry" value="業種" />
          <x-text-input id="industry" name="industry" type="text" class="mt-1 block w-full"
                        :value="old('industry', $company->industry)" />
        </div>
        <div>
          <x-input-label for="employees" value="従業員数" />
          <x-text-input id="employees" name="employees" type="number" min="1" step="1" class="mt-1 block w-full"
                        :value="old('employees', $company->employees)" />
        </div>
        <div>
          <x-input-label for="founded_on" value="設立日" />
          <x-text-input id="founded_on" name="founded_on" type="date" class="mt-1 block w-full"
                        :value="old('founded_on', optional($company->founded_on)->format('Y-m-d'))" />
        </div>
      </div>

      <div class="flex justify-end">
        <x-primary-button>保存する</x-primary-button>
      </div>
    </form>
  </div>

  {{-- 選択したロゴを即時プレビュー --}}
  <script>
    const input = document.getElementById('logo');
    const img   = document.getElementById('logoPreview');
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
