<?php
// ============================================================
// C Cube – Admin Payment Management & Verification
// ============================================================
$page_title = 'Payment Verifications';
require_once __DIR__ . '/admin-header.php';

// Handle Verify or Reject Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action     = $_POST['action'] ?? '';
    $payment_id = (int)($_POST['payment_id'] ?? 0);
    $reason     = trim($_POST['rejection_reason'] ?? '');
    $auto_approve_reg = !empty($_POST['auto_approve_reg']);

    // Fetch payment and registration details
    $stmt = $pdo->prepare(
        "SELECT p.*, r.id AS reg_id, r.user_id, r.registration_reference, r.registration_status,
                e.title AS event_title, u.full_name, u.email
         FROM payments p
         JOIN registrations r ON r.id = p.registration_id
         JOIN events e ON e.id = r.event_id
         JOIN users u ON u.id = r.user_id
         WHERE p.id = ? LIMIT 1"
    );
    $stmt->execute([$payment_id]);
    $pay = $stmt->fetch();

    if ($pay) {
        if ($action === 'verify') {
            try {
                $pdo->beginTransaction();

                // Mark payment as verified
                $stmt = $pdo->prepare(
                    "UPDATE payments SET
                        payment_status = 'verified',
                        verified_by = ?,
                        verified_at = NOW(),
                        rejection_reason = NULL,
                        updated_at = NOW()
                     WHERE id = ?"
                );
                $stmt->execute([$admin_user['id'], $payment_id]);

                // Update registration
                $new_reg_status = $auto_approve_reg ? 'approved' : 'pending_approval';
                $stmt = $pdo->prepare(
                    "UPDATE registrations SET
                        registration_status = ?,
                        reviewed_by = ?,
                        reviewed_at = NOW(),
                        updated_at = NOW()
                     WHERE id = ?"
                );
                $stmt->execute([$new_reg_status, $admin_user['id'], $pay['reg_id']]);

                // Participant notification
                notify_user(
                    $pay['user_id'],
                    'Payment Verified!',
                    "Your payment of " . fmt_money($pay['amount']) . " for \"{$pay['event_title']}\" (Ref: {$pay['registration_reference']}) has been verified!" . ($auto_approve_reg ? " Your registration is now officially APPROVED." : "")
                );

                audit_log('payment_verified', 'payments', $payment_id, "Verified payment of " . fmt_money($pay['amount']) . " for reg {$pay['registration_reference']}. Auto-approved: " . ($auto_approve_reg ? 'Yes' : 'No'));

                $pdo->commit();
                redirect_with_message(BASE_URL . 'admin/payments.php', 'success', "Payment for reference {$pay['registration_reference']} verified successfully!");
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[CCube Pay Verify] ' . $e->getMessage());
                redirect_with_message(BASE_URL . 'admin/payments.php', 'danger', "Database error while verifying payment.");
            }
        } elseif ($action === 'reject') {
            try {
                $pdo->beginTransaction();

                // Mark payment as rejected
                $stmt = $pdo->prepare(
                    "UPDATE payments SET
                        payment_status = 'rejected',
                        rejection_reason = ?,
                        verified_by = ?,
                        verified_at = NOW(),
                        updated_at = NOW()
                     WHERE id = ?"
                );
                $stmt->execute([$reason ?: 'Payment could not be verified.', $admin_user['id'], $payment_id]);

                // Reset registration back to pending_payment so user can resubmit
                $stmt = $pdo->prepare(
                    "UPDATE registrations SET
                        registration_status = 'pending_payment',
                        admin_note = ?,
                        updated_at = NOW()
                     WHERE id = ?"
                );
                $stmt->execute(["Payment rejected: " . ($reason ?: 'Unverified transaction'), $pay['reg_id']]);

                // Notify participant
                notify_user(
                    $pay['user_id'],
                    'Payment Verification Notice',
                    "Your payment for \"{$pay['event_title']}\" (Ref: {$pay['registration_reference']}) could not be verified." . ($reason ? " Reason: {$reason}" : "") . " Please check the payment instructions and submit a valid transaction reference."
                );

                audit_log('payment_rejected', 'payments', $payment_id, "Rejected payment for reg {$pay['registration_reference']}. Reason: {$reason}");

                $pdo->commit();
                redirect_with_message(BASE_URL . 'admin/payments.php', 'warning', "Payment marked as rejected and participant notified.");
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('[CCube Pay Reject] ' . $e->getMessage());
                redirect_with_message(BASE_URL . 'admin/payments.php', 'danger', "Database error while rejecting payment.");
            }
        }
    }
}

// Search and Filters
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'pending'); // Default to pending so admin immediately sees work
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where  = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(p.transaction_reference LIKE ? OR r.registration_reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter !== '' && $filter !== 'all') {
    $where[] = "p.payment_status = ?";
    $params[] = $filter;
}

$where_sql = implode(' AND ', $where);

// Count
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM payments p
     JOIN registrations r ON r.id = p.registration_id
     JOIN users u ON u.id = r.user_id
     JOIN events e ON e.id = r.event_id
     WHERE $where_sql"
);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

// Fetch payments
$data_sql = "
    SELECT p.*, r.registration_reference, r.registration_status,
           u.full_name AS participant_name, u.email AS participant_email, u.phone AS participant_phone,
           e.title AS event_title, e.registration_fee,
           v.full_name AS verifier_name
    FROM payments p
    JOIN registrations r ON r.id = p.registration_id
    JOIN users u ON u.id = r.user_id
    JOIN events e ON e.id = r.event_id
    LEFT JOIN users v ON v.id = p.verified_by
    WHERE $where_sql
    ORDER BY CASE WHEN p.payment_status = 'pending' THEN 0 ELSE 1 END, p.submitted_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">Manual Payment Verifications</h1>
    <p class="dashboard-subtitle">Inspect bank transfer slips, match transaction IDs with required fees, and verify submissions</p>
  </div>
  <div>
    <a href="<?= BASE_URL ?>admin/settings.php#payment_section" class="btn btn-outline btn-sm">
      <i class="fa fa-university"></i> Bank Account Instructions
    </a>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="dash-card mb-4" style="padding:1.25rem">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center">
    <div style="flex:1;min-width:240px;position:relative">
      <input type="text" name="search" class="form-control" placeholder="Search Tx ID, ref #, participant..." value="<?= e($search) ?>" style="padding-left:2.5rem">
      <i class="fa fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
    </div>

    <div style="display:flex;gap:0.35rem;flex-wrap:wrap">
      <a href="?filter=pending<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $filter === 'pending' ? 'btn-primary' : 'btn-outline' ?>">
        Pending Review
      </a>
      <a href="?filter=verified<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $filter === 'verified' ? 'btn-primary' : 'btn-outline' ?>">
        Verified
      </a>
      <a href="?filter=rejected<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $filter === 'rejected' ? 'btn-primary' : 'btn-outline' ?>">
        Rejected
      </a>
      <a href="?filter=all<?= $search ? '&search='.urlencode($search) : '' ?>" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-outline' ?>">
        All Submissions
      </a>
    </div>

    <?php if ($search || $filter !== 'pending'): ?>
      <a href="<?= BASE_URL ?>admin/payments.php" class="btn btn-outline btn-sm" style="margin-left:auto">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Payments Table -->
<div class="dash-card">
  <?php if (empty($payments)): ?>
    <div style="text-align:center;padding:3.5rem 1rem">
      <i class="fa fa-check-circle" style="font-size:3rem;color:#10b981;margin-bottom:1rem;display:block"></i>
      <h3 style="color:var(--text-primary);font-size:1.15rem;font-weight:600">No payment records found</h3>
      <p style="color:var(--text-secondary);max-width:380px;margin:0.5rem auto 1.5rem">
        There are currently no payments matching your selected filter.
      </p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Tx Reference & Receipt</th>
            <th>Participant</th>
            <th>Competition</th>
            <th>Amount vs Required</th>
            <th>Status</th>
            <th>Timeline</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td>
                <strong class="font-mono" style="color:var(--text-primary);font-size:0.85rem">
                  <?= e($pay['transaction_reference'] ?: 'Not Provided') ?>
                </strong>
                <div class="text-xs text-muted font-mono">Reg: <?= e($pay['registration_reference']) ?></div>

                <?php if (!empty($pay['receipt_path'])): ?>
                  <div class="mt-1">
                    <a href="<?= BASE_URL ?>view-receipt.php?payment_id=<?= $pay['id'] ?>" target="_blank" class="btn btn-outline btn-sm text-xs" style="padding:0.2rem 0.5rem;display:inline-flex;align-items:center;gap:0.3rem">
                      <i class="fa fa-file-invoice"></i> View Receipt
                    </a>
                  </div>
                <?php else: ?>
                  <span class="text-xs text-muted"><i class="fa fa-times"></i> No receipt attached</span>
                <?php endif; ?>
              </td>

              <td>
                <strong style="color:var(--text-primary)"><?= e($pay['participant_name']) ?></strong><br>
                <span class="text-xs text-muted"><?= e($pay['participant_email']) ?></span>
                <?php if ($pay['participant_phone']): ?>
                  <span class="text-xs text-muted"> • <?= e($pay['participant_phone']) ?></span>
                <?php endif; ?>
              </td>

              <td>
                <span style="color:var(--text-primary);font-weight:500"><?= e($pay['event_title']) ?></span>
              </td>

              <td>
                <strong style="color:#34d399;font-size:0.95rem"><?= fmt_money($pay['amount']) ?></strong><br>
                <span class="text-xs text-muted">Required: <?= fmt_money($pay['registration_fee']) ?></span>
                <?php if ($pay['amount'] < $pay['registration_fee']): ?>
                  <div class="badge badge-danger text-xs mt-1">Underpaid</div>
                <?php endif; ?>
              </td>

              <td>
                <?= status_badge($pay['payment_status']) ?>
                <?php if ($pay['rejection_reason']): ?>
                  <div class="text-xs text-danger mt-1" title="<?= e($pay['rejection_reason']) ?>">
                    <i class="fa fa-exclamation-circle"></i> <?= e($pay['rejection_reason']) ?>
                  </div>
                <?php endif; ?>
              </td>

              <td class="text-xs text-muted">
                <?php if ($pay['submitted_at']): ?>
                  <div><i class="fa fa-upload"></i> <?= fmt_date($pay['submitted_at'], 'M j, Y g:i A') ?></div>
                <?php endif; ?>
                <?php if ($pay['verified_at']): ?>
                  <div style="color:var(--text-primary);font-weight:500">
                    <i class="fa fa-user-check"></i> <?= e($pay['verifier_name'] ?? 'Admin') ?>
                  </div>
                  <div><?= fmt_date($pay['verified_at'], 'M j, Y') ?></div>
                <?php endif; ?>
              </td>

              <td style="text-align:right">
                <?php if ($pay['payment_status'] === 'pending'): ?>
                  <div style="display:inline-flex;gap:0.4rem">
                    <button type="button" class="btn btn-primary btn-sm" onclick="promptVerify(<?= $pay['id'] ?>, '<?= e(addslashes($pay['registration_reference'])) ?>', '<?= fmt_money($pay['amount']) ?>')" style="padding:0.3rem 0.65rem;font-size:0.75rem;background:#10b981;border-color:#10b981">
                      <i class="fa fa-check"></i> Verify
                    </button>
                    <button type="button" class="btn btn-outline btn-sm text-danger" onclick="promptReject(<?= $pay['id'] ?>, '<?= e(addslashes($pay['registration_reference'])) ?>')" style="padding:0.3rem 0.65rem;font-size:0.75rem;border-color:rgba(239,68,68,0.3)">
                      <i class="fa fa-times"></i> Reject
                    </button>
                  </div>
                <?php else: ?>
                  <span class="text-xs text-muted">Reviewed</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
      <div class="pagination mt-4" style="justify-content:center;display:flex;gap:0.5rem">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&filter=<?= urlencode($filter) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Verify Payment Modal -->
<div class="modal-backdrop" id="verifyModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title text-success"><i class="fa fa-check-circle"></i> Verify Payment</div>
      <button class="modal-close" onclick="closeModal('verifyModal')">&times;</button>
    </div>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="verify">
      <input type="hidden" name="payment_id" id="verifyPayId" value="">

      <p style="color:var(--text-secondary);margin-bottom:1.25rem">
        Verify payment receipt for registration <strong id="verifyRef" style="color:#fff"></strong> with amount <strong id="verifyAmount" style="color:#34d399"></strong>.
      </p>

      <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08);padding:1rem;border-radius:6px;margin-bottom:1.25rem">
        <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;color:var(--text-primary);font-size:0.875rem">
          <input type="checkbox" name="auto_approve_reg" value="1" checked style="width:16px;height:16px;accent-color:var(--clr-primary-2)">
          <span>Also automatically approve the participant's registration</span>
        </label>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:0.75rem">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('verifyModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="background:#10b981;border-color:#10b981"><i class="fa fa-check"></i> Confirm Verification</button>
      </div>
    </form>
  </div>
</div>

<!-- Reject Payment Modal -->
<div class="modal-backdrop" id="rejectModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title text-danger"><i class="fa fa-times-circle"></i> Reject Payment</div>
      <button class="modal-close" onclick="closeModal('rejectModal')">&times;</button>
    </div>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="reject">
      <input type="hidden" name="payment_id" id="rejectPayId" value="">

      <p style="color:var(--text-secondary);margin-bottom:1rem">
        Reject payment submission for registration <strong id="rejectRef" style="color:#fff"></strong>. The registration status will revert to <em>Pending Payment</em> and the participant will be notified to correct and resubmit.
      </p>

      <div class="form-group mb-4">
        <label class="form-label" for="rejection_reason">Rejection Reason <span class="text-danger">*</span></label>
        <textarea id="rejection_reason" name="rejection_reason" class="form-control" rows="3" placeholder="e.g. Transaction ID was not found in bank statement, amount is short, or blurred receipt." required></textarea>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:0.75rem">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('rejectModal')">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm" style="background:#dc2626;color:#fff"><i class="fa fa-times"></i> Reject Payment</button>
      </div>
    </form>
  </div>
</div>

<script>
function promptVerify(payId, ref, amount) {
  document.getElementById('verifyPayId').value = payId;
  document.getElementById('verifyRef').textContent = ref;
  document.getElementById('verifyAmount').textContent = amount;
  openModal('verifyModal');
}

function promptReject(payId, ref) {
  document.getElementById('rejectPayId').value = payId;
  document.getElementById('rejectRef').textContent = ref;
  openModal('rejectModal');
}
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
