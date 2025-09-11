<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">投稿作成</h2>
  </x-slot>

  <div class="p-6">
    {{-- 入力エラー表示 --}}
    @if ($errors->any())
      <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-700">
        <p class="font-semibold">入力エラー</p>
        <ul class="list-disc pl-5">
          @foreach ($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form id="post-create" action="{{ route('admin.posts.store') }}" method="POST"
          enctype="multipart/form-data" class="space-y-6">
      @csrf

      {{-- 本体フォーム（ここに file input も含まれている想定） --}}
      @include('admin.posts._form', ['post' => $post])

      {{-- ★ここがポイント：このパーシャルは <form> を含めないこと！ --}}
      @includeIf('admin.posts._category_tags')

      <x-primary-button type="submit">保存</x-primary-button>
    </form>
  </div>

  {{-- パーマリンクUIのJS（1回だけ） --}}
  @include('admin.posts._slug-script')
</x-app-layout>
