<?php
// ============================================================
// C Cube – Registration Page
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
session_boot();

if (is_logged_in()) {
    header('Location: ' . BASE_URL . 'participant/dashboard.php'); exit;
}

$errors = []; $success = false;
$data = ['full_name'=>'','email'=>'','phone'=>'','institution'=>'','password'=>'','confirm_password'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    foreach ($data as $k => $_) $data[$k] = trim($_POST[$k] ?? '');
    $data['password'] = $_POST['password'] ?? '';
    $data['confirm_password'] = $_POST['confirm_password'] ?? '';

    // Validation
    if (!$data['full_name'])      $errors[] = 'Full name is required.';
    if (!$data['email'])          $errors[] = 'Email is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($data['password']) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($data['password'] !== $data['confirm_password']) $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $result = register_user($data);
        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = $result['message'];
        }
    }
}

$page_title = 'Create Account';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
  <div style="position:fixed;inset:0;pointer-events:none;z-index:0">
    <div style="position:absolute;width:500px;height:500px;top:-150px;left:-100px;background:radial-gradient(circle,rgba(108,99,255,0.1),transparent);border-radius:50%;filter:blur(60px)"></div>
    <div style="position:absolute;width:350px;height:350px;bottom:-50px;right:-50px;background:radial-gradient(circle,rgba(0,212,255,0.08),transparent);border-radius:50%;filter:blur(50px)"></div>
  </div>

  <div class="auth-card" style="max-width:520px" data-reveal>
    <div class="auth-logo">
      <a href="<?= BASE_URL ?>" class="nav-brand" style="justify-content:center">
        <span class="brand-icon">C³</span>
        <span class="brand-text">C <span class="brand-accent">Cube</span></span>
      </a>
    </div>

    <?php if ($success): ?>
      <div style="text-align:center;padding:1rem 0">
        <div style="width:64px;height:64px;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.5rem;color:#4ade80">
          <i class="fa fa-check-circle"></i>
        </div>
        <h2 style="margin-bottom:0.5rem">Account Created!</h2>
        <p class="text-muted mb-3">Welcome to C Cube. You can now log in and start exploring competitions.</p>
        <a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-block btn-lg">
          <i class="fa fa-sign-in-alt"></i> Sign In Now
        </a>
      </div>
    <?php else: ?>
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-subtitle">Join C Cube and start competing.</p>

      <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger mb-2"><i class="fa fa-exclamation-circle"></i> <?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="POST" data-validate data-loading>
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= e($data['full_name']) ?>" placeholder="Your full name" required autocomplete="name">
            <div class="form-error" style="display:none"></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control"
                   value="<?= e($data['email']) ?>" placeholder="you@example.com" required autocomplete="email">
            <div class="form-error" style="display:none"></div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" class="form-control"
                   value="<?= e($data['phone']) ?>" placeholder="+92-300-0000000" autocomplete="tel">
          </div>
          <div class="form-group">
            <label class="form-label" for="institution">Institution / Organization</label>
            <input type="text" id="institution" name="institution" class="form-control"
                   value="<?= e($data['institution']) ?>" placeholder="Your university or org">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="password">Password <span class="required">*</span></label>
            <div class="input-group">
              <input type="password" id="password" name="password" class="form-control"
                     placeholder="Min 8 characters" required autocomplete="new-password">
              <button type="button" class="input-group-icon" data-toggle-pw aria-label="Toggle password">
                <i class="fa fa-eye-slash"></i>
              </button>
            </div>
            <div class="form-error" style="display:none"></div>
          </div>
          <div class="form-group">
            <label class="form-label" for="confirm_password">Confirm Password <span class="required">*</span></label>
            <div class="input-group">
              <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                     placeholder="Repeat password" required autocomplete="new-password">
              <button type="button" class="input-group-icon" data-toggle-pw aria-label="Toggle password">
                <i class="fa fa-eye-slash"></i>
              </button>
            </div>
            <div class="form-error" style="display:none"></div>
          </div>
        </div>

        <div class="form-group">
          <label style="display:flex;align-items:flex-start;gap:0.6rem;cursor:pointer;font-size:0.85rem;color:var(--clr-text-muted)">
            <input type="checkbox" name="terms" required style="margin-top:2px;accent-color:var(--clr-primary)">
            I agree to the <a href="#" class="text-primary">Terms of Service</a> and <a href="#" class="text-primary">Privacy Policy</a>.
          </label>
        </div>

        <button type="submit" class="btn btn-primary btn-block btn-lg">
          <i class="fa fa-user-plus"></i> Create Account
        </button>
      </form>

      <div class="auth-divider">already have an account?</div>
      <p class="text-center text-sm">
        <a href="<?= BASE_URL ?>login.php" class="text-primary fw-600">Sign In</a>
      </p>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
