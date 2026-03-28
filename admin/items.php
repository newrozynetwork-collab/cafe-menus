<?php
defined('ROOT') || define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::require('/admin/');

$pageTitle   = 'Menu Items';
$restaurants = DB::all('SELECT id,name FROM restaurants WHERE is_active=1 ORDER BY name');
$selRestId   = (int)($_GET['restaurant_id'] ?? ($restaurants[0]['id'] ?? 0));
$selCatId    = (int)($_GET['category_id'] ?? 0);
$search      = trim($_GET['q'] ?? '');
$page        = max(1,(int)($_GET['page']??1));
$perPage     = 20;

// ── POST handlers ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act  = $_POST['_action'] ?? '';
    $rid  = (int)($_POST['restaurant_id'] ?? 0);
    $iid  = (int)($_POST['item_id'] ?? 0);

    if ($act === 'item_save') {
        $data = [
            'restaurant_id'  => $rid,
            'category_id'    => (int)$_POST['category_id'],
            'name_en'        => trim($_POST['name_en'] ?? ''),
            'name_ar'        => trim($_POST['name_ar'] ?? ''),
            'name_ku'        => trim($_POST['name_ku'] ?? ''),
            'description_en' => trim($_POST['description_en'] ?? ''),
            'description_ar' => trim($_POST['description_ar'] ?? ''),
            'description_ku' => trim($_POST['description_ku'] ?? ''),
            'price'          => (float)str_replace(',','', $_POST['price'] ?? 0),
            'sort_order'     => (int)($_POST['sort_order'] ?? 0),
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ];
        // Image upload
        if (!empty($_FILES['image']['name'])) {
            $up = handle_upload($_FILES['image'], $rid, 'image');
            if ($up) $data['image'] = $up['path'];
        } elseif (!empty($_POST['existing_image'])) {
            $data['image'] = $_POST['existing_image'];
        }

        if ($iid) {
            DB::update('items', $data, 'id=?', [$iid]);
            flash('success','Item updated.');
        } else {
            DB::insert('items', $data);
            flash('success','Item created.');
        }

        // Variants
        DB::delete('variants','item_id=?',[$iid ?: (int)DB::val('SELECT MAX(id) FROM items')]);
        $vnames = $_POST['var_name'] ?? [];
        $vprices= $_POST['var_price'] ?? [];
        foreach ($vnames as $vi => $vn) {
            if (trim($vn)) {
                DB::insert('variants',[
                    'item_id'   => $iid ?: (int)DB::val('SELECT MAX(id) FROM items'),
                    'name_en'   => trim($vn),
                    'price'     => (float)($vprices[$vi] ?? 0),
                    'sort_order'=> $vi,
                ]);
            }
        }
    }

    if ($act === 'item_delete' && $iid) {
        DB::delete('items','id=?',[$iid]);
        flash('success','Item deleted.');
    }

    if ($act === 'item_toggle' && $iid) {
        $cur = DB::val('SELECT is_active FROM items WHERE id=?',[$iid]);
        DB::run('UPDATE items SET is_active=? WHERE id=?',[!$cur,$iid]);
    }

    header("Location: /admin/items.php?restaurant_id=$rid&category_id=$selCatId");
    exit;
}

// ── Load data ──────────────────────────────────────────────────
$categories = $selRestId ? DB::all('SELECT * FROM categories WHERE restaurant_id=? ORDER BY sort_order',[$selRestId]) : [];

$whereClause = '1=1';
$params = [];
if ($selRestId) { $whereClause .= ' AND i.restaurant_id=?'; $params[] = $selRestId; }
if ($selCatId)  { $whereClause .= ' AND i.category_id=?';    $params[] = $selCatId; }
if ($search)    { $whereClause .= ' AND (i.name_en LIKE ? OR i.name_ar LIKE ? OR i.name_ku LIKE ?)'; $params = array_merge($params,["%$search%","%$search%","%$search%"]); }

$pag   = paginate("items i", $whereClause, $params, $page, $perPage);
$items = DB::all("SELECT i.*,c.name_en AS cat_name FROM items i LEFT JOIN categories c ON c.id=i.category_id WHERE $whereClause ORDER BY i.category_id,i.sort_order LIMIT $perPage OFFSET {$pag['offset']}", $params);

ob_start(); ?>

<!-- Filters -->
<div class="filter-bar">
  <form method="GET" style="display:contents">
    <select name="restaurant_id" onchange="this.form.submit()">
      <?php foreach ($restaurants as $r): ?>
      <option value="<?= $r['id'] ?>" <?= $r['id']==$selRestId?'selected':'' ?>><?= e($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="category_id" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
      <option value="<?= $c['id'] ?>" <?= $c['id']==$selCatId?'selected':'' ?>><?= e($c['name_en']) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search items…">
    <button class="btn btn-outline" type="submit">Search</button>
    <button class="btn btn-primary" type="button" onclick="openModal('item-modal');resetItemForm()">+ Add Item</button>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Items (<?= $pag['total'] ?>)</span>
  </div>
  <?php if ($items): ?>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price (IQD)</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $item): ?>
    <tr>
      <td>
        <?php if ($item['image']): ?>
        <img src="/<?= e($item['image']) ?>" class="thumb" alt="">
        <?php else: ?><div class="thumb-placeholder">🍽</div><?php endif; ?>
      </td>
      <td>
        <strong><?= e($item['name_en']) ?></strong>
        <?php if ($item['name_ar']): ?><br><small dir="rtl" style="color:var(--gray-500)"><?= e($item['name_ar']) ?></small><?php endif; ?>
      </td>
      <td><?= e($item['cat_name'] ?? '—') ?></td>
      <td><?= number_format($item['price']) ?></td>
      <td><span class="badge <?= $item['is_active']?'badge-green':'badge-red' ?>"><?= $item['is_active']?'Active':'Hidden' ?></span></td>
      <td>
        <div class="action-btns">
          <button class="btn btn-outline btn-sm" onclick='editItem(<?= json_encode($item,JSON_UNESCAPED_UNICODE) ?>)'>✏️</button>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete item?')">
            <input type="hidden" name="_action" value="item_delete">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
            <button class="btn btn-danger btn-sm">🗑</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <!-- Pagination -->
  <?php if ($pag['pages'] > 1): ?>
  <div class="pagination">
    <?php for ($p=1;$p<=$pag['pages'];$p++): ?>
    <a href="?restaurant_id=<?=$selRestId?>&category_id=<?=$selCatId?>&q=<?=urlencode($search)?>&page=<?=$p?>" class="page-btn <?=$p==$page?'active':''?>"><?=$p?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php else: ?>
  <div class="empty-state"><span class="empty-icon">🍽</span><p>No items found.</p></div>
  <?php endif; ?>
</div>

<!-- Item Modal -->
<div class="modal-backdrop" id="item-modal">
  <div class="modal-box" style="max-width:640px">
    <div class="modal-header">
      <h3 id="item-modal-title">Add Menu Item</h3>
      <button onclick="closeModal('item-modal')" style="background:none;border:none;cursor:pointer;font-size:1.3rem">×</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="_action" value="item_save">
        <input type="hidden" name="restaurant_id" value="<?= $selRestId ?>">
        <input type="hidden" name="item_id" id="item-id-input" value="">
        <input type="hidden" name="existing_image" id="item-existing-img" value="">
        <?= csrf_field() ?>

        <!-- Tabs inside modal -->
        <div class="tab-nav">
          <button type="button" class="tab-nav-btn active" onclick="showTab('itab-basic',this)">Basic</button>
          <button type="button" class="tab-nav-btn" onclick="showTab('itab-desc',this)">Descriptions</button>
          <button type="button" class="tab-nav-btn" onclick="showTab('itab-variants',this)">Variants</button>
        </div>

        <div class="tab-pane active" id="itab-basic">
          <div class="form-grid">
            <div class="form-group">
              <label>Name (English) *</label>
              <input type="text" name="name_en" id="item-name-en" required>
            </div>
            <div class="form-group">
              <label>Name (Arabic)</label>
              <input type="text" name="name_ar" id="item-name-ar" dir="rtl">
            </div>
            <div class="form-group">
              <label>Name (Kurdish)</label>
              <input type="text" name="name_ku" id="item-name-ku" dir="rtl">
            </div>
            <div class="form-group">
              <label>Category *</label>
              <select name="category_id" id="item-cat" required>
                <?php foreach ($categories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['name_en']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Price (IQD) *</label>
              <input type="number" name="price" id="item-price" min="0" step="500" required>
            </div>
            <div class="form-group">
              <label>Sort Order</label>
              <input type="number" name="sort_order" id="item-sort" value="0" min="0">
            </div>
            <div class="form-group">
              <label>Image</label>
              <input type="file" name="image" id="item-image-file" accept="image/*">
              <div class="upload-hint">
                📐 Recommended: <strong>800 × 600 px</strong> (4:3 ratio) &nbsp;·&nbsp;
                📁 Max size: <strong>2 MB</strong> &nbsp;·&nbsp;
                🖼 Format: <strong>JPG or WebP</strong> for best quality/size
              </div>
              <div id="item-img-preview" class="img-preview-wrap"></div>
            </div>
            <div class="form-group">
              <label style="display:flex;gap:.5rem;align-items:center">
                <input type="checkbox" name="is_active" id="item-active" value="1" checked> Active
              </label>
            </div>
          </div>
        </div>

        <div class="tab-pane" id="itab-desc">
          <div class="form-group" style="margin-bottom:.75rem">
            <label>Description (English)</label>
            <textarea name="description_en" id="item-desc-en" rows="3"></textarea>
          </div>
          <div class="form-group" style="margin-bottom:.75rem">
            <label>Description (Arabic)</label>
            <textarea name="description_ar" id="item-desc-ar" rows="3" dir="rtl"></textarea>
          </div>
          <div class="form-group">
            <label>Description (Kurdish)</label>
            <textarea name="description_ku" id="item-desc-ku" rows="3" dir="rtl"></textarea>
          </div>
        </div>

        <div class="tab-pane" id="itab-variants">
          <p style="font-size:.8rem;color:var(--gray-500);margin-bottom:.75rem">Add size/variant options (e.g. Small, Large). Leave empty if not needed.</p>
          <div id="variants-list"></div>
          <button type="button" class="btn btn-outline btn-sm" onclick="addVariant()">+ Add Variant</button>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('item-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Item</button>
      </div>
    </form>
  </div>
</div>

<script>
function resetItemForm() {
  document.getElementById('item-modal-title').textContent = 'Add Menu Item';
  document.getElementById('item-id-input').value   = '';
  document.getElementById('item-name-en').value    = '';
  document.getElementById('item-name-ar').value    = '';
  document.getElementById('item-name-ku').value    = '';
  document.getElementById('item-price').value      = '';
  document.getElementById('item-sort').value       = '0';
  document.getElementById('item-desc-en').value    = '';
  document.getElementById('item-desc-ar').value    = '';
  document.getElementById('item-desc-ku').value    = '';
  document.getElementById('item-active').checked   = true;
  document.getElementById('item-img-preview').innerHTML = '';
  document.getElementById('item-existing-img').value = '';
  document.getElementById('variants-list').innerHTML = '';
}
function editItem(item) {
  resetItemForm();
  document.getElementById('item-modal-title').textContent = 'Edit Item';
  document.getElementById('item-id-input').value   = item.id;
  document.getElementById('item-name-en').value    = item.name_en || '';
  document.getElementById('item-name-ar').value    = item.name_ar || '';
  document.getElementById('item-name-ku').value    = item.name_ku || '';
  document.getElementById('item-price').value      = item.price || 0;
  document.getElementById('item-sort').value       = item.sort_order || 0;
  document.getElementById('item-desc-en').value    = item.description_en || '';
  document.getElementById('item-desc-ar').value    = item.description_ar || '';
  document.getElementById('item-desc-ku').value    = item.description_ku || '';
  document.getElementById('item-active').checked   = !!item.is_active;
  document.getElementById('item-existing-img').value = item.image || '';
  const catEl = document.getElementById('item-cat');
  if (catEl) catEl.value = item.category_id;
  if (item.image) {
    document.getElementById('item-img-preview').innerHTML = `<img src="/${item.image}" class="img-preview">`;
  }
  openModal('item-modal');
}
let variantCount = 0;
function addVariant(name='', price='') {
  const list = document.getElementById('variants-list');
  const id   = variantCount++;
  const div  = document.createElement('div');
  div.style.cssText = 'display:flex;gap:.5rem;align-items:center;margin-bottom:.4rem';
  div.innerHTML = `
    <input type="text"   name="var_name[]"  placeholder="Name (e.g. Large)"  value="${name}"  style="flex:2">
    <input type="number" name="var_price[]" placeholder="Price" value="${price}" style="flex:1" min="0">
    <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:var(--danger);font-size:1.1rem">×</button>`;
  list.appendChild(div);
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
