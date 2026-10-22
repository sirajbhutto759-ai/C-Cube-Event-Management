<?php
// ============================================================
// C Cube – Participant Profile
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
session_boot();
require_participant();
$pdo  = db();
$user = current_user();

$errors = [];
$success_msg = '';

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $full_name   = trim($_POST['full_name'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $institution = trim($_POST['institution'] ?? '');

        if (!$full_name) {
            $errors[] = 'Full name is required.';
        } elseif (strlen($full_name) < 2 || strlen($full_name) > 120) {
            $errors[] = 'Full name must be between 2 and 120 characters.';
        }

        if (strlen($phone) > 30) {
            $errors[] = 'Phone number is too long.';
        }
        if (strlen($institution) > 200) {
            $errors[] = 'Institution name is too long.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, institution=? WHERE id=?");
            $stmt->execute([$full_name, $phone ?: null, $institution ?: null, $user['id']]);

            // Refresh user session
            $user['full_name']   = $full_name;
            $user['phone']       = $phone;
            $user['institution'] = $institution;
            $_SESSION['user']    = $user;

            redirect_with_message(BASE_URL . 'participant/profile.php', 'success', 'Profile details updated successfully!');
        }
    } elseif ($action === 'change_password') {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (!$current_pass) $errors[] = 'Current password is required.';
        if (strlen($new_pass) < 8) $errors[] = 'New password must be at least 8 characters long.';
        if ($new_pass !== $confirm_pass) $errors[] = 'New passwords do not match.';

        if (!$errors) {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=? LIMIT 1");
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();

            if (!$hash || !password_verify($current_pass, $hash)) {
                $errors[] = 'Incorrect current password.';
            } else {
                $new_hash = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$new_hash, $user['id']]);
                notify_user($user['id'], 'Security Alert', 'Your account password was updated successfully.');
                redirect_with_message(BASE_URL . 'participant/profile.php', 'success', 'Password updated successfully! Please use your new password next time.');
            }
        }
    }
}

// Fetch user data fresh from DB
$stmt = $pdo->prepare("SELECT full_name, email, phone, institution, role, account_status, created_at FROM users WHERE id=? LIMIT 1");
$stmt->execute([$user['id']]);
$userData = $stmt->fetch() ?: $user;

// Reg stats for participant badge
$s = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id=?"); $s->execute([$user['id']]); $reg_count = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id=? AND registration_status='approved'"); $s->execute([$user['id']]); $approved_count = (int)$s->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Profile – C Cube</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
</head>
<body class="dashboard-body">

<?php
$flash = get_flash();
if ($flash):
?>
<div class="toast-container" id="toastContainer">
  <div class="toast toast-<?= e($flash['type']) ?> show" id="flashToast">
    <i class="fa <?= $flash['type']==='success'?'fa-check-circle':'fa-info-circle' ?>"></i>
    <span><?= e($flash['message']) ?></span>
    <button class="toast-close" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
  </div>
</div>
<?php endif; ?>

<!-- Sticky nav -->
<nav class="navbar scrolled" style="position:sticky;top:0">
  <div class="nav-container">
    <a href="<?= BASE_URL ?>" class="nav-brand">
      <span class="brand-icon">C³</span>
      <span class="brand-text">C <span class="brand-accent">Cube</span></span>
    </a>
    <div style="display:flex;align-items:center;gap:0.75rem">
      <a href="<?= BASE_URL ?>events.php" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Events</a>
      <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171">
        <i class="fa fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="dashboard-layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar"><?= strtoupper(substr($userData['full_name'],0,1)) ?></div>
      <div>
        <div class="sidebar-user-name"><?= e(explode(' ',$userData['full_name'])[0]) ?></div>
        <div class="sidebar-user-role">Participant</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Main</div>
      <a href="<?= BASE_URL ?>participant/dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>participant/my-registrations.php"><i class="fa fa-list-alt"></i> My Registrations</a>
      <a href="<?= BASE_URL ?>participant/payment.php"><i class="fa fa-credit-card"></i> Payments</a>
      <a href="<?= BASE_URL ?>participant/notifications.php">
        <i class="fa fa-bell"></i> Notifications
        <?php $nc = unread_notification_count(); if ($nc): ?>
          <span class="badge badge-danger" style="margin-left:auto"><?= $nc ?></span>
        <?php endif; ?>
      </a>
      <div class="sidebar-section">Account</div>
      <a href="<?= BASE_URL ?>participant/profile.php" class="active"><i class="fa fa-user"></i> Profile</a>
      <a href="<?= BASE_URL ?>events.php"><i class="fa fa-compass"></i> Explore Events</a>
      <a href="<?= BASE_URL ?>logout.php" style="color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Profile Settings</h1>
        <p class="dashboard-subtitle">Manage your personal information, contact info, and security credentials</p>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger mb-4">
        <i class="fa fa-exclamation-circle"></i>
        <div>
          <?php foreach ($errors as $err): ?>
            <div><?= e($err) ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:1.5rem">
      <!-- Personal Details Form -->
      <div class="dash-card">
        <div class="dash-card-header">
          <div class="dash-card-title"><i class="fa fa-user-edit text-accent"></i> Personal Details</div>
        </div>
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_profile">

          <div class="form-group mb-3">
            <label class="form-label" for="full_name">Full Name <span class="text-danger">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($userData['full_name']) ?>" required>
          </div>

          <div class="form-group mb-3">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" class="form-control" value="<?= e($userData['email']) ?>" disabled style="opacity:0.7;cursor:not-allowed">
            <small style="color:var(--text-muted);font-size:0.75rem">Email cannot be changed directly for security reasons.</small>
          </div>

          <div class="form-group mb-3">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" class="form-control" placeholder="+92-300-1234567" value="<?= e($userData['phone'] ?? '') ?>">
          </div>

          <div class="form-group mb-4">
            <label class="form-label" for="institution">University / College / Institution</label>
            <input type="text" id="institution" name="institution" class="form-control" placeholder="e.g. FAST NUCES, NUST, UET" value="<?= e($userData['institution'] ?? '') ?>">
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%">
            <i class="fa fa-save"></i> Save Changes
          </button>
        </form>
      </div>

      <!-- Security / Change Password -->
      <div class="dash-card">
        <div class="dash-card-header">
          <div class="dash-card-title"><i class="fa fa-shield-alt text-accent"></i> Password & Security</div>
        </div>
        <form method="POST" action="">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="change_password">

          <div class="form-group mb-3">
            <label class="form-label" for="current_password">Current Password <span class="text-danger">*</span></label>
            <div style="position:relative">
              <input type="password" id="current_password" name="current_password" class="form-control" required style="padding-right:2.5rem">
              <button type="button" class="btn-toggle-pwd" onclick="togglePassword('current_password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="form-group mb-3">
            <label class="form-label" for="new_password">New Password <span class="text-danger">*</span></label>
            <div style="position:relative">
              <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required style="padding-right:2.5rem">
              <button type="button" class="btn-toggle-pwd" onclick="togglePassword('new_password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
                <i class="fa fa-eye"></i>
              </button>
            </div>
            <small style="color:var(--text-muted);font-size:0.75rem">Minimum 8 characters with letters and numbers.</small>
          </div>

          <div class="form-group mb-4">
            <label class="form-label" for="confirm_password">Confirm New Password <span class="text-danger">*</span></label>
            <div style="position:relative">
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="8" required style="padding-right:2.5rem">
              <button type="button" class="btn-toggle-pwd" onclick="togglePassword('confirm_password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-secondary" style="width:100%">
            <i class="fa fa-key"></i> Update Password
          </button>
        </form>

        <div style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid rgba(255,255,255,0.08);display:flex;justify-content:space-between;align-items:center;font-size:0.82rem;color:var(--text-muted)">
          <span>Member since</span>
          <span style="color:var(--text-primary);font-weight:600"><?= fmt_date($userData['created_at'],'M Y') ?></span>
        </div>
      </div>
    </div>
  </main>
</div>

<button class="mobile-sidebar-toggle" id="mobileSidebarBtn"><i class="fa fa-bars"></i></button>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebarOverlay'),btn=document.getElementById('mobileSidebarBtn');
btn?.addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('open');});
overlay?.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('open');});

function togglePassword(inputId, btnEl) {
  const input = document.getElementById(inputId);
  const icon = btnEl.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}
</script>
</body>
</html>
