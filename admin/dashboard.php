<?php
// ============================================================
// C Cube – Admin Dashboard Overview
// ============================================================
$page_title = 'Dashboard Overview';
require_once __DIR__ . '/admin-header.php';

// Fetch Live Statistics directly from MySQL
$s = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'participant'");
$stat_participants = (int)$s->fetchColumn();

$s = $pdo->query("SELECT COUNT(*) FROM events");
$stat_events = (int)$s->fetchColumn();

$s = $pdo->query("SELECT COUNT(*) FROM registrations");
$stat_registrations = (int)$s->fetchColumn();

$s = $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'");
$stat_pending_payments = (int)$s->fetchColumn();

$s = $pdo->query("SELECT COUNT(*) FROM registrations WHERE registration_status IN ('pending_payment', 'pending_approval')");
$stat_pending_registrations = (int)$s->fetchColumn();

$s = $pdo->query("SELECT COUNT(*) FROM registrations WHERE registration_status = 'approved'");
$stat_approved = (int)$s->fetchColumn();

// Total verified revenue
$s = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'verified'");
$stat_revenue = (float)$s->fetchColumn();

// Recent Registrations (latest 6)
$stmt = $pdo->query(
    "SELECT r.*, u.full_name, u.email, e.title AS event_title, e.slug AS event_slug, p.payment_status
     FROM registrations r
     JOIN users u ON u.id = r.user_id
     JOIN events e ON e.id = r.event_id
     LEFT JOIN payments p ON p.registration_id = r.id
     ORDER BY r.created_at DESC
     LIMIT 6"
);
$recent_registrations = $stmt->fetchAll();

// Pending Payments (awaiting verification, latest 5)
$stmt = $pdo->query(
    "SELECT p.*, r.registration_reference, u.full_name AS participant_name, u.email AS participant_email,
            e.title AS event_title, e.registration_fee
     FROM payments p
     JOIN registrations r ON r.id = p.registration_id
     JOIN users u ON u.id = r.user_id
     JOIN events e ON e.id = r.event_id
     WHERE p.payment_status = 'pending'
     ORDER BY p.submitted_at DESC
     LIMIT 5"
);
$pending_payments = $stmt->fetchAll();

// Recent Audit Logs (latest 5)
$stmt = $pdo->query(
    "SELECT a.*, u.full_name AS admin_name
     FROM audit_logs a
     LEFT JOIN users u ON u.id = a.admin_user_id
     ORDER BY a.created_at DESC
     LIMIT 5"
);
$recent_logs = $stmt->fetchAll();
?>

<div class="dashboard-header">
  <div>
    <h1 class="dashboard-title">System Dashboard</h1>
    <p class="dashboard-subtitle">Real-time overview of participants, events, payments, and system activity</p>
  </div>
  <div class="quick-actions">
    <a href="<?= BASE_URL ?>admin/event-create.php" class="btn btn-primary btn-sm">
      <i class="fa fa-plus-circle"></i> Create Event
    </a>
    <a href="<?= BASE_URL ?>admin/payments.php" class="btn btn-outline btn-sm">
      <i class="fa fa-check-circle"></i> Verify Payments
    </a>
    <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-secondary btn-sm">
      <i class="fa fa-file-export"></i> Export CSV
    </a>
  </div>
</div>

<!-- Key Stat Cards -->
<div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:1.25rem;margin-bottom:2rem">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,0.15);color:var(--clr-primary-2)">
      <i class="fa fa-users"></i>
    </div>
    <div class="stat-value" data-counter="<?= $stat_participants ?>"><?= $stat_participants ?></div>
    <div class="stat-label">Total Participants</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(6,182,212,0.15);color:#22d3ee">
      <i class="fa fa-trophy"></i>
    </div>
    <div class="stat-value" data-counter="<?= $stat_events ?>"><?= $stat_events ?></div>
    <div class="stat-label">Competitions & Events</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(168,85,247,0.15);color:#c084fc">
      <i class="fa fa-clipboard-check"></i>
    </div>
    <div class="stat-value" data-counter="<?= $stat_registrations ?>"><?= $stat_registrations ?></div>
    <div class="stat-label">Total Registrations</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(239,68,68,0.15);color:#f87171">
      <i class="fa fa-money-bill-wave"></i>
    </div>
    <div class="stat-value" data-counter="<?= $stat_pending_payments ?>"><?= $stat_pending_payments ?></div>
    <div class="stat-label">Pending Payments</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:#fbbf24">
      <i class="fa fa-user-clock"></i>
    </div>
    <div class="stat-value" data-counter="<?= $stat_pending_registrations ?>"><?= $stat_pending_registrations ?></div>
    <div class="stat-label">Pending Approvals</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:#34d399">
      <i class="fa fa-coins"></i>
    </div>
    <div class="stat-value"><?= fmt_money($stat_revenue) ?></div>
    <div class="stat-label">Verified Revenue</div>
  </div>
</div>

<!-- Two-Column Layout for Tables -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(420px,1fr));gap:1.5rem;margin-bottom:2rem">

  <!-- Pending Payments Review Section -->
  <div class="dash-card">
    <div class="dash-card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <div class="dash-card-title"><i class="fa fa-clock text-accent"></i> Payments Awaiting Verification</div>
      <a href="<?= BASE_URL ?>admin/payments.php" class="btn btn-outline btn-sm text-xs">View All</a>
    </div>

    <?php if (empty($pending_payments)): ?>
      <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted)">
        <i class="fa fa-check-circle" style="font-size:2rem;color:#10b981;margin-bottom:0.5rem;display:block"></i>
        All payment submissions have been reviewed!
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Participant</th>
              <th>Event</th>
              <th>Amount</th>
              <th>Tx Ref</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending_payments as $pay): ?>
              <tr>
                <td>
                  <strong style="color:var(--text-primary)"><?= e($pay['participant_name']) ?></strong><br>
                  <span class="text-xs text-muted"><?= e($pay['participant_email']) ?></span>
                </td>
                <td>
                  <span class="text-xs"><?= e($pay['event_title']) ?></span><br>
                  <span class="text-xs text-muted font-mono"><?= e($pay['registration_reference']) ?></span>
                </td>
                <td style="font-weight:600;color:#34d399">
                  <?= fmt_money($pay['amount']) ?>
                </td>
                <td>
                  <span class="font-mono text-xs" style="background:rgba(255,255,255,0.06);padding:0.15rem 0.4rem;border-radius:4px">
                    <?= e($pay['transaction_reference']) ?>
                  </span>
                  <?php if (!empty($pay['receipt_path'])): ?>
                    <br><a href="<?= BASE_URL ?>view-receipt.php?payment_id=<?= $pay['id'] ?>" target="_blank" class="text-xs" style="color:var(--accent-indigo);display:inline-flex;align-items:center;gap:0.25rem;margin-top:0.25rem">
                      <i class="fa fa-paperclip"></i> Receipt
                    </a>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>admin/payments.php?filter=pending" class="btn btn-primary btn-sm" style="padding:0.3rem 0.6rem;font-size:0.75rem">
                    Review
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- Recent Registrations Section -->
  <div class="dash-card">
    <div class="dash-card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
      <div class="dash-card-title"><i class="fa fa-list-alt text-accent"></i> Recent Registrations</div>
      <a href="<?= BASE_URL ?>admin/registrations.php" class="btn btn-outline btn-sm text-xs">View All</a>
    </div>

    <?php if (empty($recent_registrations)): ?>
      <div style="text-align:center;padding:2.5rem 1rem;color:var(--text-muted)">
        <i class="fa fa-info-circle" style="font-size:2rem;margin-bottom:0.5rem;display:block"></i>
        No registrations submitted yet.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Reference / User</th>
              <th>Competition</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_registrations as $reg): ?>
              <tr>
                <td>
                  <strong class="font-mono text-xs" style="color:var(--text-primary)"><?= e($reg['registration_reference']) ?></strong><br>
                  <span class="text-xs text-muted"><?= e($reg['full_name']) ?></span>
                </td>
                <td>
                  <span class="text-xs"><?= e($reg['event_title']) ?></span><br>
                  <span class="text-xs text-muted"><?= ucfirst($reg['participation_type']) ?></span>
                </td>
                <td>
                  <?= status_badge($reg['registration_status']) ?>
                </td>
                <td class="text-xs text-muted">
                  <?= fmt_date($reg['created_at'], 'M j, g:i A') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- Recent System Audit Log -->
<div class="dash-card">
  <div class="dash-card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <div class="dash-card-title"><i class="fa fa-history text-accent"></i> Recent Administrative Audit Logs</div>
    <a href="<?= BASE_URL ?>admin/audit-logs.php" class="btn btn-outline btn-sm text-xs">Full Audit Trail</a>
  </div>

  <?php if (empty($recent_logs)): ?>
    <div style="padding:1rem;color:var(--text-muted);font-size:0.875rem">No administrative actions logged yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Action</th>
            <th>Entity</th>
            <th>Administrator</th>
            <th>Details</th>
            <th>IP Address</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_logs as $log): ?>
            <tr>
              <td>
                <span class="badge badge-info text-xs"><?= e($log['action']) ?></span>
              </td>
              <td class="text-xs">
                <?= e($log['entity_type'] ? $log['entity_type'] . ' #' . $log['entity_id'] : '—') ?>
              </td>
              <td class="text-xs" style="color:var(--text-primary);font-weight:500">
                <?= e($log['admin_name'] ?? 'System') ?>
              </td>
              <td class="text-xs" style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                <?= e($log['details'] ?? '—') ?>
              </td>
              <td class="text-xs font-mono text-muted">
                <?= e($log['ip_address'] ?? '—') ?>
              </td>
              <td class="text-xs text-muted">
                <?= fmt_date($log['created_at'], 'M j, Y g:i A') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
