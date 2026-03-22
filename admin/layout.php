<?php
/**
 * Admin Layout — shared header/nav/footer wrapper
 */
$user     = Auth::user();
$userName = $user['name'] ?? 'Admin';
$userRole = $user['role'] ?? 'editor';
$curPage  = basename($_SERVER['PHP_SELF'], '.php');

$navLinks = [
  ['dashboard',   'Dashboard',    '📊'],
  ['restaurants', 'Restaurants',  '🏪'],
  ['menus',       'Menus',        '📋'],
  ['items',       'Items',        '🍽'],
  ['media',       'Media',        '🖼'],
  ['ads',         'Ads & Videos', '📺'],
  ['users',       'Users',        '👤'],
];
if ($userRole !== 'superadmin') {
  $navLinks = array_filter($navLinks, fn($l) => $l[0] !== 'users');
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#111827">
<title><?= e($pageTitle ?? 'Admin') ?> — Tirana Point Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- Mobile overlay (closes sidebar on tap outside) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="/assets/images/tirana-logo-wide.svg" alt="Tirana Point" class="sidebar-brand-logo">
  </div>

  <nav class="sidebar-nav" aria-label="Admin navigation">
    <?php foreach ($navLinks as [$href, $label, $icon]): ?>
    <a href="/admin/<?= $href ?>.php"
       class="nav-link <?= $curPage === $href ? 'active' : '' ?>"
       onclick="closeSidebar()">
      <span class="nav-icon" aria-hidden="true"><?= $icon ?></span>
      <span class="nav-label"><?= $label ?></span>
    </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-user">
    <div class="user-avatar" aria-hidden="true"><?= strtoupper($userName[0]) ?></div>
    <div class="user-info">
      <span class="user-name"><?= e($userName) ?></span>
      <span class="user-role"><?= ucfirst($userRole) ?></span>
    </div>
    <a href="/admin/logout.php" class="btn-logout" title="Logout" aria-label="Logout">↩</a>
  </div>
</aside>

<!-- Main area -->
<div class="admin-main" id="adminMain">
  <!-- Top bar -->
  <header class="admin-topbar">
    <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle menu" aria-expanded="false" id="sidebarToggle">
      ☰
    </button>
    <h1 class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
    <div class="topbar-right">
      <?php if ($flash = flash('success')): ?>
      <div class="flash flash-success" role="alert"><?= e($flash) ?></div>
      <?php endif; ?>
      <?php if ($flash = flash('error')): ?>
      <div class="flash flash-error" role="alert"><?= e($flash) ?></div>
      <?php endif; ?>
    </div>
  </header>

  <!-- Page content -->
  <main class="admin-content">
    <?= $content ?? '' ?>
  </main>
</div>

<script src="/assets/js/admin.js"></script>
<script>
function toggleSidebar() {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const btn      = document.getElementById('sidebarToggle');
  const open     = sidebar.classList.toggle('open');
  overlay.classList.toggle('open', open);
  btn.setAttribute('aria-expanded', open);
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
  document.getElementById('sidebarToggle')?.setAttribute('aria-expanded','false');
}
</script>
</body>
</html>
