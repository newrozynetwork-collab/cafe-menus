<?php
/**
 * Restaurant Splash / Language-Select Page
 * Layout matches morinamenu.com reference design
 */
$theme   = $restaurant['theme_color'];
$bodyBg  = $restaurant['body_bg'];
$font    = $restaurant['font'];
$name    = $restaurant['name'];
$slug    = $restaurant['slug'];
$hasVideo= (bool)$restaurant['has_splash_video'];
$videoUrl= $restaurant['splash_video_url']  ? asset_url($restaurant['splash_video_url'])  : '';
$vidThumb= $restaurant['splash_video_thumb'] ? asset_url($restaurant['splash_video_thumb']) : '';
$restLogo= $restaurant['logo'] ? asset_url($restaurant['logo']) : '';
$brandLogo = asset_url('assets/images/tirana-logo-white.svg');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="<?= e($theme) ?>">
<title><?= e($name) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
<style>
  :root{--theme:<?= e($theme) ?>;--body-bg:<?= e($bodyBg) ?>;}
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

  html,body{height:100%;}
  html{height:-webkit-fill-available;}

  body{
    font-family:'<?= e($font) ?>','Poppins',sans-serif;
    background:var(--theme);
    color:#fff;
    width:100svw;
    min-height:100svh;
    min-height:-webkit-fill-available;
    overflow:hidden;
  }

  /* ── Background video ── */
  .bg-video{
    position:fixed;inset:0;width:100%;height:100%;
    object-fit:cover;z-index:-1;
    opacity:1;pointer-events:none;
  }

  /* ── Red overlay — heavy tint so video shows subtly ── */
  .overlay{
    position:fixed;inset:0;
    background:var(--theme);
    opacity:.55;
    z-index:0;pointer-events:none;
  }

  /* ── Main layout ── */
  main{
    position:relative;z-index:1;
    height:100svh;height:100vh;
    min-height:-webkit-fill-available;
    width:100%;
    display:flex;flex-direction:column;
    justify-content:space-between;
    align-items:center;
    overflow-x:hidden;
  }

  /* ── Feedback button — plain white link ── */
  .btn-feedback{
    position:absolute;
    top:max(1rem,env(safe-area-inset-top,1rem));
    left:max(1rem,env(safe-area-inset-left,1rem));
    z-index:20;
    display:flex;align-items:center;gap:.4rem;
    color:#fff;text-decoration:none;
    font-size:.9rem;font-weight:400;
    -webkit-tap-highlight-color:transparent;
    touch-action:manipulation;
  }
  .btn-feedback svg{
    width:20px;height:20px;
    flex-shrink:0;
  }
  .btn-feedback:hover{opacity:.85;}

  /* ── Middle section: logo + buttons ── */
  .splash-middle{
    margin-top:clamp(48px,10vh,72px);
    width:100%;
    display:flex;flex-direction:column;
    align-items:center;
    gap:0;
  }

  /* ── Restaurant logo — bare, no box ── */
  .rest-logo{
    display:block;
    width:clamp(160px,40vw,320px);
    height:clamp(90px,22vw,176px);
    object-fit:contain;
    pointer-events:none;
  }
  .rest-logo-placeholder{
    font-size:clamp(3rem,12vw,6rem);
    line-height:1;
  }

  /* ── Language buttons ── */
  .lang-wrapper{
    width:75%;max-width:560px;
    margin-top:clamp(32px,7vh,56px);
  }
  .lang-grid{
    display:flex;flex-wrap:wrap;
    gap:12px;
    width:100%;justify-content:center;
    direction:ltr;
  }
  .lang-btn{
    background:var(--theme);    /* blends with background — text floats */
    color:#fff;
    border:none;
    border-radius:200px;
    cursor:pointer;
    font-size:clamp(.9rem,2.5vw,1rem);
    font-family:inherit;
    font-weight:400;
    flex-basis:100%;
    height:36px;
    padding:6px 12px;
    text-align:center;
    transition:background .15s;
    -webkit-tap-highlight-color:transparent;
    touch-action:manipulation;
  }
  .lang-btn.half{
    flex:1;
    min-width:calc(50% - 6px);
    height:32px;
    padding:4px 12px;
  }
  .lang-btn:hover,.lang-btn:focus-visible{
    background:rgba(255,255,255,.09);
    outline:none;
  }
  .lang-btn:active{transform:scale(.98);}

  /* ── Bottom section: social + footer ── */
  .splash-bottom{
    width:100%;
    display:flex;flex-direction:column;
    align-items:center;
  }

  /* ── Social icons ── */
  .social-bar{
    display:flex;flex-direction:row;
    direction:ltr;
    gap:16px;
    align-items:center;
    flex-wrap:wrap;
    justify-content:center;
  }
  .social-icon-wrap{
    display:block;
    margin:0 2px;
  }
  .social-btn{
    display:flex;align-items:center;justify-content:center;
    width:36px;height:36px;
    border-radius:50%;
    border:1.5px solid rgba(255,255,255,.5);
    color:#fff;text-decoration:none;
    transition:background .15s,border-color .15s;
    -webkit-tap-highlight-color:transparent;
    touch-action:manipulation;
  }
  .social-btn:hover,.social-btn:active{
    background:rgba(255,255,255,.12);
    border-color:rgba(255,255,255,.9);
  }
  .social-btn svg{width:18px;height:18px;flex-shrink:0;}

  /* ── Brand footer ── */
  .splash-footer{
    width:100%;
    display:flex;align-items:center;justify-content:center;
    padding:12px 0;
    padding-bottom:calc(12px + env(safe-area-inset-bottom,0px));
  }
  .brand-logo{
    height:clamp(18px,4vw,28px);
    width:auto;object-fit:contain;
    opacity:.85;
    filter:brightness(10);
  }

  /* ── Responsive: small height ── */
  @media (max-height:600px){
    .splash-middle{margin-top:32px;}
    .rest-logo{height:clamp(70px,18vw,120px);}
    .lang-wrapper{margin-top:24px;}
    .lang-btn{height:30px;font-size:.85rem;}
    .lang-btn.half{height:28px;}
    .social-btn{width:32px;height:32px;}
    .social-btn svg{width:15px;height:15px;}
  }

  /* ── Responsive: small width ── */
  @media (max-width:400px){
    .lang-wrapper{width:88%;}
  }
</style>
</head>
<body>

<?php if ($hasVideo && $videoUrl): ?>
<video class="bg-video" autoplay loop muted playsinline<?= $vidThumb ? ' poster="'.e($vidThumb).'"' : '' ?>>
  <source src="<?= e($videoUrl) ?>" type="video/mp4">
</video>
<div class="overlay" aria-hidden="true"></div>
<?php endif; ?>

<!-- Feedback -->
<a class="btn-feedback" href="/<?= e($slug) ?>/feedback" aria-label="Feedback">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
       stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="M12 20h9"/>
    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
  </svg>
  Feedback
</a>

<main role="main">

  <!-- Middle: logo + language buttons -->
  <div class="splash-middle">

    <?php if ($restLogo): ?>
      <img src="<?= e($restLogo) ?>" alt="<?= e($name) ?>" class="rest-logo" draggable="false">
    <?php else: ?>
      <span class="rest-logo-placeholder" aria-hidden="true">🏪</span>
    <?php endif; ?>

    <div class="lang-wrapper">
      <div class="lang-grid" role="group" aria-label="Select language">
        <button class="lang-btn" onclick="selectLang('ku')" lang="ku" dir="rtl">کوردی</button>
        <button class="lang-btn half" onclick="selectLang('ar')" lang="ar" dir="rtl">العربية</button>
        <button class="lang-btn half" onclick="selectLang('en')" lang="en" dir="ltr">English</button>
      </div>
    </div>

  </div>

  <!-- Bottom: social + brand footer -->
  <div class="splash-bottom">

    <?php if ($restaurant['social_facebook'] || $restaurant['social_instagram'] || $restaurant['social_location'] || $restaurant['social_phone']): ?>
    <nav class="social-bar" aria-label="Social links">

      <?php if ($restaurant['social_facebook']): ?>
      <div class="social-icon-wrap">
        <a class="social-btn" href="<?= e($restaurant['social_facebook']) ?>"
           target="_blank" rel="noopener" aria-label="Facebook">
          <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
          </svg>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($restaurant['social_instagram']): ?>
      <div class="social-icon-wrap">
        <a class="social-btn" href="<?= e($restaurant['social_instagram']) ?>"
           target="_blank" rel="noopener" aria-label="Instagram">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"/>
            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>
          </svg>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($restaurant['social_location']): ?>
      <div class="social-icon-wrap">
        <a class="social-btn" href="<?= e($restaurant['social_location']) ?>"
           target="_blank" rel="noopener" aria-label="Location">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
            <circle cx="12" cy="10" r="3"/>
          </svg>
        </a>
      </div>
      <?php endif; ?>

      <?php if ($restaurant['social_phone']): ?>
      <div class="social-icon-wrap">
        <a class="social-btn" href="tel:<?= e($restaurant['social_phone']) ?>" aria-label="Phone">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07
                     A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67
                     A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72
                     c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11
                     L7.91 9.91a16 16 0 0 0 6.09 6.09l1.27-1.27
                     a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7
                     A2 2 0 0 1 22 16.92z"/>
          </svg>
        </a>
      </div>
      <?php endif; ?>

    </nav>
    <?php endif; ?>

    <footer class="splash-footer">
      <img src="<?= e($brandLogo) ?>" alt="Tirana Point" class="brand-logo" draggable="false">
    </footer>

  </div>

</main>

<script>
function selectLang(lang) {
  try { localStorage.setItem('<?= e($slug) ?>_lang', lang); } catch(e) {}
  window.location.href = '/<?= e($slug) ?>/menu';
}
</script>
</body>
</html>
