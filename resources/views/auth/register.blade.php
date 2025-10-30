{{-- resources/views/auth/register.blade.php --}}
<x-guest-layout>
    {{-- ブラウザ側バリデーションは一旦オフ。判定はサーバ側に寄せる --}}
    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        {{-- 氏名 --}}
        <div>
            <x-input-label for="name">
                氏名 <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input
                id="name"
                class="block mt-1 w-full"
                type="text"
                name="name"
                :value="old('name')"
                placeholder="例：山田 太郎"
                required
                autofocus
                autocomplete="name"
                maxlength="20"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        {{-- メールアドレス --}}
        <div class="mt-4">
            <x-input-label for="email">
                メールアドレス <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input
                id="email"
                class="block mt-1 w-full"
                type="email"
                name="email"
                :value="old('email')"
                placeholder="例：user@example.com"
                required
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- パスワード（8文字以上・英大文字/小文字を含む。数字・記号は任意） --}}
        <div class="mt-4">
            <x-input-label for="password">
                パスワード <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input
                id="password"
                class="block mt-1 w-full"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="8文字以上、英大文字・小文字を含む（数字・記号は任意）"
            />
            <p class="text-sm text-gray-500 mt-1">
                ※ 例：Abcdefgh ／ AbcDEFGH1!（数字・記号は任意）
            </p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- パスワード（確認） --}}
        <div class="mt-4">
            <x-input-label for="password_confirmation">
                パスワード（確認） <span class="text-red-500">*</span>
            </x-input-label>
            <x-text-input
                id="password_confirmation"
                class="block mt-1 w-full"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="もう一度同じパスワードを入力してください"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        {{-- フッター（リンク＋送信ボタン） --}}
        <div class="flex items-center justify-end mt-6">
            <a
                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                href="{{ route('login') }}"
            >
                すでに登録済みの方はこちら
            </a>

            {{-- submit 固定（コンポーネント依存を排除） --}}
            <button
                type="submit"
                class="ms-4 inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-md
                       font-semibold text-xs uppercase tracking-widest hover:bg-gray-800
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                       disabled:opacity-25 transition ease-in-out duration-150"
            >
                登録する
            </button>
        </div>
    </form>

    {{-- グローバルエラー（原因の可視化用） --}}
    @if ($errors->any())
        <div class="mt-6 p-3 bg-red-50 text-red-700 text-sm rounded">
            <div class="font-bold mb-1">エラー:</div>
            <ul class="list-disc ml-5 space-y-0.5">
                @foreach ($errors->all() as $msg)
                    <li>{{ $msg }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</x-guest-layout>
