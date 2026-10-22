<?php
// ============================================================
// C Cube – Participant Notifications
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
session_boot();
require_participant();
$pdo  = db();
$user = current_user();

// Handle Mark All As Read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0");
        $stmt->execute([$user['id']]);
        redirect_with_message(BASE_URL . 'participant/notifications.php', 'success', 'All notifications marked as read.');
    } elseif ($action === 'mark_read') {
        $notif_id = (int)($_POST['notification_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        $stmt->execute([$notif_id, $user['id']]);
        redirect_with_message(BASE_URL . 'participant/notifications.php', 'success', 'Notification marked as read.');
    } elseif ($action === 'delete_all') {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id=?");
        $stmt->execute([$user['id']]);
        redirect_with_message(BASE_URL . 'participant/notifications.php', 'success', 'Notification history cleared.');
    }
}

// Fetch user notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$unread_count = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unread_count++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Notifications – C Cube</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
<style>
.notif-item {
  display: flex;
  gap: 1rem;
  padding: 1.15rem 1.25rem;
  border-radius: var(--radius-md);
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  margin-bottom: 0.75rem;
  transition: all 0.25s ease;
  align-items: flex-start;
}
.notif-item.unread {
  background: rgba(99, 102, 241, 0.08);
  border-color: rgba(99, 102, 241, 0.25);
}
.notif-item:hover {
  background: rgba(255, 255, 255, 0.05);
  transform: translateX(3px);
}
.notif-icon {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: rgba(99, 102, 241, 0.15);
  color: var(--accent-indigo);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  flex-shrink: 0;
}
.notif-item.unread .notif-icon {
  background: var(--accent-gradient);
  color: #fff;
  box-shadow: 0 0 12px rgba(99, 102, 241, 0.4);
}
.notif-content {
  flex: 1;
}
.notif-title {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.notif-desc {
  font-size: 0.88rem;
  color: var(--text-secondary);
  line-height: 1.5;
}
.notif-time {
  font-size: 0.75rem;
  color: var(--text-muted);
  margin-top: 0.4rem;
}
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
      <a href="<?= BASE_URL ?>participant/dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>participant/my-registrations.php"><i class="fa fa-list-alt"></i> My Registrations</a>
      <a href="<?= BASE_URL ?>participant/payment.php"><i class="fa fa-credit-card"></i> Payments</a>
      <a href="<?= BASE_URL ?>participant/notifications.php" class="active">
        <i class="fa fa-bell"></i> Notifications
        <?php if ($unread_count): ?>
          <span class="badge badge-danger" style="margin-left:auto"><?= $unread_count ?></span>
        <?php endif; ?>
      </a>
      <div class="sidebar-section">Account</div>
      <a href="<?= BASE_URL ?>participant/profile.php"><i class="fa fa-user"></i> Profile</a>
      <a href="<?= BASE_URL ?>events.php"><i class="fa fa-compass"></i> Explore Events</a>
      <a href="<?= BASE_URL ?>logout.php" style="color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- Main Content -->
  <main class="dashboard-main">
    <div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
      <div>
        <h1 class="dashboard-title">Notifications</h1>
        <p class="dashboard-subtitle">Stay updated on registration approvals, payment verifications, and event alerts</p>
      </div>
      <?php if (!empty($notifications)): ?>
      <div style="display:flex;gap:0.75rem;align-items:center">
        <?php if ($unread_count > 0): ?>
        <form method="POST" action="" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="mark_all_read">
          <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa fa-check-double"></i> Mark all as read
          </button>
        </form>
        <?php endif; ?>
        <form method="POST" action="" style="display:inline" onsubmit="return confirm('Clear all notifications? This cannot be undone.')">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete_all">
          <button type="submit" class="btn btn-outline btn-sm text-danger" style="border-color:rgba(239,68,68,0.3)">
            <i class="fa fa-trash-alt"></i> Clear All
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
      <div class="dash-card text-center" style="padding:4rem 2rem">
        <div style="font-size:3.5rem;color:var(--text-muted);margin-bottom:1rem">
          <i class="fa fa-bell-slash"></i>
        </div>
        <h3 style="color:var(--text-primary);font-size:1.25rem;font-weight:600;margin-bottom:0.5rem">No notifications yet</h3>
        <p style="color:var(--text-secondary);max-width:400px;margin:0 auto 1.5rem">
          You will receive updates here whenever there are status updates regarding your event registrations and payments.
        </p>
        <a href="<?= BASE_URL ?>events.php" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Explore Competitions</a>
      </div>
    <?php else: ?>
      <div>
        <?php foreach ($notifications as $n): ?>
          <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>">
            <div class="notif-icon">
              <i class="fa <?= str_contains(strtolower($n['title']), 'approved') ? 'fa-check-circle' : (str_contains(strtolower($n['title']), 'rejected') ? 'fa-times-circle' : (str_contains(strtolower($n['title']), 'payment') ? 'fa-credit-card' : 'fa-bell')) ?>"></i>
            </div>
            <div class="notif-content">
              <div class="notif-title">
                <?= e($n['title']) ?>
                <?php if (!$n['is_read']): ?>
                  <span class="badge badge-warning" style="font-size:0.65rem;padding:0.15rem 0.45rem">New</span>
                <?php endif; ?>
              </div>
              <div class="notif-desc"><?= nl2br(e($n['message'])) ?></div>
              <div class="notif-time"><i class="fa fa-clock"></i> <?= fmt_date($n['created_at'], 'M j, Y g:i A') ?></div>
            </div>
            <?php if (!$n['is_read']): ?>
              <form method="POST" action="" style="align-self:center">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_read">
                <input type="hidden" name="notification_id" value="<?= $n['id'] ?>">
                <button type="submit" class="btn btn-outline btn-sm" title="Mark as read" style="padding:0.3rem 0.6rem;font-size:0.75rem">
                  <i class="fa fa-check"></i>
                </button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<button class="mobile-sidebar-toggle" id="mobileSidebarBtn"><i class="fa fa-bars"></i></button>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
const sidebar=document.getElementById('sidebar'),overlay=document.getElementById('sidebarOverlay'),btn=document.getElementById('mobileSidebarBtn');
btn?.addEventListener('click',()=>{sidebar.classList.toggle('open');overlay.classList.toggle('open');});
overlay?.addEventListener('click',()=>{sidebar.classList.remove('open');overlay.classList.remove('open');});
</script>
</body>
</html>
