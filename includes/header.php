<?php
// ============================================================
// C Cube – Header Include
// Expects: $page_title (string), $body_class (string optional)
// ============================================================
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/functions.php';
}
require_once __DIR__ . '/auth.php';
session_boot();
$flash         = get_flash();
$user          = current_user();
$notif_count   = unread_notification_count();
$site_name     = setting('site_name', 'C Cube');
$page_title    = isset($page_title) ? e($page_title) . ' – ' . $site_name : $site_name;
$body_class    = $body_class ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?></title>
<meta name="description" content="<?= e(setting('site_tagline','Where Innovation Meets Competition')) ?>">
<meta name="theme-color" content="#0f0f1a">

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Core CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/animations.css">

<!-- Favicon -->
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='6' fill='%236c63ff'/><text x='50%25' y='54%25' font-size='18' text-anchor='middle' dominant-baseline='middle' fill='white' font-family='Arial' font-weight='bold'>C³</text></svg>">
</head>
<body class="<?= e($body_class) ?>">

<!-- ─── NAVBAR ─────────────────────────────────────────────── -->
<nav class="navbar" id="mainNav">
  <div class="nav-container">
    <a href="<?= BASE_URL ?>" class="nav-brand">
      <span class="brand-icon">C³</span>
      <span class="brand-text">C <span class="brand-accent">Cube</span></span>
    </a>

    <ul class="nav-links" id="navLinks">
      <li><a href="<?= BASE_URL ?>" class="nav-link">Home</a></li>
      <li><a href="<?= BASE_URL ?>events.php" class="nav-link nav-highlight">Explore Events</a></li>
      <li><a href="<?= BASE_URL ?>#about" class="nav-link">About Us</a></li>
      <li><a href="<?= BASE_URL ?>#how-it-works" class="nav-link">How It Works</a></li>
      <li><a href="<?= BASE_URL ?>#contact" class="nav-link">Contact</a></li>

      <?php if ($user): ?>
        <?php if ($user['role'] === 'admin'): ?>
          <li><a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link">Admin Panel</a></li>
        <?php else: ?>
          <li><a href="<?= BASE_URL ?>participant/dashboard.php" class="nav-link">Dashboard</a></li>
        <?php endif; ?>
        <li class="nav-user-menu">
          <button class="nav-avatar-btn" id="userMenuBtn">
            <span class="avatar-initials"><?= e(strtoupper(substr($user['full_name'],0,1))) ?></span>
            <span><?= e(explode(' ',$user['full_name'])[0]) ?></span>
            <?php if ($notif_count > 0): ?>
              <span class="notif-dot"><?= $notif_count ?></span>
            <?php endif; ?>
            <i class="fa fa-chevron-down fa-xs"></i>
          </button>
          <div class="user-dropdown" id="userDropdown">
            <?php if ($user['role'] === 'admin'): ?>
              <a href="<?= BASE_URL ?>admin/dashboard.php"><i class="fa fa-shield-alt"></i> Admin Dashboard</a>
              <a href="<?= BASE_URL ?>admin/settings.php"><i class="fa fa-cog"></i> Settings</a>
            <?php else: ?>
              <a href="<?= BASE_URL ?>participant/profile.php"><i class="fa fa-user"></i> Profile</a>
              <a href="<?= BASE_URL ?>participant/notifications.php">
                <i class="fa fa-bell"></i> Notifications
                <?php if ($notif_count > 0): ?><span class="badge badge-danger"><?= $notif_count ?></span><?php endif; ?>
              </a>
            <?php endif; ?>
            <div class="dropdown-divider"></div>
            <a href="<?= BASE_URL ?>logout.php" class="text-danger"><i class="fa fa-sign-out-alt"></i> Logout</a>
          </div>
        </li>
      <?php else: ?>
        <li><a href="<?= BASE_URL ?>login.php" class="nav-link">Login</a></li>
        <li><a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-sm">Register</a></li>
      <?php endif; ?>
    </ul>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Flash Message -->
<?php if ($flash): ?>
<div class="toast-container" id="toastContainer">
  <div class="toast toast-<?= e($flash['type']) ?> show" id="flashToast">
    <i class="fa <?= $flash['type']==='success'?'fa-check-circle':($flash['type']==='error'?'fa-times-circle':'fa-info-circle') ?>"></i>
    <span><?= e($flash['message']) ?></span>
    <button class="toast-close" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>
  </div>
</div>
<?php endif; ?>
