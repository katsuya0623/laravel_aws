<script>
(function(){
  // コントローラの /^[A-Za-z0-9\-_.]+$/ に合わせて生成
  const toSlug = (s) => {
    return (s || '')
      .normalize('NFKC')          // 全角→半角など幅の正規化
      .replace(/[^A-Za-z0-9._\-\s]+/g, '') // 許可以外の文字を削除（ドット・アンダースコア許可）
      .replace(/\s+/g, '-')       // 空白→ハイフン
      .replace(/\-+/g, '-')       // 連続ハイフン圧縮
      .replace(/^[\-\._]+|[\-\._]+$/g, '') // 先頭末尾の -_. を除去
      .toLowerCase();
  };

  const $title     = document.getElementById('js-title') || document.getElementById('title');
  const $view      = document.getElementById('js-permalink-view');
  const $edit      = document.getElementById('js-permalink-edit');
  const $slugText  = document.getElementById('js-slug-text');
  const $slugIn    = document.getElementById('js-slug-input');
  const $btnEdit   = document.getElementById('js-edit-slug');
  const $btnOk     = document.getElementById('js-ok');
  const $btnCancel = document.getElementById('js-cancel');

  if (!$title || !$view) return;

  let manual = !!$slugIn?.value; // 既にスラッグがある場合は手動扱い

  // タイトル→スラッグ自動反映（手動になったら停止）
  $title.addEventListener('input', () => {
    if (manual) return;
    const s = toSlug($title.value);
    if ($slugText) $slugText.textContent = s || '（未設定）';
    if ($slugIn)   $slugIn.value = s;
  });

  // スラッグ入力欄に手で触れたら手動モードへ
  $slugIn?.addEventListener('input', () => { manual = true; });

  // 表示↔編集 切替
  $btnEdit?.addEventListener('click', () => {
    $view.style.display = 'none';
    $edit.style.display = 'block';
    $slugIn?.focus();
  });

  // OK: 正規化して確定
  $btnOk?.addEventListener('click', () => {
    const s = toSlug($slugIn.value);
    $slugIn.value = s;
    if ($slugText) $slugText.textContent = s || '（未設定）';
    manual = true;
    $edit.style.display = 'none';
    $view.style.display = 'flex';
  });

  // キャンセル: 表示に戻る（値は変えない）
  $btnCancel?.addEventListener('click', () => {
    $edit.style.display = 'none';
    $view.style.display = 'flex';
  });
})();
</script>
