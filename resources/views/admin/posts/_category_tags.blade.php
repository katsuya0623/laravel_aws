@php
  $categories = $categories ?? \App\Models\Category::orderBy('name')->get();
  $tags = $tags ?? \App\Models\Tag::orderBy('name')->get();
@endphp

<div class="mt-6 space-y-4">
  <div>
    <label class="block mb-1 font-medium">カテゴリ</label>
    <select name="category_id" class="w-full border rounded px-3 py-2">
      <option value="">未設定</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}" @selected(old('category_id', $post->category_id ?? null)==$cat->id)>
          {{ $cat->name }}
        </option>
      @endforeach
    </select>
    @error('category_id')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
  </div>

  <div>
    <label class="block mb-1 font-medium">タグ（複数選択可）</label>
    <select name="tags[]" multiple size="6" class="w-full border rounded px-3 py-2">
      @php $selectedIds = collect(old('tags', $post->tags->pluck('id')->all() ?? [])); @endphp
      @foreach($tags as $tag)
        <option value="{{ $tag->id }}" @selected($selectedIds->contains($tag->id))>{{ $tag->name }}</option>
      @endforeach
    </select>
    @error('tags')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
  </div>
</div>
