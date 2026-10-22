<?php
// ============================================================
// C Cube – Login Page
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
session_boot();

// Already logged in
if (is_logged_in()) {
    $r = current_user()['role'] === 'admin' ? 'admin/dashboard.php' : 'participant/dashboard.php';
    header('Location: ' . BASE_URL . $r); exit;
}

$errors  = [];
$email   = '';
$redirect = trim($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? '');

    if (!$email)    $errors[] = 'Email is required.';
    if (!$password) $errors[] = 'Password is required.';

    if (!$errors) {
        $result = login_user($email, $password, $_SERVER['REMOTE_ADDR'] ?? '');
        if ($result['success']) {
            $dest = ($redirect && filter_var($redirect, FILTER_VALIDATE_URL) && str_starts_with($redirect, BASE_URL))
                ? $redirect
                : BASE_URL . (current_user()['role'] === 'admin' ? 'admin/dashboard.php' : 'participant/dashboard.php');
            redirect_with_message($dest, 'success', $result['message']);
        } else {
            $errors[] = $result['message'];
        }
    }
}

$page_title = 'Login';
$body_class = 'auth-page-body';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
  <!-- Background orbs -->
  <div style="position:fixed;inset:0;pointer-events:none;z-index:0">
    <div style="position:absolute;width:500px;height:500px;top:-150px;right:-100px;background:radial-gradient(circle,rgba(108,99,255,0.12),transparent);border-radius:50%;filter:blur(60px)"></div>
    <div style="position:absolute;width:300px;height:300px;bottom:-50px;left:-50px;background:radial-gradient(circle,rgba(0,212,255,0.08),transparent);border-radius:50%;filter:blur(50px)"></div>
  </div>

  <div class="auth-card" data-reveal>
    <!-- Logo -->
    <div class="auth-logo">
      <a href="<?= BASE_URL ?>" class="nav-brand" style="justify-content:center">
        <span class="brand-icon">C³</span>
        <span class="brand-text">C <span class="brand-accent">Cube</span></span>
      </a>
    </div>

    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to your account to access your dashboard.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger mb-2"><i class="fa fa-exclamation-circle"></i> <?= e($err) ?></div>
    <?php endforeach; ?>

    <form method="POST" data-validate data-loading>
      <?= csrf_field() ?>
      <?php if ($redirect): ?>
        <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label" for="email">Email Address <span class="required">*</span></label>
        <input type="email" id="email" name="email" class="form-control" placeholder="you@example.com"
               value="<?= e($email) ?>" required autocomplete="email">
        <div class="form-error" style="display:none"></div>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password <span class="required">*</span></label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password"
                 required autocomplete="current-password">
          <button type="button" class="input-group-icon" data-toggle-pw aria-label="Toggle password">
            <i class="fa fa-eye-slash"></i>
          </button>
        </div>
        <div class="form-error" style="display:none"></div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:0.5rem">
        <i class="fa fa-sign-in-alt"></i> Sign In
      </button>
    </form>

    <div class="auth-divider">or</div>

    <p class="text-center text-sm text-muted">
      Don't have an account?
      <a href="<?= BASE_URL ?>register.php" class="text-primary fw-600">Create one free</a>
    </p>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
