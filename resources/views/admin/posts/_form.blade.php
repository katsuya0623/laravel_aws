@php
  $baseUrl     = url('/posts');
  $currentSlug = old('slug', $post->slug ?? '');
@endphp

{{-- タイトル（※ JSが拾うので id="js-title" のまま） --}}
<div class="mb-4">
  <x-input-label for="js-title" value="タイトル" />
  <x-text-input id="js-title" name="title" type="text" class="mt-1 block w-full"
                :value="old('title', $post->title ?? '')" required />
  <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

{{-- ★ パーマリンクUI（現在のIDのまま） --}}
<div class="mb-6 rounded border border-gray-200 bg-gray-50 p-3">
  <div id="js-permalink-view" class="flex items-center gap-2 flex-wrap">
    <span class="text-gray-700">{{ $baseUrl }}/</span>
    <strong id="js-slug-text" class="text-gray-900">{{ $currentSlug ?: '（未設定）' }}</strong>
    <a href="javascript:void(0)" id="js-edit-slug" class="text-blue-600 underline text-sm">編集</a>
  </div>

  <div id="js-permalink-edit" class="mt-2 hidden">
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-gray-700">{{ $baseUrl }}/</span>
      <input id="js-slug-input" name="slug" type="text"
             value="{{ $currentSlug }}"
             placeholder="example-post"
             class="border-gray-300 rounded px-2 py-1 min-w-[220px]">
      <button type="button" id="js-ok" class="px-3 py-1 rounded bg-sky-600 text-white">OK</button>
      <button type="button" id="js-cancel" class="px-3 py-1 rounded border">キャンセル</button>
    </div>
    <div class="text-xs text-gray-500 mt-1">半角英数字・ハイフン・アンダースコアのみ</div>
    <x-input-error :messages="$errors->get('slug')" class="mt-2" />
  </div>
</div>

{{-- 紹介文（一覧表示用） --}}
<div class="mb-4">
  <x-input-label for="excerpt" value="紹介文（一覧に表示・200文字まで）" />
  <textarea id="excerpt" name="excerpt"
            class="mt-1 block w-full border-gray-300 rounded"
            rows="3">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
  <x-input-error :messages="$errors->get('excerpt')" class="mt-2" />
</div>

{{-- 本文 --}}
<div class="mb-4">
  <x-input-label for="body" value="本文" />
  <textarea id="body" name="body" class="mt-1 block w-full border-gray-300 rounded" rows="8">{{ old('body', $post->body ?? '') }}</textarea>
  <x-input-error :messages="$errors->get('body')" class="mt-2" />
</div>

{{-- SEO タイトル／説明 --}}
<div class="mb-4 grid grid-cols-1 md:grid-cols-2 gap-6">
  <div>
    <x-input-label for="seo_title" value="検索で出るタイトル（70文字まで）" />
    <x-text-input id="seo_title" name="seo_title" type="text"
                  class="mt-1 block w-full"
                  :value="old('seo_title', $post->seo_title ?? '')" />
    <x-input-error :messages="$errors->get('seo_title')" class="mt-2" />
  </div>
  <div>
    <x-input-label for="seo_description" value="検索で出る説明文（160文字まで）" />
    <x-text-input id="seo_description" name="seo_description" type="text"
                  class="mt-1 block w-full"
                  :value="old('seo_description', $post->seo_description ?? '')" />
    <x-input-error :messages="$errors->get('seo_description')" class="mt-2" />
  </div>
</div>

{{-- 公開日・おすすめ・読む時間 --}}
<div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
  <div>
    <x-input-label for="published_at" value="公開日" />
    <x-text-input id="published_at" name="published_at" type="datetime-local" class="mt-1"
                  :value="old('published_at', optional($post->published_at ?? null)->format('Y-m-d\TH:i'))" />
    <x-input-error :messages="$errors->get('published_at')" class="mt-2" />
  </div>

  <div class="mt-6 md:mt-0">
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="is_featured" value="1"
             class="rounded border-gray-300"
             {{ old('is_featured', $post->is_featured ?? false) ? 'checked' : '' }}>
      <span class="text-sm text-gray-700">おすすめにする</span>
    </label>
    <x-input-error :messages="$errors->get('is_featured')" class="mt-2" />
  </div>

  <div>
    <x-input-label for="reading_time" value="読む時間（分）" />
    <x-text-input id="reading_time" name="reading_time" type="number" min="1" class="mt-1 w-32"
                  :value="old('reading_time', $post->reading_time ?? '')" />
    <x-input-error :messages="$errors->get('reading_time')" class="mt-2" />
  </div>
</div>

{{-- サムネイル（保存後はブラウザ仕様でファイル欄が空に戻ります） --}}
<div class="mb-6">
  <x-input-label for="thumbnail" value="サムネイル" />

  {{-- ✅ アクセサに合わせて条件を変更 --}}
  @if(!empty($post->thumbnail_url))
    <div class="flex items-start gap-4 mb-3">
      <img src="{{ $post->thumbnail_url }}" alt="現在の画像"
           class="h-24 w-24 object-cover rounded border" id="thumbCurrent">
      <label class="inline-flex items-center gap-2 mt-1">
        <input type="checkbox" name="thumbnail_remove" value="1" class="rounded border-gray-300">
        <span class="text-sm text-gray-700">この画像を削除する</span>
      </label>
    </div>
  @endif

  <input id="thumbnail" name="thumbnail" type="file" accept="image/*" class="mt-1 block">

  {{-- 事前アップロードの保存先（成功時のみJSが値を入れる） --}}
  <input type="hidden" name="preuploaded_thumbnail_path"
         id="preuploaded_thumbnail_path"
         value=""> {{-- ←常に空からスタート --}}

  {{-- 選択直後のプレビュー --}}
  <img id="thumbPreview" class="mt-2 h-24 w-24 object-cover rounded border hidden" alt="preview">

  <p class="text-xs text-gray-500 mt-1">
    送信後はファイル欄の表示は空に戻ります（仕様）。保存結果は上のプレビューで確認してください。
  </p>

  <x-input-error :messages="$errors->get('thumbnail')" class="mt-2" />
</div>

{{-- 事前アップロードJS（成功時のみ hidden をセット／失敗時は必ず空） --}}
<script>
document.getElementById('thumbnail')?.addEventListener('change', async (e) => {
  const f = e.target.files?.[0];
  const hidden = document.getElementById('preuploaded_thumbnail_path');
  if (hidden) hidden.value = ''; // ☆ まず必ず空に戻す
  if (!f) return;

  // 即時プレビュー
  const img = document.getElementById('thumbPreview');
  if (img) { img.src = URL.createObjectURL(f); img.classList.remove('hidden'); }

  try {
    const fd = new FormData();
    fd.append('thumbnail', f);

    const res  = await fetch('{{ route('preupload') }}', {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      body: fd
    });

    const okJson = res.ok ? await res.json().catch(() => null) : null;

    if (okJson && okJson.ok && okJson.path) {
      hidden.value = okJson.path;            // ☆ 成功時だけセット
      const current = document.getElementById('thumbCurrent');
      if (current && okJson.url) current.src = okJson.url;
    } else {
      hidden.value = ''; // ☆ 必ず空のまま
    }

  } catch (err) {
    if (hidden) hidden.value = '';
  }
});
</script>
