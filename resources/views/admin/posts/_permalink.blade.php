@php
  $baseUrl = url('/posts');
  $currentSlug = old('slug', $post->slug ?? '');
@endphp
<div class="mb-6 rounded border border-gray-200 bg-gray-50 p-3">
  <div class="text-sm text-gray-600 mb-2">パーマリンク</div>
  <div id="js-permalink-view" class="flex items-center gap-2 flex-wrap">
    <span class="text-gray-700">{{ $baseUrl }}/</span>
    <strong id="js-slug-text" class="text-gray-900">{{ $currentSlug ?: '（未設定）' }}</strong>
    <a href="javascript:void(0)" id="js-edit-slug" class="text-blue-600 underline text-sm">編集</a>
  </div>
  <div id="js-permalink-edit" class="mt-2 hidden">
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-gray-700">{{ $baseUrl }}/</span>
      <input id="js-slug-input" name="slug" type="text" value="{{ $currentSlug }}"
             placeholder="example-post" class="border-gray-300 rounded px-2 py-1 min-w-[220px]">
      <button type="button" id="js-ok" class="px-3 py-1 rounded bg-sky-600 text-white">OK</button>
      <button type="button" id="js-cancel" class="px-3 py-1 rounded border">キャンセル</button>
    </div>
    <div class="text-xs text-gray-500 mt-1">半角英数字・ハイフン・アンダースコアのみ</div>
    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
  </div>
</div>
