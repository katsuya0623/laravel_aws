{{-- フロント用 管理バー（ログイン時のみ） --}}
<div id="admin-bar"
     style="
      position:fixed; left:0; right:0; top:0; z-index:9999;
      height:40px; display:flex; align-items:center; gap:12px;
      padding:0 12px; background:#111; color:#fff; font-size:13px;
      -webkit-font-smoothing:antialiased; font-family:system-ui, -apple-system, Segoe UI, Roboto, 'Helvetica Neue', Arial, 'Noto Sans JP', sans-serif;
     ">
  <a href="{{ url('/') }}" style="color:#9ae6b4; text-decoration:none; font-weight:700;">SITE</a>
  <span style="opacity:.6;">|</span>

  <a href="{{ route('dashboard') }}" style="color:#fff; text-decoration:none;">ダッシュボード</a>
  <a href="{{ route('admin.posts.index') }}" style="color:#fff; text-decoration:none;">投稿一覧</a>
  <a href="{{ route('admin.posts.create') }}" style="color:#fff; text-decoration:none;">＋新規投稿</a>
  <a href="{{ route('front.articles.index') }}" style="color:#fff; text-decoration:none;">フロント記事一覧</a>

  <span style="margin-left:auto; opacity:.85;">
    {{ auth()->user()->name ?? 'User' }} でログイン中
  </span>

  <form id="logout-form" method="POST" action="{{ route('logout') }}" style="margin:0 0 0 8px;">
    @csrf
    <button type="submit"
            style="background:#2563eb; color:#fff; border:0; padding:6px 10px;
                   border-radius:6px; cursor:pointer;">
      ログアウト
    </button>
  </form>
</div>

{{-- バー分だけ押し下げる（40px分） --}}
<div style="height:40px;"></div>

<style>
  @media (max-width: 420px) {
    #admin-bar { font-size:12px; gap:8px; }
    #admin-bar a:nth-of-type(3), /* 投稿一覧 */
    #admin-bar a:nth-of-type(4)  /* ＋新規投稿 */
    { display:none; }
  }
</style>
