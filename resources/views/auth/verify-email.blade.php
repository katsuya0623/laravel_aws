<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 leading-relaxed">
        ご登録ありがとうございます。<br>
        サービスをご利用いただく前に、登録したメールアドレスの認証をお願いいたします。<br>
        先ほどお送りした確認メールのリンクをクリックして認証を完了してください。<br><br>
        メールが届いていない場合は、以下のボタンから再送信できます。
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-green-600">
            新しい確認メールを送信しました。<br>
            メールボックスをご確認ください。
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        {{-- 確認メール再送 --}}
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button>
                確認メールを再送信する
            </x-primary-button>
        </form>

        {{-- ログアウト --}}
        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md
                       focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
                ログアウト
            </button>
        </form>
    </div>
</x-guest-layout>
