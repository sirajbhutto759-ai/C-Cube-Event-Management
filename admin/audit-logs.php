<?php
// ============================================================
// C Cube – Admin Audit Trail Logs
// ============================================================
$page_title = 'System Audit Logs';
require_once __DIR__ . '/admin-header.php';

// Search and Filter
$search = trim($_GET['search'] ?? '');
$action = trim($_GET['action'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$where  = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(a.action LIKE ? OR a.entity_type LIKE ? OR a.details LIKE ? OR a.ip_address LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($action !== '') {
    $where[] = "a.action = ?";
    $params[] = $action;
}

$where_sql = implode(' AND ', $where);

// Count
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM audit_logs a
     LEFT JOIN users u ON u.id = a.admin_user_id
     WHERE $where_sql"
);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

// Fetch logs
$data_sql = "
    SELECT a.*, u.full_name AS admin_name, u.email AS admin_email
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.admin_user_id
    WHERE $where_sql
    ORDER BY a.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distinct actions for filter
$distinct_actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">System Audit Trail</h1>
    <p class="dashboard-subtitle">Immutable chronological ledger of administrative decisions, approvals, and mutations</p>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="dash-card mb-4" style="padding:1.25rem">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center">
    <div style="flex:1;min-width:240px;position:relative">
      <input type="text" name="search" class="form-control" placeholder="Search action, details, admin, IP address..." value="<?= e($search) ?>" style="padding-left:2.5rem">
      <i class="fa fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
    </div>

    <div style="min-width:200px">
      <select name="action" class="form-control">
        <option value="">All Action Types</option>
        <?php foreach ($distinct_actions as $act): ?>
          <option value="<?= e($act) ?>" <?= $action === $act ? 'selected' : '' ?>>
            <?= e(ucwords(str_replace('_', ' ', $act))) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm" style="height:42px">
      <i class="fa fa-filter"></i> Filter Logs
    </button>
    <?php if ($search || $action): ?>
      <a href="<?= BASE_URL ?>admin/audit-logs.php" class="btn btn-outline btn-sm" style="height:42px;display:flex;align-items:center">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Audit Logs Table -->
<div class="dash-card">
  <?php if (empty($logs)): ?>
    <div style="text-align:center;padding:3.5rem 1rem">
      <i class="fa fa-history" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;display:block"></i>
      <h3 style="color:var(--text-primary);font-size:1.15rem;font-weight:600">No audit records found</h3>
      <p style="color:var(--text-secondary);max-width:380px;margin:0.5rem auto 1.5rem">
        There are no administrative actions matching your search parameters.
      </p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Action</th>
            <th>Entity</th>
            <th>Administrator</th>
            <th>Details & Narrative</th>
            <th>IP Address</th>
            <th>Timestamp</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <tr>
              <td>
                <span class="badge badge-info text-xs"><?= e($log['action']) ?></span>
              </td>
              <td class="text-xs">
                <?php if ($log['entity_type']): ?>
                  <span class="font-mono text-muted"><?= e($log['entity_type']) ?> #<?= (int)$log['entity_id'] ?></span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <strong style="color:var(--text-primary);font-size:0.875rem"><?= e($log['admin_name'] ?? 'System / Anonymous') ?></strong>
                <?php if ($log['admin_email']): ?>
                  <div class="text-xs text-muted"><?= e($log['admin_email']) ?></div>
                <?php endif; ?>
              </td>
              <td style="max-width:360px;font-size:0.85rem;color:var(--text-secondary)">
                <?= e($log['details'] ?: 'No details recorded') ?>
              </td>
              <td class="font-mono text-xs text-muted">
                <?= e($log['ip_address'] ?: '—') ?>
              </td>
              <td class="text-xs text-muted" style="white-space:nowrap">
                <?= fmt_date($log['created_at'], 'M j, Y g:i A') ?>
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
          <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
