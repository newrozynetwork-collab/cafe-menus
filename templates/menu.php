<?php
/**
 * Restaurant Menu Page
 * Variables: $restaurant, $sections, $categories, $items, $ads
 */
$theme      = $restaurant['theme_color'];
$bodyBg     = $restaurant['body_bg'];
$font       = $restaurant['font'];
$name       = $restaurant['name'];
$slug       = $restaurant['slug'];
$defaultLang= $restaurant['default_lang'];
$hasSections= (bool)$restaurant['has_sections'];
$logo       = $restaurant['logo'] ? asset_url($restaurant['logo']) : asset_url('assets/images/tirana-logo.svg');

// Build JS data structures
$catsBySection = [];
if ($hasSections) {
    foreach ($sections as $sec) {
        $catsBySection[$sec['id']] = array_values(array_filter($categories, fn($c) => $c['section_id'] == $sec['id']));
    }
} else {
    $catsBySection[0] = $categories;
}

$itemsByCat = [];
foreach ($items as $item) {
    $itemsByCat[$item['category_id']][] = $item;
}
?><!DOCTYPE html>
<html lang="<?= e($defaultLang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="<?= e($theme) ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<title><?= e($name) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap">
<!-- Swiper -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
<link rel="stylesheet" href="/assets/css/front.css">
<style>
  :root{
    --theme: <?= e($theme) ?>;
    --body-bg: <?= e($bodyBg) ?>;
    --font: '<?= e($font) ?>', 'Poppins', sans-serif;
  }
</style>
</head>
<body class="menu-body">

<!-- ── HEADER ─────────────────────────────────────────────── -->
<header class="site-header" id="site-header">

  <!-- Row 1: Logo + Name + Language -->
  <div class="header-row1">
    <div class="header-brand">
      <img src="<?= e($logo) ?>" alt="<?= e($name) ?>" class="header-logo" id="restaurantLogo">
      <h1 class="header-title font-rabar" id="restaurantName"><?= e($name) ?></h1>
    </div>
    <div class="header-actions">
      <!-- Language toggle -->
      <div class="lang-toggle" id="langToggle">
        <button class="lang-current" id="langBtn" onclick="toggleLangMenu()">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          <span id="langLabel">Language</span>
        </button>
        <ul class="lang-dropdown hidden" id="langDropdown">
          <li onclick="setLang('en')">English</li>
          <li onclick="setLang('ar')">العربية</li>
          <li onclick="setLang('ku')">کوردی</li>
        </ul>
      </div>
    </div>
  </div>

  <?php if ($hasSections && !empty($sections)): ?>
  <!-- Row 2: Section tabs (Food / Drinks / Hookah) -->
  <div class="header-row2" id="sectionTabs">
    <?php foreach ($sections as $i => $sec): ?>
    <button class="tab-btn <?= $i===0?'active':'' ?>"
            data-section="<?= e($sec['id']) ?>"
            onclick="switchSection(<?= $sec['id'] ?>)">
      <span class="tab-en"><?= e($sec['name_en']) ?></span>
      <span class="tab-ar" style="display:none"><?= e($sec['name_ar']) ?></span>
      <span class="tab-ku" style="display:none"><?= e($sec['name_ku']) ?></span>
    </button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Row 3: Category icon scroll (Swiper) -->
  <div class="header-row3">
    <div class="swiper cat-swiper" id="catSwiper">
      <div class="swiper-wrapper" id="catSwiperWrapper">
        <!-- Populated by JS -->
      </div>
    </div>
  </div>

</header>

<!-- ── MAIN CONTENT ───────────────────────────────────────── -->
<main class="menu-main" id="menuMain">
  <!-- Category sections rendered by JS -->
</main>

<!-- ── MODAL ──────────────────────────────────────────────── -->
<div class="modal-overlay hidden" id="modalOverlay" onclick="closeModal(event)">
  <div class="modal-dialog" id="modalDialog">
    <button class="modal-close" onclick="closeModal()">
      <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.5" fill="none"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>
    <div class="modal-img-wrap" id="modalImgWrap">
      <img id="modalImg" src="" alt="">
    </div>
    <div class="modal-info" id="modalInfo">
      <h2 class="modal-name" id="modalName"></h2>
      <p  class="modal-desc" id="modalDesc"></p>
      <div class="modal-variants" id="modalVariants"></div>
      <div class="modal-price-row">
        <span class="modal-price" id="modalPrice"></span>
      </div>
    </div>
  </div>
</div>

<!-- ── FOOTER ─────────────────────────────────────────────── -->
<footer class="site-footer">
  <img src="/assets/images/tirana-logo-white.svg" alt="Tirana Point" class="footer-logo">
</footer>

<!-- ── DATA ───────────────────────────────────────────────── -->
<script>
window.RESTAURANT = <?= json_encode([
  'id'          => $restaurant['id'],
  'name'        => $restaurant['name'],
  'slug'        => $slug,
  'theme'       => $theme,
  'bodyBg'      => $bodyBg,
  'font'        => $font,
  'defaultLang' => $defaultLang,
  'hasSections' => $hasSections,
  'logo'        => $logo,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

window.SECTIONS = <?= json_encode(array_values($sections), JSON_UNESCAPED_UNICODE) ?>;

window.CATEGORIES = <?= json_encode(array_map(function($c) {
  return [
    'id'        => $c['id'],
    'section_id'=> $c['section_id'],
    'name_en'   => $c['name_en'],
    'name_ar'   => $c['name_ar'] ?? $c['name_en'],
    'name_ku'   => $c['name_ku'] ?? $c['name_en'],
    'icon'      => $c['icon'] ? asset_url($c['icon']) : '',
  ];
}, $categories), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

window.ITEMS = <?= json_encode(array_map(function($item) {
  return [
    'id'          => $item['id'],
    'category_id' => $item['category_id'],
    'name_en'     => $item['name_en'],
    'name_ar'     => $item['name_ar'] ?? $item['name_en'],
    'name_ku'     => $item['name_ku'] ?? $item['name_en'],
    'desc_en'     => $item['description_en'] ?? '',
    'desc_ar'     => $item['description_ar'] ?? '',
    'desc_ku'     => $item['description_ku'] ?? '',
    'price'       => $item['price'],
    'image'       => $item['image'] ? asset_url($item['image']) : '',
  ];
}, $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="/assets/js/menu.js"></script>
</body>
</html>
