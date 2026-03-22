<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';
Auth::requireRole('superadmin', '/admin/dashboard.php');

$pageTitle   = 'User Management';
$restaurants = DB::all('SELECT id,name FROM restaurants ORDER BY name');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);

    if ($act === 'user_save') {
        $data = [
            'name'          => trim($_POST['name'] ?? ''),
            'email'         => strtolower(trim($_POST['email'] ?? '')),
            'role'          => $_POST['role'] ?? 'editor',
            'restaurant_id' => $_POST['restaurant_id'] ?: null,
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }
        if ($uid) {
            DB::update('users', $data, 'id=?', [$uid]);
            flash('success', 'User updated.');
        } else {
            if (empty($data['password'])) { flash('error','Password required for new users.'); header('Location: /admin/users.php'); exit; }
            DB::insert('users', $data);
            flash('success', 'User created.');
        }
    }
    if ($act === 'user_delete' && $uid) {
        if ($uid == Auth::user()['id']) { flash('error','Cannot delete yourself.'); }
        else { DB::delete('users','id=?',[$uid]); flash('success','User deleted.'); }
    }
    header('Location: /admin/users.php');
    exit;
}

$users = DB::all('SELECT u.*,r.name AS rest_name FROM users u LEFT JOIN restaurants r ON r.id=u.restaurant_id ORDER BY u.created_at DESC');

ob_start(); ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">Admin Users (<?= count($users) ?>)</span>
    <button class="btn btn-primary" onclick="openModal('user-modal');resetUserForm()">+ New User</button>
  </div>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Restaurant Access</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
    <tr>
      <td>
        <div style="display:flex;align-items:center;gap:.6rem">
          <div class="user-avatar" style="width:28px;height:28px;font-size:.75rem;background:var(--primary)"><?= strtoupper($u['name'][0]) ?></div>
          <?= e($u['name']) ?>
        </div>
      </td>
      <td><?= e($u['email']) ?></td>
      <td>
        <span class="badge <?= $u['role']==='superadmin'?'badge-blue':($u['role']==='admin'?'badge-yellow':'badge-gray') ?>">
          <?= ucfirst($u['role']) ?>
        </span>
      </td>
      <td><?= e($u['rest_name'] ?? '🌐 All Restaurants') ?></td>
      <td style="font-size:.75rem;color:var(--gray-500)"><?= $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : 'Never' ?></td>
      <td><span class="badge <?= $u['is_active']?'badge-green':'badge-red' ?>"><?= $u['is_active']?'Active':'Inactive' ?></span></td>
      <td>
        <div class="action-btns">
          <button class="btn btn-outline btn-sm" onclick='editUser(<?= json_encode($u) ?>)'>Edit</button>
          <?php if ($u['id'] != Auth::user()['id']): ?>
          <form method="POST" style="display:inline" onsubmit="return confirm('Delete user?')">
            <input type="hidden" name="_action" value="user_delete">
            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
            <button class="btn btn-danger btn-sm">🗑</button>
          </form>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card" style="background:#eff6ff;border-color:#bfdbfe">
  <h3 style="font-size:.9rem;margin-bottom:.5rem">🔐 Roles Explained</h3>
  <ul style="font-size:.82rem;color:var(--gray-700);padding-left:1.25rem;line-height:1.8">
    <li><strong>Superadmin</strong> — full access: can manage all restaurants, users, system settings</li>
    <li><strong>Admin</strong> — can manage all restaurants but cannot manage users</li>
    <li><strong>Editor</strong> — limited to one assigned restaurant: can edit menu items and categories only</li>
  </ul>
</div>

<!-- User Modal -->
<div class="modal-backdrop" id="user-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="user-modal-title">New User</h3>
      <button onclick="closeModal('user-modal')" style="background:none;border:none;cursor:pointer;font-size:1.3rem">×</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="_action" value="user_save">
        <input type="hidden" name="user_id" id="user-id-input" value="">
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group">
            <label>Full Name *</label>
            <input type="text" name="name" id="user-name" required>
          </div>
          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" id="user-email" required>
          </div>
          <div class="form-group">
            <label>Password <span id="pw-hint" style="color:var(--gray-400);font-weight:400">(required for new user)</span></label>
            <input type="password" name="password" id="user-password" placeholder="Leave blank to keep current">
          </div>
          <div class="form-group">
            <label>Role</label>
            <select name="role" id="user-role" onchange="toggleRestField()">
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
              <option value="superadmin">Super Admin</option>
            </select>
          </div>
          <div class="form-group" id="rest-field">
            <label>Restaurant Access</label>
            <select name="restaurant_id" id="user-rest">
              <option value="">🌐 All Restaurants</option>
              <?php foreach ($restaurants as $r): ?>
              <option value="<?= $r['id'] ?>"><?= e($r['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="form-hint">For editors: limits them to one restaurant only</span>
          </div>
          <div class="form-group">
            <label style="display:flex;gap:.5rem;align-items:center">
              <input type="checkbox" name="is_active" id="user-active" value="1" checked> Active
            </label>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('user-modal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save User</button>
      </div>
    </form>
  </div>
</div>

<script>
function resetUserForm() {
  document.getElementById('user-modal-title').textContent = 'New User';
  document.getElementById('user-id-input').value = '';
  document.getElementById('user-name').value = '';
  document.getElementById('user-email').value = '';
  document.getElementById('user-password').value = '';
  document.getElementById('user-role').value = 'editor';
  document.getElementById('user-rest').value = '';
  document.getElementById('user-active').checked = true;
  document.getElementById('pw-hint').textContent = '(required for new user)';
  toggleRestField();
}
function editUser(u) {
  resetUserForm();
  document.getElementById('user-modal-title').textContent = 'Edit User';
  document.getElementById('user-id-input').value = u.id;
  document.getElementById('user-name').value  = u.name || '';
  document.getElementById('user-email').value = u.email || '';
  document.getElementById('user-role').value  = u.role || 'editor';
  document.getElementById('user-rest').value  = u.restaurant_id || '';
  document.getElementById('user-active').checked = !!u.is_active;
  document.getElementById('pw-hint').textContent = '(leave blank to keep current)';
  toggleRestField();
  openModal('user-modal');
}
function toggleRestField() {
  const role = document.getElementById('user-role').value;
  const field = document.getElementById('rest-field');
  field.style.display = role === 'editor' ? '' : 'none';
}
</script>

<?php
$content = ob_get_clean();
require_once ROOT . '/admin/layout.php';
