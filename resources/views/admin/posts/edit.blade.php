<x-app-layout>
  <x-slot name="header">
    <h2 class="font-semibold text-xl">投稿編集</h2>
  </x-slot>

  <div class="p-6 max-w-3xl space-y-6">
    @if ($errors->any())
      <div class="text-red-600 text-sm">
        <ul class="list-disc pl-5">
          @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @if (session('status'))
      <p class="text-emerald-600 text-sm">{{ session('status') }}</p>
    @endif

    {{-- 保存フォーム --}}
    <form id="post-edit"
          method="POST"
          action="{{ route('admin.posts.update', $post) }}"
          enctype="multipart/form-data"
          class="space-y-6">
      @csrf
      @method('PUT')

      {{-- タイトル（slug自動生成が拾えるように id="js-title"） --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">タイトル</label>
        <input id="js-title" type="text" name="title"
               value="{{ old('title', $post->title) }}"
               class="mt-1 block w-full rounded-md border-gray-300" />
      </div>

      {{-- パーマリンク --}}
      @php
        $permalinkPrefix = rtrim(url('/posts'), '/') . '/';
        $currentSlug = old('slug', $post->slug ?? '');
      @endphp
      <div class="rounded-md border border-gray-200 bg-gray-50 p-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">公開URL（パーマリンク）</label>

        <div id="permalink-row" class="flex flex-wrap items-center gap-2">
          <span class="text-sm select-text">
            <span id="permalink-prefix">{{ $permalinkPrefix }}</span><span id="permalink-slug">{{ $currentSlug }}</span>
          </span>
          <button type="button" id="edit-slug-btn" class="px-2.5 py-1 text-sm border rounded-md bg-white">編集</button>
          <a id="view-link" href="{{ $permalinkPrefix . $currentSlug }}" target="_blank" rel="noopener"
             class="px-2.5 py-1 text-sm border rounded-md bg-white">表示</a>
          <button type="button" id="copy-link-btn" class="px-2.5 py-1 text-sm border rounded-md bg-white">コピー</button>
        </div>

        {{-- 入力ボックス（常に form に含める・UI的には隠す） --}}
        <div id="permalink-editor" class="mt-3 hidden items-center gap-2">
          <span class="text-sm">{{ $permalinkPrefix }}</span>
          <input id="slug" name="slug" type="text" value="{{ $currentSlug }}"
                 placeholder="example-post"
                 class="w-64 rounded-md border-gray-300" />
          <button type="button" id="slug-ok-btn" class="px-3 py-1.5 text-sm border rounded-md bg-white">OK</button>
          <button type="button" id="slug-cancel-btn" class="px-3 py-1.5 text-sm border rounded-md bg-white">キャンセル</button>
          <small class="text-gray-500">半角英数字・ハイフン・アンダースコア・ドットのみ（自動整形）</small>
        </div>
      </div>

      {{-- 紹介文 --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">紹介文（一覧に表示・200文字まで）</label>
        <textarea name="excerpt" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300">{{ old('excerpt', $post->excerpt) }}</textarea>
      </div>

      {{-- 本文 --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">本文</label>
        <textarea name="body" rows="8"
                  class="mt-1 block w-full rounded-md border-gray-300">{{ old('body', $post->body) }}</textarea>
      </div>

      {{-- SEO --}}
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">検索で出るタイトル（70文字まで）</label>
          <input type="text" name="seo_title"
                 value="{{ old('seo_title', $post->seo_title) }}"
                 class="mt-1 block w-full rounded-md border-gray-300" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">検索で出る説明文（160文字まで）</label>
          <input type="text" name="seo_description"
                 value="{{ old('seo_description', $post->seo_description) }}"
                 class="mt-1 block w-full rounded-md border-gray-300" />
        </div>
      </div>

      {{-- 公開・おすすめ・読了時間 --}}
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700">公開日</label>
          <input type="datetime-local" name="published_at"
                 value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\TH:i')) }}"
                 class="mt-1 block w-full rounded-md border-gray-300" />
        </div>

        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="is_featured" value="1"
                 class="rounded border-gray-300"
                 {{ old('is_featured', $post->is_featured ?? false) ? 'checked' : '' }}>
          <span class="text-sm text-gray-700">おすすめにする</span>
        </label>

        <div>
          <label class="block text-sm font-medium text-gray-700">読む時間（分）</label>
          <input type="number" min="1" name="reading_time"
                 value="{{ old('reading_time', $post->reading_time) }}"
                 class="mt-1 block w-32 rounded-md border-gray-300" />
        </div>
      </div>

      {{-- サムネイル --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">サムネイル</label>

        @if ($post->thumbnail_path)
          <img src="{{ $post->thumbnail_url }}"
               alt="現在のサムネイル"
               class="mt-2 max-w-xs rounded border" id="thumbCurrent">
          <label class="mt-2 block">
            <input type="checkbox" name="thumbnail_remove" value="1" class="mr-2">
            サムネイルを削除
          </label>
        @endif

        <input type="file" name="thumbnail" id="thumbnail" accept="image/*"
               class="mt-2 block" />

        {{-- 事前アップロードで返るパスを格納（Controller 側でこれを優先採用） --}}
        <input type="hidden" name="preuploaded_thumbnail_path" id="preuploaded_thumbnail_path" value="{{ old('preuploaded_thumbnail_path') }}">

        {{-- 選択直後のプレビュー（任意） --}}
        <img id="thumbPreview" class="mt-2 max-w-xs rounded border hidden" alt="preview" />
      </div>

      {{-- カテゴリ --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">カテゴリ</label>
        <select name="category_id"
                class="mt-1 block w-60 rounded-md border-gray-300">
          <option value="">未選択</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}"
              {{ (string)old('category_id', optional($post->category)->id) === (string)$cat->id ? 'selected' : '' }}>
              {{ $cat->name }}
            </option>
          @endforeach
        </select>
      </div>

      {{-- タグ（複数選択） --}}
      <div>
        <label class="block text-sm font-medium text-gray-700">タグ（複数選択可）</label>
        @php
          $selectedTags = collect(old('tags', $post->exists ? $post->tags->pluck('id')->all() : []))
                          ->map(fn($v) => (string)$v)->all();
        @endphp
        <select name="tags[]" multiple size="6"
                class="mt-1 block min-w-[200px] rounded-md border-gray-300">
          @foreach($tags as $tag)
            <option value="{{ $tag->id }}" {{ in_array((string)$tag->id, $selectedTags, true) ? 'selected' : '' }}>
              {{ $tag->name }}
            </option>
          @endforeach
        </select>
      </div>

      <x-primary-button>保存</x-primary-button>
    </form>

    {{-- 削除 --}}
    <form method="POST"
          action="{{ route('admin.posts.destroy', $post) }}"
          onsubmit="return confirm('本当に削除しますか？');">
      @csrf
      @method('DELETE')
      <x-danger-button>削除</x-danger-button>
    </form>
  </div>

  {{-- スラッグ＆パーマリンクJS（この画面専用。_slug-script は読み込まない） --}}
  <script>
    (function () {
      // コントローラの /^[A-Za-z0-9\-_.]+$/ に合わせる
      const toSlug = (s) => (s || '')
        .normalize('NFKC')
        .replace(/[^A-Za-z0-9._\-\s]+/g, '')
        .replace(/\s+/g, '-')
        .replace(/\-+/g, '-')
        .replace(/^[\-\._]+|[\-\._]+$/g, '')
        .toLowerCase();

      const $ = (sel) => document.querySelector(sel);

      const prefixEl  = $('#permalink-prefix');
      const slugSpan  = $('#permalink-slug');
      const row       = $('#permalink-row');
      const editor    = $('#permalink-editor');
      const slugInput = $('#slug');
      const viewLink  = $('#view-link');
      const titleEl   = document.getElementById('js-title');

      let manual = !!(slugInput?.value);

      function apply(slug) {
        slug = toSlug(slug);
        if (slugSpan)  slugSpan.textContent = slug;
        if (slugInput) slugInput.value = slug;
        if (viewLink)  viewLink.href = (prefixEl?.textContent || '') + slug;
      }

      document.getElementById('edit-slug-btn')?.addEventListener('click', () => {
        row?.classList.add('hidden');
        editor?.classList.remove('hidden');
        slugInput?.focus(); slugInput?.select();
      });
      document.getElementById('slug-cancel-btn')?.addEventListener('click', () => {
        editor?.classList.add('hidden');
        row?.classList.remove('hidden');
      });
      document.getElementById('slug-ok-btn')?.addEventListener('click', () => {
        apply(slugInput?.value || '');
        manual = true;
        editor?.classList.add('hidden');
        row?.classList.remove('hidden');
      });
      document.getElementById('copy-link-btn')?.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText((prefixEl?.textContent || '') + (slugSpan?.textContent || ''));
          alert('URLをコピーしました');
        } catch (e) { alert('コピーに失敗しました'); }
      });

      // タイトル → slug（手動編集までは追随）
      if (titleEl && slugInput) {
        titleEl.addEventListener('input', () => {
          if (manual) return;
          apply(titleEl.value);
        });
      }
      slugInput?.addEventListener('input', () => { manual = true; });
    })();
  </script>

  {{-- 画像の事前アップロード（成功時のみ hidden をセット） --}}
  <script>
    (function () {
      const input   = document.getElementById('thumbnail');
      const hidden  = document.getElementById('preuploaded_thumbnail_path');
      const preview = document.getElementById('thumbPreview');
      const current = document.getElementById('thumbCurrent');
      if (!input || !hidden) return;

      input.addEventListener('change', async (e) => {
        const f = e.target.files?.[0];
        hidden.value = ''; // まず必ず空に戻す（失敗時の取りこぼし防止）
        if (!f) return;

        if (preview) {
          preview.src = URL.createObjectURL(f);
          preview.classList.remove('hidden');
        }

        try {
          const fd = new FormData();
          fd.append('thumbnail', f);
          const res  = await fetch('{{ route('preupload') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            credentials: 'same-origin',
            body: fd
          });
          const json = res.ok ? await res.json().catch(() => null) : null;
          if (json && json.ok && json.path) {
            hidden.value = json.path;
            if (current && json.url) current.src = json.url;
          } else {
            hidden.value = '';
          }
        } catch (err) {
          hidden.value = '';
          alert('画像のアップロードに失敗しました: ' + (err?.message || err));
        }
      });
    })();
  </script>
</x-app-layout>
