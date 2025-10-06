<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>投稿一覧</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="padding:20px; line-height:1.6;">
  <h1>投稿一覧</h1>

  @if (session('status'))
    <p style="color:#0a7;">{{ session('status') }}</p>
  @endif

  <div style="margin:10px 0 20px; display:flex; gap:8px; flex-wrap:wrap;">
    <a href="{{ route('admin.posts.create') }}"
       style="display:inline-block; padding:6px 10px; border:1px solid #999; border-radius:6px; text-decoration:none;">＋ 新規作成</a>
    <a href="{{ url('/admin/dashboard') }}"
       style="display:inline-block; padding:6px 10px; border:1px solid #999; border-radius:6px; text-decoration:none;">ダッシュボード</a>
    {{-- もし名前付きルートがあるなら: href="{{ route('admin.dashboard') }}" --}}
  </div>

  {{-- ▼ 検索・絞り込みフォーム（カテゴリ＋キーワード＋おすすめ＋公開状態＋日付） --}}
  <form method="GET" action="{{ route('admin.posts.index') }}" style="margin:0 0 16px; display:grid; gap:8px;">
    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      <input type="search" name="q" value="{{ request('q') }}"
             placeholder="キーワード（スペースでAND）"
             style="flex:1; min-width:240px; padding:6px 10px; border:1px solid #bbb; border-radius:6px;">

      <label for="category" style="font-size:14px; color:#444;">カテゴリ</label>
      <select id="category" name="category" style="padding:6px 10px; border:1px solid #bbb; border-radius:6px;">
        <option value="">すべて</option>
        @foreach($categories as $cat)
          <option value="{{ $cat->id }}" @selected((string)request('category') === (string)$cat->id)>{{ $cat->name }}</option>
        @endforeach
      </select>

      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }}>
        おすすめのみ
      </label>
    </div>

    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
      @php $s = request('status','all'); @endphp
      <select name="status" style="padding:6px 10px; border:1px solid #bbb; border-radius:6px;">
        <option value="all" {{ $s==='all'?'selected':'' }}>すべて</option>
        <option value="published" {{ $s==='published'?'selected':'' }}>公開</option>
        <option value="draft" {{ $s==='draft'?'selected':'' }}>下書き</option>
      </select>

      <input type="date" name="from" value="{{ request('from') }}"
             style="padding:6px 10px; border:1px solid #bbb; border-radius:6px;">
      <span>〜</span>
      <input type="date" name="to" value="{{ request('to') }}"
             style="padding:6px 10px; border:1px solid #bbb; border-radius:6px;">

      <button type="submit"
              style="padding:6px 10px; border:1px solid #4f46e5; background:#4f46e5; color:#fff; border-radius:6px;">
        検索
      </button>
      <a href="{{ route('admin.posts.index') }}"
         style="padding:6px 10px; border:1px solid #999; border-radius:6px; text-decoration:none; color:#333;">
        クリア
      </a>
    </div>
  </form>

  @if ($posts->count())
    <ul style="padding-left:1.2em;">
      @foreach ($posts as $post)
        <li style="margin-bottom:10px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
          @if ($post->thumbnail_path)
            <img src="{{ Storage::url($post->thumbnail_path) }}" alt=""
                 style="width:60px; height:60px; object-fit:cover; border:1px solid #ddd; border-radius:4px;">
          @endif

          <a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title ?: '(無題)' }}</a>

          {{-- 投稿日時 --}}
          <small style="color:#666;"> / {{ optional($post->published_at)->format('Y-m-d H:i') }}</small>

          {{-- カテゴリ（単一） --}}
          <span style="margin-left:6px; font-size:12px; padding:2px 8px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px;">
            {{ $post->category?->name ?? 'カテゴリなし' }}
          </span>

          {{-- 読む時間バッジ --}}
          @if(!is_null($post->reading_time) && $post->reading_time > 0)
            <span style="margin-left:6px; font-size:12px; padding:2px 8px;
                         background:#eef2ff; border:1px solid #c7d2fe; border-radius:999px;">
              {{ $post->reading_time }}分
            </span>
          @endif

          {{-- おすすめフラグ --}}
          <span style="margin-left:6px; font-size:12px; padding:2px 8px; background:#fff7ed; border:1px solid #fed7aa; border-radius:999px;">
            {{ $post->is_featured ? 'おすすめ' : '—' }}
          </span>

          {{-- 削除ボタン --}}
          <form method="POST" action="{{ route('admin.posts.destroy', $post) }}"
                style="display:inline; margin-left:8px;"
                onsubmit="return window.confirm('本当に削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    style="color:#c00; background:transparent; border:1px solid #c00; padding:2px 6px; border-radius:4px;">
              削除
            </button>
          </form>
        </li>
      @endforeach
    </ul>

    <div style="margin-top:16px;">
      {{ $posts->links() }}
    </div>
  @else
    <p>投稿がありません。</p>
  @endif
</body>
</html>
