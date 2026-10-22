<?php
// ============================================================
// C Cube – Reports & CSV Export
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
session_boot();
require_admin();
$pdo = db();

// Handle CSV Export Request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $event_id = (int)($_GET['event_id'] ?? 0);
    $status   = trim($_GET['status'] ?? '');

    $where = ["1=1"];
    $params = [];

    if ($event_id > 0) {
        $where[] = "r.event_id = ?";
        $params[] = $event_id;
    }
    if ($status !== '') {
        $where[] = "r.registration_status = ?";
        $params[] = $status;
    }

    $where_sql = implode(' AND ', $where);

    $sql = "
        SELECT r.registration_reference, e.title AS event_title, u.full_name, u.email, u.phone, u.institution,
               r.participation_type, r.team_name, r.registration_status,
               COALESCE(p.payment_status, 'not_submitted') AS payment_status,
               COALESCE(p.amount, 0.00) AS amount,
               COALESCE(p.transaction_reference, '') AS transaction_reference,
               r.created_at
        FROM registrations r
        JOIN users u ON u.id = r.user_id
        JOIN events e ON e.id = r.event_id
        LEFT JOIN payments p ON p.registration_id = r.id
        WHERE $where_sql
        ORDER BY r.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Stream CSV response
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ccube_registrations_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fputs($out, "\xEF\xBB\xBF");

    // CSV Header row
    fputcsv($out, [
        'Registration Reference',
        'Competition',
        'Participant Name',
        'Email Address',
        'Phone Number',
        'Institution',
        'Participation Type',
        'Team Name',
        'Registration Status',
        'Payment Status',
        'Paid Amount (PKR)',
        'Transaction Reference',
        'Registered At'
    ]);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['registration_reference'],
            $r['event_title'],
            $r['full_name'],
            $r['email'],
            $r['phone'] ?? '',
            $r['institution'] ?? '',
            ucfirst($r['participation_type']),
            $r['team_name'] ?? '',
            ucfirst(str_replace('_', ' ', $r['registration_status'])),
            ucfirst(str_replace('_', ' ', $r['payment_status'])),
            number_format($r['amount'], 2),
            $r['transaction_reference'],
            $r['created_at']
        ]);
    }

    fclose($out);
    audit_log('csv_exported', 'reports', 0, "Exported " . count($rows) . " registration records to CSV.");
    exit;
}

// Regular page rendering
$page_title = 'Reports & Data Export';
require_once __DIR__ . '/admin-header.php';

$event_id = (int)($_GET['event_id'] ?? 0);
$status   = trim($_GET['status'] ?? '');

$where = ["1=1"];
$params = [];

if ($event_id > 0) {
    $where[] = "r.event_id = ?";
    $params[] = $event_id;
}
if ($status !== '') {
    $where[] = "r.registration_status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

// Aggregated metrics for active filter
$metrics_sql = "
    SELECT
        COUNT(r.id) AS total_count,
        SUM(CASE WHEN r.registration_status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
        SUM(CASE WHEN r.registration_status IN ('pending_payment', 'pending_approval') THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN r.registration_status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
        COALESCE(SUM(CASE WHEN p.payment_status = 'verified' THEN p.amount ELSE 0 END), 0) AS total_revenue
    FROM registrations r
    LEFT JOIN payments p ON p.registration_id = r.id
    WHERE $where_sql
";
$stmt = $pdo->prepare($metrics_sql);
$stmt->execute($params);
$metrics = $stmt->fetch();

// Sample rows preview (top 10)
$preview_sql = "
    SELECT r.*, e.title AS event_title, u.full_name, u.email,
           p.payment_status, p.amount AS paid_amount
    FROM registrations r
    JOIN users u ON u.id = r.user_id
    JOIN events e ON e.id = r.event_id
    LEFT JOIN payments p ON p.registration_id = r.id
    WHERE $where_sql
    ORDER BY r.created_at DESC
    LIMIT 10
";
$stmt = $pdo->prepare($preview_sql);
$stmt->execute($params);
$preview_rows = $stmt->fetchAll();

// Events list for dropdown
$all_events = $pdo->query("SELECT id, title FROM events ORDER BY title ASC")->fetchAll();
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">Reports & Data Export</h1>
    <p class="dashboard-subtitle">Generate tabular summaries and export real participant registrations to CSV</p>
  </div>
  <div>
    <a href="?export=csv&event_id=<?= $event_id ?>&status=<?= urlencode($status) ?>" class="btn btn-primary">
      <i class="fa fa-download"></i> Download CSV Export
    </a>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="dash-card mb-4" style="padding:1.25rem">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center">
    <div style="flex:1;min-width:220px">
      <select name="event_id" class="form-control">
        <option value="">All Competitions & Events</option>
        <?php foreach ($all_events as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $event_id === (int)$ev['id'] ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width:180px">
      <select name="status" class="form-control">
        <option value="">All Registration Statuses</option>
        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="pending_approval" <?= $status === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
        <option value="pending_payment" <?= $status === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm" style="height:42px">
      <i class="fa fa-filter"></i> Apply Filters
    </button>
    <?php if ($event_id || $status): ?>
      <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-outline btn-sm" style="height:42px;display:flex;align-items:center">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Report Summary Metrics -->
<div class="stats-grid mb-4" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem">
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(99,102,241,0.15);color:var(--clr-primary-2)">
      <i class="fa fa-clipboard-list"></i>
    </div>
    <div class="stat-value"><?= (int)$metrics['total_count'] ?></div>
    <div class="stat-label">Matching Registrations</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(16,185,129,0.15);color:#34d399">
      <i class="fa fa-check-circle"></i>
    </div>
    <div class="stat-value"><?= (int)$metrics['approved_count'] ?></div>
    <div class="stat-label">Approved Participants</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(245,158,11,0.15);color:#fbbf24">
      <i class="fa fa-clock"></i>
    </div>
    <div class="stat-value"><?= (int)$metrics['pending_count'] ?></div>
    <div class="stat-label">Pending Reviews</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(6,182,212,0.15);color:#22d3ee">
      <i class="fa fa-coins"></i>
    </div>
    <div class="stat-value"><?= fmt_money($metrics['total_revenue']) ?></div>
    <div class="stat-label">Verified Revenue</div>
  </div>
</div>

<!-- Preview Table -->
<div class="dash-card">
  <div class="dash-card-header mb-3" style="display:flex;justify-content:space-between;align-items:center">
    <div class="dash-card-title"><i class="fa fa-table text-accent"></i> Dataset Preview (Top 10 of <?= (int)$metrics['total_count'] ?>)</div>
    <a href="?export=csv&event_id=<?= $event_id ?>&status=<?= urlencode($status) ?>" class="btn btn-outline btn-sm text-xs">
      <i class="fa fa-file-csv"></i> Download Full CSV
    </a>
  </div>

  <?php if (empty($preview_rows)): ?>
    <div style="padding:2.5rem;text-align:center;color:var(--text-muted)">
      No data available for the selected filter combination.
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Participant</th>
            <th>Competition</th>
            <th>Reg Status</th>
            <th>Payment Status</th>
            <th>Registered Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($preview_rows as $row): ?>
            <tr>
              <td class="font-mono text-xs" style="color:var(--text-primary)"><?= e($row['registration_reference']) ?></td>
              <td>
                <strong style="color:var(--text-primary)"><?= e($row['full_name']) ?></strong><br>
                <span class="text-xs text-muted"><?= e($row['email']) ?></span>
              </td>
              <td><?= e($row['event_title']) ?></td>
              <td><?= status_badge($row['registration_status']) ?></td>
              <td><?= status_badge($row['payment_status'] ?? 'not_submitted') ?></td>
              <td class="text-xs text-muted"><?= fmt_date($row['created_at'], 'M j, Y g:i A') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
