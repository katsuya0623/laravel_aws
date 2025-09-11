<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-semibold tracking-tight">サインイン</h1>
        <p class="mt-1 text-sm text-slate-500">ダッシュボードへアクセス</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email -->
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">メールアドレス</label>
            <input
                id="email"
                name="email"
                type="email"
                required
                autofocus
                autocomplete="username"
                value="{{ old('email') }}"
                placeholder="you@example.com"
                class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-gray-900 placeholder:text-slate-400
                       focus:border-emerald-500 focus:ring-emerald-500"
            >
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="mb-1 flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-gray-700">パスワード</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}"
                       class="text-xs text-emerald-600 hover:text-emerald-500 underline underline-offset-2">
                        パスワードをお忘れですか？
                    </a>
                @endif
            </div>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                placeholder="••••••••"
                class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-gray-900 placeholder:text-slate-400
                       focus:border-emerald-500 focus:ring-emerald-500"
            >
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember -->
        <label class="flex items-center gap-2">
            <input type="checkbox" name="remember"
                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <span class="text-sm text-gray-600">ログイン状態を保持する</span>
        </label>

        <!-- Submit -->
        <button
            type="submit"
            class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-600 py-3 font-medium text-white
                   shadow-lg shadow-emerald-900/20 hover:bg-emerald-500 focus:outline-none focus-visible:ring-4
                   focus-visible:ring-emerald-300"
        >
            ログイン
        </button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-slate-500">
                アカウント未作成？ <a href="{{ route('register') }}" class="text-emerald-600 hover:text-emerald-500 underline underline-offset-2">新規登録</a>
            </p>
        @endif
    </form>
</x-guest-layout>
