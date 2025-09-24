<section class="max-w-2xl mx-auto mt-10 mb-16">
  @if (session('status'))
    <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
      {{ session('status') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700">
      入力内容を確認してください。
    </div>
  @endif

  <div class="rounded-2xl border border-gray-200 bg-white/90 shadow-sm">
    <div class="px-6 py-5 border-b border-gray-100">
      <h3 class="text-lg font-semibold">この求人に応募する</h3>
      <p class="mt-1 text-sm text-gray-500">担当者からご連絡できる連絡先をご入力ください。</p>
    </div>

    <form action="{{ route('front.jobs.apply', $job) }}" method="POST" class="px-6 py-6">
      @csrf

      <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">お名前 <span class="text-red-500">*</span></label>
          <input name="name" type="text" required autocomplete="name"
                 value="{{ old('name') }}"
                 class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
          @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス <span class="text-red-500">*</span></label>
          <input name="email" type="email" required autocomplete="email"
                 value="{{ old('email') }}"
                 class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
          @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="md:col-span-1">
          <label class="block text-sm font-medium text-gray-700 mb-1">電話番号</label>
          <input name="phone" type="tel" autocomplete="tel"
                 value="{{ old('phone') }}"
                 class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">
        </div>

        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">志望動機・メッセージ</label>
          <textarea name="message" rows="5"
                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2">{{ old('message') }}</textarea>
        </div>
      </div>

      <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <p class="text-xs text-gray-500">
          送信いただいた情報は採用目的以外では使用しません。
          <a href="/privacy" class="underline hover:no-underline">プライバシーポリシー</a>
        </p>
        <button type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-2.5 text-white font-medium hover:bg-indigo-700 transition">
          応募する
        </button>
      </div>
    </form>
  </div>
</section>
