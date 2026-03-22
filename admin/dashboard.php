<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::require('/admin/');

$pageTitle = 'Dashboard';

// Stats
$restCount = DB::val('SELECT COUNT(*) FROM restaurants WHERE is_active=1');
$catCount  = DB::val('SELECT COUNT(*) FROM categories WHERE is_active=1');
$itemCount = DB::val('SELECT COUNT(*) FROM items WHERE is_active=1');
$userCount = DB::val('SELECT COUNT(*) FROM users WHERE is_active=1');
$mediaCount= DB::val('SELECT COUNT(*) FROM media');
$adCount   = DB::val('SELECT COUNT(*) FROM ads WHERE is_active=1');

$restaurants = DB::all('SELECT r.*,c.name AS city_name FROM restaurants r LEFT JOIN cities c ON c.id=r.city_id ORDER BY r.created_at DESC LIMIT 10');
$recentItems = DB::all('SELECT i.*,cat.name_en AS cat_name,r.name AS rest_name FROM items i JOIN categories cat ON cat.id=i.category_id JOIN restaurants r ON r.id=i.restaurant_id ORDER BY i.created_at DESC LIMIT 8');
$recentAudit = DB::all('SELECT l.*,u.name AS user_name FROM audit_log l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.created_at DESC LIMIT 10');

ob_start(); ?>

<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-icon">🏪</span>
    <span class="stat-label">Restaurants</span>
    <span class="stat-value"><?= $restCount ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">📋</span>
    <span class="stat-label">Categories</span>
    <span class="stat-value"><?= $catCount ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">🍽</span>
    <span class="stat-label">Menu Items</span>
    <span class="stat-value"><?= $itemCount ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">🖼</span>
    <span class="stat-label">Media Files</span>
    <span class="stat-value"><?= $mediaCount ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">📺</span>
    <span class="stat-label">Active Ads</span>
    <span class="stat-value"><?= $adCount ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-icon">👤</span>
    <span class="stat-label">Admin Users</span>
    <span class="stat-value"><?= $userCount ?></span>
  </div>
</div>

<div class="dash-grid">
  <!-- Restaurants -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Restaurants</span>
      <a href="/admin/restaurants.php?action=new" class="btn btn-primary btn-sm">+ Add</a>
    </div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Restaurant</th><th>Slug</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($restaurants as $r): ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:.5rem">
            <span class="color-dot" style="background:<?= e($r['theme_color']) ?>"></span>
            <?= e($r['name']) ?>
          </div>
        </td>
        <td><code>/<?= e($r['slug']) ?></code></td>
        <td><span class="badge <?= $r['is_active']?'badge-green':'badge-red' ?>"><?= $r['is_active']?'Active':'Inactive' ?></span></td>
        <td>
          <div class="action-btns">
            <a href="/<?= e($r['slug']) ?>/menu" target="_blank" class="btn btn-outline btn-sm">View</a>
            <a href="/admin/restaurants.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Recent Items -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Items</span>
      <a href="/admin/items.php?action=new" class="btn btn-primary btn-sm">+ Add</a>
    </div>
    <div class="table-wrap">
    <table>
      <thead><tr><th>Item</th><th>Restaurant</th><th>Price</th></tr></thead>
      <tbody>
      <?php foreach ($recentItems as $item): ?>
      <tr>
        <td>
          <?php if ($item['image']): ?>
          <img src="/<?= e($item['image']) ?>" class="thumb" alt="">
          <?php else: ?>
          <div class="thumb-placeholder">🍽</div>
          <?php endif; ?>
          <?= e($item['name_en']) ?>
        </td>
        <td><?= e($item['rest_name']) ?></td>
        <td><?= number_format($item['price']) ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<!-- Audit Log -->
<div class="card" style="margin-top:1.25rem">
  <div class="card-header"><span class="card-title">Recent Activity</span></div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>User</th><th>Action</th><th>Entity</th><th>Time</th></tr></thead>
    <tbody>
    <?php foreach ($recentAudit as $log): ?>
    <tr>
      <td><?= e($log['user_name'] ?? 'System') ?></td>
      <td><span class="badge badge-blue"><?= e($log['action']) ?></span></td>
      <td><?= e($log['entity'] ?? '') ?> <?= $log['entity_id'] ? '#'.$log['entity_id'] : '' ?></td>
      <td style="font-size:.75rem;color:var(--gray-500)"><?= e($log['created_at']) ?></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recentAudit): ?>
    <tr><td colspan="4" style="text-align:center;color:var(--gray-500);padding:1.5rem">No activity yet</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php $content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
