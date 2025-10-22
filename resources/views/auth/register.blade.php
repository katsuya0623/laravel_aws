<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- 氏名（日本語OK・記号/絵文字NGはサーバで検証。ここでは20文字制限のみ） -->
        <div>
            <x-input-label for="name">
                氏名 <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input id="name" class="block mt-1 w-full"
                type="text"
                name="name"
                :value="old('name')"
                placeholder="例：山田 太郎"
                required autofocus autocomplete="name"
                maxlength="20" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- メールアドレス -->
        <div class="mt-4">
            <x-input-label for="email">
                メールアドレス <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input id="email" class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                placeholder="例：user@example.com"
                required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- パスワード（8文字以上・英大文字と小文字を含む。数字・記号は任意） -->
        <div class="mt-4">
            <x-input-label for="password">
                パスワード <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required autocomplete="new-password"
                placeholder="8文字以上、英大文字・小文字を含む（数字・記号は任意）"
                pattern="(?=.*[a-z])(?=.*[A-Z]).{8,}"
                title="8文字以上で、英大文字と小文字をそれぞれ1文字以上含めてください。" />
            <p class="text-sm text-gray-500 mt-1">
                ※ 例：Abcdefgh ／ AbcDEFGH1!（数字・記号は任意）
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- パスワード（確認） -->
        <div class="mt-4">
            <x-input-label for="password_confirmation">
                パスワード（確認） <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required autocomplete="new-password"
                placeholder="もう一度同じパスワードを入力してください" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- 登録ボタン -->
        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md
               focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
               href="{{ route('login') }}">
                すでに登録済みの方はこちら
            </a>

            <x-primary-button class="ms-4">
                登録する
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
