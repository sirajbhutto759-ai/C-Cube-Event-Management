<?php
// ============================================================
// C Cube – Participant: My Registrations
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
session_boot();
require_participant();
$pdo  = db();
$user = current_user();

$filter = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 8;

$where = "r.user_id = ?";
$params= [$user['id']];
if ($filter) { $where .= " AND r.registration_status = ?"; $params[] = $filter; }

$c_stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations r WHERE $where");
$c_stmt->execute($params);
$total = (int)$c_stmt->fetchColumn();
$pages = max(1, (int)ceil($total/$per));
$page  = max(1, min($page, $pages));

$d_stmt = $pdo->prepare(
    "SELECT r.*, e.title AS event_title, e.slug AS event_slug, e.event_date, e.registration_fee,
            p.payment_status, p.transaction_reference, p.submitted_at AS pay_submitted
     FROM registrations r
     JOIN events e ON e.id=r.event_id
     LEFT JOIN payments p ON p.registration_id=r.id
     WHERE $where ORDER BY r.created_at DESC LIMIT $per OFFSET " . (($page-1)*$per)
);
$d_stmt->execute($params);
$regs = $d_stmt->fetchAll();

$statuses = ['pending_payment','pending_approval','approved','rejected','cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>My Registrations – C Cube</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/animations.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/dashboard.css">
</head>
<body class="dashboard-body">
<nav class="navbar scrolled" style="position:sticky;top:0">
  <div class="nav-container">
    <a href="<?= BASE_URL ?>" class="nav-brand"><span class="brand-icon">C³</span><span class="brand-text">C <span class="brand-accent">Cube</span></span></a>
    <div style="display:flex;gap:0.5rem">
      <a href="<?= BASE_URL ?>events.php" class="btn btn-outline btn-sm"><i class="fa fa-search"></i> Events</a>
      <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</nav>
<?php $flash = get_flash(); if ($flash): ?>
<div class="toast-container"><div class="toast toast-<?= e($flash['type']) ?> show"><i class="fa fa-check-circle"></i><span><?= e($flash['message']) ?></span><button class="toast-close" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button></div></div>
<?php endif; ?>

<div class="dashboard-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div><div class="sidebar-user-name"><?= e(explode(' ',$user['full_name'])[0]) ?></div><div class="sidebar-user-role">Participant</div></div>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= BASE_URL ?>participant/dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>participant/my-registrations.php" class="active"><i class="fa fa-list-alt"></i> My Registrations</a>
      <a href="<?= BASE_URL ?>participant/payment.php"><i class="fa fa-credit-card"></i> Payments</a>
      <a href="<?= BASE_URL ?>participant/notifications.php"><i class="fa fa-bell"></i> Notifications</a>
      <div class="sidebar-section">Account</div>
      <a href="<?= BASE_URL ?>participant/profile.php"><i class="fa fa-user"></i> Profile</a>
      <a href="<?= BASE_URL ?>events.php"><i class="fa fa-compass"></i> Explore Events</a>
      <a href="<?= BASE_URL ?>logout.php" style="color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <main class="main-content">
    <div class="page-header">
      <h1 class="page-title">My Registrations</h1>
      <p class="page-subtitle">Track all your competition registrations and payment statuses.</p>
    </div>

    <!-- Filter -->
    <div class="filter-bar mb-3">
      <a href="?status=" class="cat-pill <?= !$filter?'active':'' ?>">All</a>
      <?php foreach ($statuses as $st): ?>
        <a href="?status=<?= $st ?>" class="cat-pill <?= $filter===$st?'active':'' ?>"><?= ucwords(str_replace('_',' ',$st)) ?></a>
      <?php endforeach; ?>
    </div>

    <div class="text-sm text-muted mb-3"><?= $total ?> registration<?= $total!=1?'s':'' ?></div>

    <?php if (empty($regs)): ?>
      <div class="empty-state">
        <i class="fa fa-inbox"></i>
        <h3>No registrations found</h3>
        <p>You haven't registered for any competitions yet.</p>
        <a href="<?= BASE_URL ?>events.php" class="btn btn-primary btn-sm mt-3"><i class="fa fa-search"></i> Explore Events</a>
      </div>
    <?php else: ?>
      <?php foreach ($regs as $reg): ?>
      <div class="reg-card" data-reveal>
        <div class="reg-card-info">
          <div class="reg-card-title"><?= e($reg['event_title']) ?></div>
          <div class="reg-card-ref">Ref: <?= e($reg['registration_reference']) ?></div>
          <div class="reg-card-meta">
            <span><i class="fa fa-calendar"></i> <?= fmt_date($reg['event_date'],'M j, Y') ?></span>
            <span><i class="fa fa-tag"></i> <?= fmt_money($reg['registration_fee']) ?></span>
            <span><i class="fa fa-users"></i> <?= ucfirst($reg['participation_type']) ?></span>
            <span><i class="fa fa-credit-card"></i> <?= status_badge($reg['payment_status']??'not_submitted') ?></span>
          </div>
          <!-- Timeline -->
          <div class="timeline mt-2">
            <?php
            $steps = [
              ['Registered',       'done',  $reg['created_at']],
              ['Payment Submitted', ($reg['payment_status']==='not_submitted')?'':'done',  $reg['pay_submitted']??''],
              ['Payment Verified',  $reg['payment_status']==='verified'?'done':($reg['payment_status']==='rejected'?'failed':''), ''],
              ['Registration Approved', $reg['registration_status']==='approved'?'done':($reg['registration_status']==='rejected'?'failed':''), $reg['reviewed_at']??''],
            ];
            foreach ($steps as [$label, $state, $date]):
            ?>
            <div class="timeline-item">
              <div class="timeline-dot <?= $state ?>"></div>
              <div class="timeline-label"><?= $label ?></div>
              <?php if ($date): ?><div class="timeline-meta"><?= fmt_date($date,'M j, Y g:i A') ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if ($reg['admin_note']): ?>
            <div class="alert alert-info mt-2 text-xs"><i class="fa fa-info-circle"></i> Admin note: <?= e($reg['admin_note']) ?></div>
          <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.5rem">
          <?= status_badge($reg['registration_status']) ?>
          <div class="reg-card-actions">
            <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($reg['event_slug']) ?>" class="btn-icon btn-icon-primary" title="View Event"><i class="fa fa-eye"></i></a>
            <?php if (in_array($reg['payment_status']??'',['not_submitted','rejected'])&&!in_array($reg['registration_status'],['approved','rejected','cancelled'])): ?>
              <a href="<?= BASE_URL ?>participant/payment.php?ref=<?= urlencode($reg['registration_reference']) ?>" class="btn btn-primary btn-sm"><i class="fa fa-credit-card"></i> Submit Payment</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- Pagination -->
      <?php if ($pages>1): ?>
      <div class="pagination mt-3" style="justify-content:center">
        <?php for ($p=1;$p<=$pages;$p++): ?>
          <a class="page-btn <?= $p===$page?'active':'' ?>" href="?page=<?= $p ?>&status=<?= urlencode($filter) ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
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
</body></html>
