<?php
/**
 * Restaurant Splash / Language-Select Page
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
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
<style>
  :root{--theme:<?= e($theme) ?>;--body-bg:<?= e($bodyBg) ?>;}
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

  body{
    font-family:'<?= e($font) ?>','Poppins',sans-serif;
    background:var(--theme);
    min-height:100svh;min-height:-webkit-fill-available;
    display:flex;flex-direction:column;
    align-items:center;justify-content:center;
    position:relative;overflow:hidden;
  }
  html{height:-webkit-fill-available;}

  /* ── Background video ── */
  .bg-video{
    position:fixed;inset:0;width:100%;height:100%;
    object-fit:cover;z-index:0;opacity:.35;pointer-events:none;
  }
  .overlay{position:fixed;inset:0;background:var(--theme);opacity:.7;z-index:1;pointer-events:none;}

  /* ── Feedback button ── */
  .btn-feedback{
    position:fixed;top:max(.75rem,env(safe-area-inset-top,.75rem));
    left:max(.75rem,env(safe-area-inset-left,.75rem));
    z-index:20;
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3);
    color:#fff;border-radius:6px;
    padding:.35rem .75rem;font-size:.8rem;
    display:flex;align-items:center;gap:.4rem;
    cursor:pointer;text-decoration:none;
    backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
    touch-action:manipulation;
  }
  .btn-feedback svg{width:14px;height:14px;fill:currentColor;flex-shrink:0;}

  /* ── Main content ── */
  .splash-content{
    position:relative;z-index:10;
    width:100%;max-width:400px;
    padding:clamp(1.25rem,5vw,2rem) clamp(1rem,5vw,1.5rem);
    padding-bottom:calc(clamp(1.25rem,5vw,2rem) + env(safe-area-inset-bottom,0px));
    display:flex;flex-direction:column;
    align-items:center;gap:clamp(.9rem,3vw,1.25rem);
  }

  /* ── Restaurant logo ── */
  .rest-logo-wrap{
    width:clamp(70px,20vw,96px);
    height:clamp(70px,20vw,96px);
    border-radius:16px;
    overflow:hidden;
    box-shadow:0 6px 24px rgba(0,0,0,.35);
    background:rgba(255,255,255,.1);
    display:flex;align-items:center;justify-content:center;
    flex-shrink:0;
  }
  .rest-logo-wrap img{width:100%;height:100%;object-fit:cover;}
  .rest-logo-placeholder{font-size:2.5rem;}

  .splash-name{
    color:#fff;
    font-size:clamp(1rem,4vw,1.3rem);
    font-weight:600;
    text-align:center;
    line-height:1.3;
    max-width:280px;
  }

  /* ── Language buttons ── */
  .lang-group{width:100%;display:flex;flex-direction:column;gap:.55rem;}
  .lang-btn{
    width:100%;
    background:rgba(255,255,255,.1);
    color:#fff;
    border:1.5px solid rgba(255,255,255,.4);
    border-radius:10px;
    padding:clamp(.75rem,3vw,.9rem) 1rem;
    font-size:clamp(.95rem,3vw,1.1rem);
    font-weight:500;cursor:pointer;
    transition:background .15s,border-color .15s,transform .12s;
    font-family:inherit;
    -webkit-tap-highlight-color:transparent;
    touch-action:manipulation;
  }
  .lang-btn:hover,.lang-btn:focus{
    background:rgba(255,255,255,.2);
    border-color:rgba(255,255,255,.8);
    outline:none;
  }
  .lang-btn:active{transform:scale(.98);}
  .lang-row{display:grid;grid-template-columns:1fr 1fr;gap:.55rem;}

  /* ── Social icons ── */
  .social-bar{display:flex;gap:.65rem;flex-wrap:wrap;justify-content:center;}
  .social-btn{
    width:44px;height:44px;border-radius:50%;
    border:1.5px solid rgba(255,255,255,.45);
    display:flex;align-items:center;justify-content:center;
    color:#fff;text-decoration:none;
    transition:background .15s;
    -webkit-tap-highlight-color:transparent;
    touch-action:manipulation;
  }
  .social-btn:hover,.social-btn:active{background:rgba(255,255,255,.2);border-color:#fff;}
  .social-btn svg{width:18px;height:18px;fill:currentColor;}

  /* ── Brand footer ── */
  .splash-footer{
    position:fixed;
    bottom:0;left:0;right:0;
    z-index:10;
    display:flex;align-items:center;justify-content:center;
    padding:clamp(.45rem,2vw,.65rem);
    padding-bottom:calc(clamp(.45rem,2vw,.65rem) + env(safe-area-inset-bottom,0px));
    background:rgba(0,0,0,.25);
    backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
  }
  .brand-logo{
    height:clamp(18px,4vw,26px);
    width:auto;
    object-fit:contain;
    opacity:.85;
    filter:brightness(10);  /* make SVG white */
  }

  /* ── Responsive: very small screens ── */
  @media (max-height: 580px) {
    .splash-content{gap:.7rem;}
    .rest-logo-wrap{width:60px;height:60px;}
    .splash-name{font-size:.95rem;}
    .lang-btn{padding:.6rem .9rem;font-size:.9rem;}
    .social-bar{gap:.45rem;}
    .social-btn{width:38px;height:38px;}
  }
</style>
</head>
<body>

<?php if ($hasVideo && $videoUrl): ?>
<video class="bg-video" autoplay loop muted playsinline<?= $vidThumb ? ' poster="'.e($vidThumb).'"' : '' ?>>
  <source src="<?= e($videoUrl) ?>" type="video/mp4">
</video>
<div class="overlay"></div>
<?php endif; ?>

<!-- Feedback -->
<a class="btn-feedback" href="#feedback" aria-label="Feedback">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
  Feedback
</a>

<main class="splash-content" role="main">

  <!-- Restaurant logo -->
  <div class="rest-logo-wrap">
    <?php if ($restLogo): ?>
      <img src="<?= e($restLogo) ?>" alt="<?= e($name) ?>">
    <?php else: ?>
      <span class="rest-logo-placeholder" aria-hidden="true">🏪</span>
    <?php endif; ?>
  </div>

  <p class="splash-name"><?= e($name) ?></p>

  <!-- Language selection -->
  <div class="lang-group" role="group" aria-label="Select language">
    <button class="lang-btn" onclick="selectLang('ku')" lang="ku" dir="rtl">کوردی</button>
    <div class="lang-row">
      <button class="lang-btn" onclick="selectLang('ar')" lang="ar" dir="rtl">العربية</button>
      <button class="lang-btn" onclick="selectLang('en')" lang="en">English</button>
    </div>
  </div>

  <!-- Social links -->
  <?php if ($restaurant['social_facebook'] || $restaurant['social_instagram'] || $restaurant['social_location'] || $restaurant['social_phone']): ?>
  <nav class="social-bar" aria-label="Social links">
    <?php if ($restaurant['social_facebook']): ?>
    <a class="social-btn" href="<?= e($restaurant['social_facebook']) ?>" target="_blank" rel="noopener" aria-label="Facebook">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($restaurant['social_instagram']): ?>
    <a class="social-btn" href="<?= e($restaurant['social_instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram">
      <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".5" fill="currentColor"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($restaurant['social_location']): ?>
    <a class="social-btn" href="<?= e($restaurant['social_location']) ?>" target="_blank" rel="noopener" aria-label="Location">
      <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
    </a>
    <?php endif; ?>
    <?php if ($restaurant['social_phone']): ?>
    <a class="social-btn" href="tel:<?= e($restaurant['social_phone']) ?>" aria-label="Phone">
      <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6.09 6.09l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
    </a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>

</main>

<!-- Brand footer with Tirana Point logo -->
<footer class="splash-footer">
  <img src="<?= e($brandLogo) ?>" alt="Tirana Point" class="brand-logo">
</footer>

<script>
function selectLang(lang) {
  try { localStorage.setItem('<?= e($slug) ?>_lang', lang); } catch(e) {}
  window.location.href = '/<?= e($slug) ?>/menu';
}
</script>
</body>
</html>
