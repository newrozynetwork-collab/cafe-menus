<?php
/**
 * Admin Login Page
 */
defined('ROOT') || define('ROOT', dirname(__DIR__));
require_once ROOT . '/core/DB.php';
require_once ROOT . '/core/Auth.php';
require_once ROOT . '/core/helpers.php';

Auth::init();

// Already logged in → redirect
if (Auth::check()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');
    if ($email && $password) {
        $user = Auth::attempt($email, $password);
        if ($user) {
            header('Location: /admin/dashboard.php');
            exit;
        }
        $error = 'Invalid email or password.';
    } else {
        $error = 'Please fill in all fields.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Tirana Menu</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
  body { display:flex; align-items:center; justify-content:center; min-height:100vh; background:var(--gray-100); }
  .login-box { background:#fff; border-radius:12px; padding:2.5rem 2rem; width:100%; max-width:380px; box-shadow:0 4px 24px rgba(0,0,0,.1); }
  .login-logo { display:flex; align-items:center; gap:.65rem; margin-bottom:1.75rem; }
  .login-title { font-size:1.4rem; font-weight:700; }
  .login-sub { color:var(--gray-500); font-size:.875rem; margin-top:.15rem; }
  .error-box { background:#fee2e2; color:#b91c1c; border-radius:7px; padding:.6rem .85rem; font-size:.83rem; margin-bottom:1rem; }
  .login-footer { text-align:center; margin-top:1.25rem; font-size:.8rem; color:var(--gray-500); }
</style>
</head>
<body class="admin-body">
<div class="login-box">
  <div class="login-logo">
    <img src="/assets/images/tirana-logo.svg" alt="Tirana Point" style="width:110px;height:auto;object-fit:contain;">
    <div>
      <div class="login-sub" style="margin-top:.35rem">Admin Portal</div>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="error-box"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="on">
    <div class="form-group" style="margin-bottom:.85rem">
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" placeholder="admin@tirana.local" value="<?= e($_POST['email']??'') ?>" required autofocus>
    </div>
    <div class="form-group" style="margin-bottom:1.25rem">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="••••••••" required>
    </div>
    <button class="btn btn-primary" style="width:100%;justify-content:center" type="submit">Sign In</button>
  </form>
  <p class="login-footer">Default: admin@tirana.local / admin123</p>
</div>
</body>
</html>
