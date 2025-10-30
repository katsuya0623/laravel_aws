{{-- resources/views/front/partials/cta-ambassador.blade.php --}}
<section class="amb-cta-wrap">
  <div class="amb-cta">
    <div class="amb-cta__inner">
      {{-- 左：コピー --}}
      <div class="amb-cta__left">
        <span class="amb-cta__pill">求人でお困りの企業様へ</span>
        <h2 class="amb-cta__ttl">ドウソコに求人を掲載しませんか！？</h2>
        <ul class="amb-cta__list">
          <li><span class="emoji">🏅</span><span>今なら無料でタイアップ記事を掲載！</span></li>
          <li><span class="emoji">🏅</span><span>管理画面から求職者の応募が一元管理！</span></li>
        </ul>
      </div>

      {{-- 右：人物・吹き出し・肩書・大ボタン --}}
      <div class="amb-cta__right">
        <div class="amb-cta__cap">
          <div class="cap-sub">青森系インフルエンサー</div>
          <div class="cap-name">クマガイオウスケ</div>
        </div>

        <div class="amb-cta__balloon">地方の求職者たちが<br>魅力的な求人を<br>待っています！</div>

        <img
          src="{{ asset('images/ambassador-man.png') }}"
          alt="青森系インフルエンサー クマガイオウスケ"
          class="amb-cta__man"
          loading="lazy"
        >

        <a href="{{ url('/contact') }}" class="amb-cta__btn">
          <span>掲載希望の企業の方はこちら</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      </div>
    </div>
  </div>
</section>

<style>
/* === フル幅（親 .container の影響を切る） === */
.amb-cta-wrap{
  position: relative;
  width: 100vw;
  left: 50%;
  margin-left: -50vw;
}

/* === ベース === */
.amb-cta{
  background:#C23A41;
  color:#fff;
  height:300px;           /* 指定通り固定高さ */
  overflow:hidden;        /* はみ出しカット（下雲などがあってもOK） */
  display:flex;
  align-items:center;
}

.amb-cta__inner{
  width:100%;
  max-width:1200px;       /* 盤面幅 */
  margin:0 auto;
  padding:0 20px;
  display:grid;
  grid-template-columns: 1fr 520px; /* 右に人物エリア */
  column-gap:24px;
}

/* === 左側 === */
.amb-cta__left{ align-self:center; }
.amb-cta__pill{
  display:inline-block;
  background:rgba(0,0,0,.18);
  border-radius:9999px;
  padding:6px 14px;
  font-size:13px; line-height:1;
}
.amb-cta__ttl{
  margin:14px 0 12px;
  font-weight:800; line-height:1.25;
  font-size:32px; letter-spacing:.01em;
}
.amb-cta__list{ margin:0; padding:0; list-style:none; }
.amb-cta__list li{
  display:flex; align-items:center; gap:8px;
  font-size:15px; margin-top:8px;
}
.amb-cta__list .emoji{ width:20px; text-align:center; }

/* === 右側（Flexで人物を自然配置） === */
.amb-cta__right{
  position:relative;      /* 吹き出し/ボタンの基準 */
  height:100%;
  display:flex;
  align-items:flex-end;   /* 下端合わせ */
  justify-content:flex-end; /* 右寄せ */
}

/* 人物：通常フロー。親の高さにフィットさせる */
.amb-cta__man{
  height:92%;             /* 300px内にスッと収める（見た目合わせ） */
  max-height:280px;       /* 多少の余白を確保 */
  width:auto;
  object-fit:contain;
  margin-right:24px;      /* 右端からの余白 */
  pointer-events:none; user-select:none;
  z-index:1;
}

/* 肩書（右上） */
.amb-cta__cap{
  position:absolute; top:6px; right:10px;
  text-align:right; z-index:3;
}
.amb-cta__cap .cap-sub{ color:#F5C542; font-weight:700; font-size:12px; }
.amb-cta__cap .cap-name{ color:#fff; font-weight:800; font-size:16px; margin-top:2px; }

/* 吹き出し（人物の左肩あたりに重ねる） */
.amb-cta__balloon{
  position:absolute;
  top:40px; right:240px;  /* 仕上がり位置はここで微調整 */
  background:#fff; color:#111;
  border-radius:14px;
  padding:12px 16px;
  font-size:13px; line-height:1.45;
  z-index:2;
  box-shadow:0 2px 0 rgba(0,0,0,.08);
}
.amb-cta__balloon::after{
  content:""; position:absolute;
  right:-12px; top:22px;
  border:8px solid transparent;
  border-left-color:#fff; /* 吹き出しのしっぽ */
}

/* 黄色の大CTA（人物の左腰あたり） */
.amb-cta__btn{
  position:absolute;
  right:190px;            /* 人物との重なり具合 */
  bottom:28px;
  display:inline-flex; align-items:center; gap:10px;
  background:#F5C542; color:#111;
  font-weight:800; border-radius:9999px;
  padding:14px 22px; text-decoration:none;
  z-index:3; box-shadow:0 4px 0 rgba(0,0,0,.12);
  transform:translateZ(0);
}
.amb-cta__btn svg{ flex:none; }

/* ====== 中画面（詰まり緩和） ====== */
@media (max-width:1100px){
  .amb-cta__inner{ grid-template-columns:1fr 480px; }
  .amb-cta__man{ margin-right:18px; max-height:260px; height:90%; }
  .amb-cta__balloon{ right:210px; }
  .amb-cta__btn{ right:170px; }
}

/* ====== タブレット以下：縦積み ====== */
@media (max-width:900px){
  .amb-cta{ height:auto; padding:36px 0; }
  .amb-cta__inner{ grid-template-columns:1fr; row-gap:18px; }
  .amb-cta__left{ text-align:center; }
  .amb-cta__ttl{ font-size:28px; }

  .amb-cta__right{
    height:auto; padding-top:8px;
    justify-content:center; align-items:center;
    flex-direction:column;
  }
  .amb-cta__cap{ position:static; text-align:center; margin-bottom:8px; }
  .amb-cta__balloon{ position:static; margin:0 auto 12px; }
  .amb-cta__man{ height:240px; max-height:none; margin:0 auto; }
  .amb-cta__btn{ position:static; margin:14px auto 0; }
}
</style>
