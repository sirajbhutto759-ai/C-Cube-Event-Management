<?php
// ============================================================
// C Cube – Admin Header Partial
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
session_boot();
require_admin();

$admin_user = current_user();
$pdo        = db();

// Dynamic counter badges for admin sidebar
$badge_stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'");
$pending_payments_count = (int)$badge_stmt->fetchColumn();

$badge_stmt2 = $pdo->query("SELECT COUNT(*) FROM registrations WHERE registration_status IN ('pending_payment', 'pending_approval')");
$pending_registrations_count = (int)$badge_stmt2->fetchColumn();

// Active nav detection
$current_page = basename($_SERVER['PHP_SELF']);
$page_title   = $page_title ?? 'Admin Panel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= e($page_title) ?> – C Cube Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
<style>
/* Admin specific enhancements */
.admin-badge {
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.35);
  color: #fca5a5;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  padding: 0.2rem 0.5rem;
  border-radius: var(--radius-sm);
}
.table-responsive {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}
.admin-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}
.admin-table th {
  text-align: left;
  padding: 0.85rem 1rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}
.admin-table td {
  padding: 0.9rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  color: var(--text-secondary);
  vertical-align: middle;
}
.admin-table tr:hover td {
  background: rgba(255, 255, 255, 0.02);
  color: var(--text-primary);
}
.modal-backdrop {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.75);
  backdrop-filter: blur(4px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}
.modal-backdrop.open {
  display: flex;
}
.modal-box {
  background: #0f1123;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: var(--radius-lg);
  max-width: 560px;
  width: 100%;
  box-shadow: 0 20px 40px rgba(0,0,0,0.6);
  padding: 1.75rem;
  position: relative;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.modal-title {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text-primary);
}
.modal-close {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.25rem;
}
.modal-close:hover { color: #fff; }
</style>
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

<!-- Top Sticky Navbar -->
<nav class="navbar scrolled" style="position:sticky;top:0;z-index:150">
  <div class="nav-container">
    <div style="display:flex;align-items:center;gap:1rem">
      <a href="<?= BASE_URL ?>" class="nav-brand">
        <span class="brand-icon">C³</span>
        <span class="brand-text">C <span class="brand-accent">Cube</span></span>
      </a>
      <span class="admin-badge"><i class="fa fa-shield-alt"></i> Admin Panel</span>
    </div>
    <div style="display:flex;align-items:center;gap:0.75rem">
      <a href="<?= BASE_URL ?>" class="btn btn-outline btn-sm" target="_blank" title="View Public Website">
        <i class="fa fa-external-link-alt"></i> View Site
      </a>
      <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171">
        <i class="fa fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
</nav>

<div class="dashboard-layout">
  <!-- Admin Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
        <i class="fa fa-user-shield"></i>
      </div>
      <div>
        <div class="sidebar-user-name"><?= e(explode(' ', $admin_user['full_name'])[0]) ?></div>
        <div class="sidebar-user-role" style="color:#f87171">Administrator</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Overview</div>
      <a href="<?= BASE_URL ?>admin/dashboard.php" class="<?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa fa-tachometer-alt"></i> Dashboard
      </a>

      <div class="sidebar-section">Management</div>
      <a href="<?= BASE_URL ?>admin/events.php" class="<?= in_array($current_page, ['events.php','event-create.php','event-edit.php']) ? 'active' : '' ?>">
        <i class="fa fa-calendar-alt"></i> Events
      </a>
      <a href="<?= BASE_URL ?>admin/categories.php" class="<?= $current_page === 'categories.php' ? 'active' : '' ?>">
        <i class="fa fa-tags"></i> Categories
      </a>
      <a href="<?= BASE_URL ?>admin/registrations.php" class="<?= $current_page === 'registrations.php' ? 'active' : '' ?>">
        <i class="fa fa-clipboard-list"></i> Registrations
        <?php if ($pending_registrations_count > 0): ?>
          <span class="badge badge-warning" style="margin-left:auto"><?= $pending_registrations_count ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>admin/payments.php" class="<?= $current_page === 'payments.php' ? 'active' : '' ?>">
        <i class="fa fa-credit-card"></i> Payments
        <?php if ($pending_payments_count > 0): ?>
          <span class="badge badge-danger" style="margin-left:auto"><?= $pending_payments_count ?></span>
        <?php endif; ?>
      </a>
      <a href="<?= BASE_URL ?>admin/participants.php" class="<?= $current_page === 'participants.php' ? 'active' : '' ?>">
        <i class="fa fa-users"></i> Participants
      </a>

      <div class="sidebar-section">Analytics & System</div>
      <a href="<?= BASE_URL ?>admin/reports.php" class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
        <i class="fa fa-chart-pie"></i> Reports & Export
      </a>
      <a href="<?= BASE_URL ?>admin/audit-logs.php" class="<?= $current_page === 'audit-logs.php' ? 'active' : '' ?>">
        <i class="fa fa-history"></i> Audit Logs
      </a>
      <a href="<?= BASE_URL ?>admin/settings.php" class="<?= $current_page === 'settings.php' ? 'active' : '' ?>">
        <i class="fa fa-cog"></i> Website Settings
      </a>

      <div class="sidebar-section">Session</div>
      <a href="<?= BASE_URL ?>logout.php" style="color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Admin Main Content Area -->
  <main class="dashboard-main">
