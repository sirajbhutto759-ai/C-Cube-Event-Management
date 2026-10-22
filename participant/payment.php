<?php
// ============================================================
// C Cube – Participant: Payment Submission
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
session_boot();
require_participant();
$pdo  = db();
$user = current_user();

$ref = trim($_GET['ref'] ?? '');
$errors = [];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $ref_post = trim($_POST['registration_reference'] ?? '');

    // Fetch registration (must belong to this user)
    $stmt = $pdo->prepare(
        "SELECT r.*, e.registration_fee, e.title AS event_title, p.id AS pay_id, p.payment_status
         FROM registrations r
         JOIN events e ON e.id=r.event_id
         JOIN payments p ON p.registration_id=r.id
         WHERE r.registration_reference=? AND r.user_id=? LIMIT 1"
    );
    $stmt->execute([$ref_post, $user['id']]);
    $reg = $stmt->fetch();

    if (!$reg)                               $errors[] = 'Registration not found.';
    elseif ($reg['payment_status']==='verified') $errors[] = 'Payment is already verified.';
    elseif ($reg['payment_status']==='pending')  $errors[] = 'Payment is already submitted and pending verification.';
    elseif (in_array($reg['registration_status'],['approved','rejected','cancelled'])) $errors[] = 'This registration cannot accept payments.';

    if (!$errors) {
        $tx_ref  = trim($_POST['transaction_reference'] ?? '');
        $amount  = (float)($_POST['amount'] ?? 0);
        if (!$tx_ref) $errors[] = 'Transaction reference is required.';
        if ($amount <= 0) $errors[] = 'Enter the payment amount.';
        // Always validate against event fee on server
        if ($amount < $reg['registration_fee']) {
            $errors[] = 'Payment amount (' . fmt_money($amount) . ') is less than the required fee (' . fmt_money($reg['registration_fee']) . ').';
        }
    }

    if (!$errors) {
        // Handle receipt upload
        $receipt_path = null;
        if (!empty($_FILES['receipt']['name'])) {
            try {
                $receipt_path = upload_receipt($_FILES['receipt']);
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            // Update payment
            $pdo->prepare(
                "UPDATE payments SET transaction_reference=?, amount=?, receipt_path=?, payment_status='pending', submitted_at=NOW()
                 WHERE registration_id=?"
            )->execute([$tx_ref, $amount, $receipt_path, $reg['id']]);
            // Update registration status to pending_approval
            $pdo->prepare(
                "UPDATE registrations SET registration_status='pending_approval', updated_at=NOW() WHERE id=?"
            )->execute([$reg['id']]);
            // Notify
            notify_user($user['id'], 'Payment Submitted',
                "Your payment for \"{$reg['event_title']}\" (Ref: {$reg['registration_reference']}) has been submitted and is awaiting verification.");
            $pdo->commit();
            redirect_with_message(BASE_URL . 'participant/my-registrations.php', 'success', 'Payment submitted successfully! Awaiting admin verification.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('[CCube Pay] '.$e->getMessage());
            $errors[] = 'Payment submission failed. Please try again.';
        }
    }
}

// Load registration for display
$reg = null;
if ($ref) {
    $stmt = $pdo->prepare(
        "SELECT r.*, e.title AS event_title, e.registration_fee, e.slug AS event_slug,
                p.id AS pay_id, p.payment_status, p.transaction_reference AS existing_tx,
                p.amount AS paid_amount, p.submitted_at, p.rejection_reason
         FROM registrations r
         JOIN events e ON e.id=r.event_id
         LEFT JOIN payments p ON p.registration_id=r.id
         WHERE r.registration_reference=? AND r.user_id=? LIMIT 1"
    );
    $stmt->execute([$ref, $user['id']]);
    $reg = $stmt->fetch();
}

// All pending-payment registrations (for dropdown)
$pending_regs = $pdo->prepare(
    "SELECT r.registration_reference, e.title AS event_title, e.registration_fee
     FROM registrations r JOIN events e ON e.id=r.event_id
     LEFT JOIN payments p ON p.registration_id=r.id
     WHERE r.user_id=? AND r.registration_status NOT IN ('approved','rejected','cancelled')
     AND (p.payment_status IS NULL OR p.payment_status IN ('not_submitted','rejected'))"
);
$pending_regs->execute([$user['id']]);
$pending_list = $pending_regs->fetchAll();

$instructions = setting('payment_instructions', 'Please contact admin for payment details.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Submit Payment – C Cube</title>
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
    <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm" style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171"><i class="fa fa-sign-out-alt"></i> Logout</a>
  </div>
</nav>
<?php $flash=get_flash(); if($flash): ?>
<div class="toast-container"><div class="toast toast-<?=e($flash['type'])?> show"><i class="fa fa-check-circle"></i><span><?=e($flash['message'])?></span><button class="toast-close" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button></div></div>
<?php endif; ?>

<div class="dashboard-layout">
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-profile">
      <div class="sidebar-avatar"><?= strtoupper(substr($user['full_name'],0,1)) ?></div>
      <div><div class="sidebar-user-name"><?= e(explode(' ',$user['full_name'])[0]) ?></div><div class="sidebar-user-role">Participant</div></div>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= BASE_URL ?>participant/dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= BASE_URL ?>participant/my-registrations.php"><i class="fa fa-list-alt"></i> My Registrations</a>
      <a href="<?= BASE_URL ?>participant/payment.php" class="active"><i class="fa fa-credit-card"></i> Payments</a>
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
      <h1 class="page-title">Submit Payment</h1>
      <p class="page-subtitle">Submit your transaction reference after completing the bank transfer.</p>
    </div>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger mb-2"><i class="fa fa-exclamation-circle"></i> <?= e($e) ?></div>
    <?php endforeach; ?>

    <?php if ($reg && in_array($reg['payment_status'],['pending','verified'])): ?>
      <div class="alert alert-<?= $reg['payment_status']==='verified'?'success':'info' ?> mb-3">
        <i class="fa fa-<?= $reg['payment_status']==='verified'?'check-circle':'clock' ?>"></i>
        <?php if ($reg['payment_status']==='verified'): ?>
          Payment for this registration has been verified. No further action required.
        <?php else: ?>
          Payment submitted on <?= fmt_date($reg['submitted_at'],'M j, Y') ?>. Awaiting admin verification.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($reg && $reg['payment_status']==='rejected'): ?>
      <div class="alert alert-danger mb-3">
        <i class="fa fa-times-circle"></i>
        <div>Payment was rejected. Reason: <strong><?= e($reg['rejection_reason']??'No reason provided.') ?></strong><br>Please resubmit with correct information.</div>
      </div>
    <?php endif; ?>

    <!-- Payment Instructions -->
    <div class="card mb-3" data-reveal>
      <h3 style="margin-bottom:1rem"><i class="fa fa-university text-primary"></i> Payment Instructions</h3>
      <div class="payment-instructions"><?= e($instructions) ?></div>
      <div class="alert alert-warning" style="margin-top:0.75rem">
        <i class="fa fa-info-circle"></i>
        All payments are <strong>manually verified</strong> by our admin team. Do NOT submit until you have completed the bank transfer.
      </div>
    </div>

    <!-- Form -->
    <?php if (!empty($pending_list) || $reg): ?>
    <div class="card payment-box" data-reveal>
      <h3 style="margin-bottom:1.25rem"><i class="fa fa-paper-plane text-primary"></i> Submit Payment Details</h3>
      <form method="POST" enctype="multipart/form-data" data-validate data-loading>
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Select Registration <span class="required">*</span></label>
          <select name="registration_reference" class="form-control" required>
            <option value="">-- Select a Registration --</option>
            <?php foreach ($pending_list as $pr): ?>
              <option value="<?= e($pr['registration_reference']) ?>" <?= ($ref===$pr['registration_reference'])?'selected':'' ?>>
                <?= e($pr['registration_reference']) ?> – <?= e($pr['event_title']) ?> (<?= fmt_money($pr['registration_fee']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-error" style="display:none"></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Transaction Reference <span class="required">*</span></label>
            <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. TXN-1234567890" required>
            <div class="form-hint">The reference number from your bank transfer receipt.</div>
            <div class="form-error" style="display:none"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Amount Paid (<?= e(setting('payment_currency','PKR')) ?>) <span class="required">*</span></label>
            <input type="number" name="amount" class="form-control" placeholder="0.00" step="0.01" min="1" required>
            <div class="form-hint">Enter the exact amount transferred.</div>
            <div class="form-error" style="display:none"></div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Payment Receipt (optional)</label>
          <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf">
          <div class="form-hint">Upload a screenshot or scan of your payment receipt. JPG, PNG, PDF – max 5MB.</div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
          <i class="fa fa-paper-plane"></i> Submit Payment
        </button>
      </form>
    </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="fa fa-credit-card"></i>
        <h3>No pending payments</h3>
        <p>You have no registrations requiring payment submission.</p>
        <a href="<?= BASE_URL ?>events.php" class="btn btn-primary btn-sm mt-3">Explore Events</a>
      </div>
    <?php endif; ?>
  </main>
</div>
<button class="mobile-sidebar-toggle" id="mobileSidebarBtn"><i class="fa fa-bars"></i></button>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
<script>
const s=document.getElementById('sidebar'),o=document.getElementById('sidebarOverlay'),b=document.getElementById('mobileSidebarBtn');
b?.addEventListener('click',()=>{s.classList.toggle('open');o.classList.toggle('open');});
o?.addEventListener('click',()=>{s.classList.remove('open');o.classList.remove('open');});
</script>
</body></html>
