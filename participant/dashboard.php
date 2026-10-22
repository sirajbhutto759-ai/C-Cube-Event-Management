<?php
// ============================================================
// C Cube – Participant Dashboard
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
session_boot();
require_participant();
$pdo  = db();
$user = current_user();

// Refresh user from DB
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->execute([$user['id']]);
$user = $stmt->fetch();
unset($user['password_hash']);
$_SESSION['user'] = $user;

// Stats
$total_reg  = (int)$pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id=?")->execute([$user['id']]) ? 0 : 0;
$s          = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id=?"); $s->execute([$user['id']]); $total_reg = (int)$s->fetchColumn();
$s          = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id=? AND registration_status='approved'"); $s->execute([$user['id']]); $approved = (int)$s->fetchColumn();
$s          = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE user_id=? AND registration_status IN ('pending_payment','pending_approval')"); $s->execute([$user['id']]); $pending = (int)$s->fetchColumn();
$s          = $pdo->prepare("SELECT COUNT(*) FROM payments p JOIN registrations r ON r.id=p.registration_id WHERE r.user_id=? AND p.payment_status='pending'"); $s->execute([$user['id']]); $pay_pending = (int)$s->fetchColumn();

// Recent registrations
$recent = $pdo->prepare(
    "SELECT r.*, e.title AS event_title, e.slug AS event_slug, e.event_date,
            p.payment_status, p.id AS payment_id
     FROM registrations r
     JOIN events e ON e.id=r.event_id
     LEFT JOIN payments p ON p.registration_id=r.id
     WHERE r.user_id=?
     ORDER BY r.created_at DESC LIMIT 5"
);
$recent->execute([$user['id']]);
$recent_regs = $recent->fetchAll();

// Unread notifications
$notifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 3");
$notifs->execute([$user['id']]);
$unread_notifs = $notifs->fetchAll();

$page_title = 'My Dashboard';
$body_class = 'dashboard-body';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Dashboard – C Cube</title>
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
      <div class="sidebar-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div>
        <div class="sidebar-user-name"><?= e(explode(' ',$user['full_name'])[0]) ?></div>
        <div class="sidebar-user-role">Participant</div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="sidebar-section">Main</div>
      <a href="<?= BASE_URL ?>participant/dashboard.php" class="active"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>participant/my-registrations.php"><i class="fa fa-list-alt"></i> My Registrations</a>
      <a href="<?= BASE_URL ?>participant/payment.php"><i class="fa fa-credit-card"></i> Payments</a>
      <a href="<?= BASE_URL ?>participant/notifications.php">
        <i class="fa fa-bell"></i> Notifications
        <?php $nc = unread_notification_count(); if ($nc): ?>
          <span class="badge badge-danger" style="margin-left:auto"><?= $nc ?></span>
        <?php endif; ?>
      </a>
      <div class="sidebar-section">Account</div>
      <a href="<?= BASE_URL ?>participant/profile.php"><i class="fa fa-user"></i> Profile</a>
      <a href="<?= BASE_URL ?>events.php"><i class="fa fa-compass"></i> Explore Events</a>
      <a href="<?= BASE_URL ?>logout.php" style="color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Main -->
  <main class="main-content">
    <div class="page-header">
      <h1 class="page-title">Welcome back, <?= e(explode(' ',$user['full_name'])[0]) ?>! 👋</h1>
      <p class="page-subtitle">Here's an overview of your competition activity.</p>
    </div>

    <!-- Stat cards -->
    <div class="stat-cards" data-reveal-stagger>
      <div class="stat-card">
        <div class="stat-card-icon purple"><i class="fa fa-list-alt"></i></div>
        <div class="stat-card-value" data-counter data-target="<?= $total_reg ?>"><?= $total_reg ?></div>
        <div class="stat-card-label">Total Registrations</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon green"><i class="fa fa-check-circle"></i></div>
        <div class="stat-card-value" data-counter data-target="<?= $approved ?>"><?= $approved ?></div>
        <div class="stat-card-label">Approved</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon yellow"><i class="fa fa-clock"></i></div>
        <div class="stat-card-value" data-counter data-target="<?= $pending ?>"><?= $pending ?></div>
        <div class="stat-card-label">Pending</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-icon blue"><i class="fa fa-credit-card"></i></div>
        <div class="stat-card-value" data-counter data-target="<?= $pay_pending ?>"><?= $pay_pending ?></div>
        <div class="stat-card-label">Payments Pending</div>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="quick-actions mb-3">
      <a href="<?= BASE_URL ?>events.php" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Find Events</a>
      <a href="<?= BASE_URL ?>participant/my-registrations.php" class="btn btn-outline btn-sm"><i class="fa fa-list"></i> My Registrations</a>
    </div>

    <!-- Notifications -->
    <?php if (!empty($unread_notifs)): ?>
    <div class="dash-section" data-reveal>
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fa fa-bell text-primary"></i> New Notifications</div>
        <a href="<?= BASE_URL ?>participant/notifications.php" class="text-xs text-primary">View all</a>
      </div>
      <div class="notif-list">
        <?php foreach ($unread_notifs as $n): ?>
        <div class="notif-item unread">
          <div class="notif-dot-indicator"></div>
          <div>
            <div class="notif-title"><?= e($n['title']) ?></div>
            <div class="notif-message"><?= e($n['message']) ?></div>
            <div class="notif-time"><?= fmt_date($n['created_at'], 'M j, Y g:i A') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent Registrations -->
    <div class="dash-section" data-reveal>
      <div class="dash-section-header">
        <div class="dash-section-title"><i class="fa fa-list-alt text-primary"></i> Recent Registrations</div>
        <a href="<?= BASE_URL ?>participant/my-registrations.php" class="text-xs text-primary">View all</a>
      </div>
      <?php if (empty($recent_regs)): ?>
        <div class="empty-state" style="padding:2.5rem 1rem">
          <i class="fa fa-calendar-plus"></i>
          <h3>No registrations yet</h3>
          <p>Explore available competitions and register for your first event.</p>
          <a href="<?= BASE_URL ?>events.php" class="btn btn-primary btn-sm mt-3"><i class="fa fa-search"></i> Browse Events</a>
        </div>
      <?php else: ?>
        <?php foreach ($recent_regs as $reg): ?>
        <div class="reg-card">
          <div class="reg-card-info">
            <div class="reg-card-title"><?= e($reg['event_title']) ?></div>
            <div class="reg-card-ref"><?= e($reg['registration_reference']) ?></div>
            <div class="reg-card-meta">
              <span><i class="fa fa-calendar"></i> <?= fmt_date($reg['event_date'], 'M j, Y') ?></span>
              <span><i class="fa fa-credit-card"></i> Payment: <?= status_badge($reg['payment_status'] ?? 'not_submitted') ?></span>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem">
            <?= status_badge($reg['registration_status']) ?>
            <div class="reg-card-actions">
              <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($reg['event_slug']) ?>" class="btn-icon btn-icon-primary" title="View Event"><i class="fa fa-eye"></i></a>
              <?php if (in_array($reg['payment_status'],['not_submitted','rejected']) && !in_array($reg['registration_status'],['approved','rejected','cancelled'])): ?>
                <a href="<?= BASE_URL ?>participant/payment.php?ref=<?= urlencode($reg['registration_reference']) ?>" class="btn btn-primary btn-sm">
                  <i class="fa fa-credit-card"></i> Pay
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<button class="mobile-sidebar-toggle" id="mobileSidebarBtn"><i class="fa fa-bars"></i></button>

<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const btn     = document.getElementById('mobileSidebarBtn');
btn?.addEventListener('click', () => { sidebar.classList.toggle('open'); overlay.classList.toggle('open'); });
overlay?.addEventListener('click', () => { sidebar.classList.remove('open'); overlay.classList.remove('open'); });
</script>
</body>
</html>
