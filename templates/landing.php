<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tirana Menu — Digital Restaurant Menus</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Poppins',sans-serif;background:#1d1d1d;color:#fff;min-height:100vh}
nav{background:#1d1d1d;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid rgba(255,255,255,.08)}
.nav-logo{font-size:1.2rem;font-weight:700;color:#6668ab}
.nav-links a{color:rgba(255,255,255,.7);text-decoration:none;margin-left:1.5rem;font-size:.9rem}
.nav-links a:hover{color:#fff}
.hero{text-align:center;padding:6rem 1rem 4rem}
.hero h1{font-size:clamp(2rem,5vw,3.5rem);font-weight:700;margin-bottom:1rem;line-height:1.2}
.hero h1 span{color:#6668ab}
.hero p{color:rgba(255,255,255,.65);font-size:1.05rem;max-width:540px;margin:0 auto 2.5rem;line-height:1.7}
.btn-cta{display:inline-block;background:#6668ab;color:#fff;padding:.85rem 2.25rem;border-radius:8px;text-decoration:none;font-weight:600;font-size:1rem;transition:background .15s}
.btn-cta:hover{background:#5557a0}
.restaurants{max-width:1100px;margin:0 auto;padding:3rem 1rem}
.restaurants h2{text-align:center;font-size:1.5rem;margin-bottom:2rem;color:rgba(255,255,255,.9)}
.rest-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem}
.rest-card{background:#2a2a2a;border-radius:12px;overflow:hidden;text-decoration:none;color:#fff;transition:transform .15s,box-shadow .15s;border:1px solid rgba(255,255,255,.06)}
.rest-card:hover{transform:translateY(-3px);box-shadow:0 8px 32px rgba(0,0,0,.4)}
.rest-card-header{height:100px;display:flex;align-items:center;justify-content:center;font-size:2rem}
.rest-card-body{padding:1rem}
.rest-card-body h3{font-size:1rem;font-weight:600;margin-bottom:.25rem}
.rest-card-body p{font-size:.8rem;color:rgba(255,255,255,.5)}
.rest-card-body .slug{display:inline-block;margin-top:.5rem;font-size:.75rem;color:#6668ab;font-weight:500}
footer{text-align:center;padding:2rem;color:rgba(255,255,255,.3);font-size:.8rem;border-top:1px solid rgba(255,255,255,.06)}
</style>
</head>
<body>
<?php
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/helpers.php';
$restaurants = DB::all("SELECT r.*,c.name AS city FROM restaurants r LEFT JOIN cities c ON c.id=r.city_id WHERE r.is_active=1 ORDER BY r.name");
?>
<nav>
  <img src="/assets/images/tirana-logo-wide.svg" alt="Tirana Point" class="nav-logo" style="height:36px;width:auto;object-fit:contain;">
  <div class="nav-links">
    <a href="/api/restaurants">API</a>
    <a href="/admin/">Admin ↗</a>
  </div>
</nav>

<div class="hero">
  <h1>Digital Menus for<br><span>Every Restaurant</span></h1>
  <p>Beautiful, multilingual, mobile-first digital menus. Manage everything from one admin panel.</p>
  <a href="/admin/" class="btn-cta">Open Admin Panel →</a>
</div>

<div class="restaurants">
  <h2>Active Restaurants</h2>
  <div class="rest-grid">
    <?php foreach ($restaurants as $r): ?>
    <a class="rest-card" href="/<?= e($r['slug']) ?>">
      <div class="rest-card-header" style="background:<?= e($r['theme_color']) ?>">
        <?php if ($r['logo']): ?>
        <img src="/<?= e($r['logo']) ?>" style="height:60px;border-radius:8px;object-fit:contain" alt="">
        <?php else: ?>🏪<?php endif; ?>
      </div>
      <div class="rest-card-body">
        <h3><?= e($r['name']) ?></h3>
        <p><?= e($r['city'] ?? 'Restaurant') ?></p>
        <span class="slug">/<?= e($r['slug']) ?></span>
      </div>
    </a>
    <?php endforeach; ?>
    <?php if (!$restaurants): ?>
    <p style="color:rgba(255,255,255,.4);grid-column:1/-1;text-align:center">No restaurants yet. <a href="/admin/restaurants.php?action=new" style="color:#6668ab">Add one ↗</a></p>
    <?php endif; ?>
  </div>
</div>

<footer>
  <img src="/assets/images/tirana-logo-wide.svg" alt="Tirana Point" style="height:28px;width:auto;opacity:.5;display:block;margin:0 auto .5rem">
  Built with PHP + SQLite
</footer>
</body>
</html>
