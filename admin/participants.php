<?php
// ============================================================
// C Cube – Admin Participant Account Management
// ============================================================
$page_title = 'Manage Participants';
require_once __DIR__ . '/admin-header.php';

// Handle Account Status Change via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action  = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status  = $_POST['account_status'] ?? '';

    if ($user_id && in_array($status, ['active', 'inactive', 'banned'])) {
        $stmt = $pdo->prepare("SELECT full_name, email, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $target_user = $stmt->fetch();

        if ($target_user && $target_user['role'] === 'participant') {
            $stmt = $pdo->prepare("UPDATE users SET account_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $user_id]);

            audit_log('account_status_changed', 'users', $user_id, "Changed account status of {$target_user['full_name']} ({$target_user['email']}) to {$status}");
            redirect_with_message(BASE_URL . 'admin/participants.php', 'success', "Participant account status updated to: " . ucfirst($status));
        }
    }
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;

$where = ["u.role = 'participant'"];
$params = [];

if ($search !== '') {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR u.institution LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($status !== '' && in_array($status, ['active', 'inactive', 'banned'])) {
    $where[] = "u.account_status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

// Count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE $where_sql");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

// Fetch participants with registration count
$data_sql = "
    SELECT u.id, u.full_name, u.email, u.phone, u.institution, u.account_status, u.created_at,
           (SELECT COUNT(*) FROM registrations r WHERE r.user_id = u.id) AS reg_count,
           (SELECT COUNT(*) FROM registrations r WHERE r.user_id = u.id AND r.registration_status = 'approved') AS approved_count
    FROM users u
    WHERE $where_sql
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$participants = $stmt->fetchAll();
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">Participant Accounts</h1>
    <p class="dashboard-subtitle">Monitor registered users, inspect registration history, and manage account statuses</p>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="dash-card mb-4" style="padding:1.25rem">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center">
    <div style="flex:1;min-width:240px;position:relative">
      <input type="text" name="search" class="form-control" placeholder="Search participant name, email, institution..." value="<?= e($search) ?>" style="padding-left:2.5rem">
      <i class="fa fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
    </div>

    <div style="min-width:160px">
      <select name="status" class="form-control">
        <option value="">All Account Statuses</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm" style="height:42px">
      <i class="fa fa-filter"></i> Filter
    </button>
    <?php if ($search || $status): ?>
      <a href="<?= BASE_URL ?>admin/participants.php" class="btn btn-outline btn-sm" style="height:42px;display:flex;align-items:center">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Participants Table -->
<div class="dash-card">
  <?php if (empty($participants)): ?>
    <div style="text-align:center;padding:3.5rem 1rem">
      <i class="fa fa-users-slash" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;display:block"></i>
      <h3 style="color:var(--text-primary);font-size:1.15rem;font-weight:600">No participant accounts found</h3>
      <p style="color:var(--text-secondary);max-width:380px;margin:0.5rem auto 1.5rem">
        Try adjusting your filter search criteria.
      </p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Participant</th>
            <th>Contact & Institution</th>
            <th>Registrations</th>
            <th>Account Status</th>
            <th>Joined</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($participants as $pt): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                  <div class="sidebar-avatar" style="width:36px;height:36px;font-size:0.85rem">
                    <?= strtoupper(substr($pt['full_name'], 0, 1)) ?>
                  </div>
                  <div>
                    <strong style="color:var(--text-primary)"><?= e($pt['full_name']) ?></strong><br>
                    <span class="text-xs text-muted"><?= e($pt['email']) ?></span>
                  </div>
                </div>
              </td>
              <td>
                <div class="text-xs"><i class="fa fa-phone text-muted"></i> <?= e($pt['phone'] ?: 'No phone') ?></div>
                <div class="text-xs text-muted mt-1"><i class="fa fa-university"></i> <?= e($pt['institution'] ?: 'Unspecified') ?></div>
              </td>
              <td>
                <span style="font-weight:600;color:var(--text-primary)"><?= $pt['reg_count'] ?> registered</span><br>
                <span class="text-xs text-muted"><?= $pt['approved_count'] ?> approved</span>
              </td>
              <td>
                <?php if ($pt['account_status'] === 'active'): ?>
                  <span class="badge badge-success text-xs"><i class="fa fa-check"></i> Active</span>
                <?php elseif ($pt['account_status'] === 'inactive'): ?>
                  <span class="badge badge-warning text-xs"><i class="fa fa-pause"></i> Inactive</span>
                <?php else: ?>
                  <span class="badge badge-danger text-xs"><i class="fa fa-ban"></i> Banned</span>
                <?php endif; ?>
              </td>
              <td class="text-xs text-muted">
                <?= fmt_date($pt['created_at'], 'M j, Y') ?>
              </td>
              <td style="text-align:right">
                <div style="display:inline-flex;gap:0.4rem">
                  <!-- Status Toggle Dropdown / Form -->
                  <form method="POST" action="" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= $pt['id'] ?>">
                    <?php if ($pt['account_status'] === 'active'): ?>
                      <input type="hidden" name="account_status" value="banned">
                      <button type="submit" class="btn btn-outline btn-sm text-danger" title="Ban Account" style="border-color:rgba(239,68,68,0.3);padding:0.3rem 0.6rem;font-size:0.75rem" onclick="return confirm('Ban this participant? They will no longer be able to log in.')">
                        <i class="fa fa-ban"></i> Ban
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="account_status" value="active">
                      <button type="submit" class="btn btn-primary btn-sm" title="Activate Account" style="background:#10b981;border-color:#10b981;padding:0.3rem 0.6rem;font-size:0.75rem">
                        <i class="fa fa-check"></i> Unban
                      </button>
                    <?php endif; ?>
                  </form>
                  <a href="<?= BASE_URL ?>admin/registrations.php?search=<?= urlencode($pt['email']) ?>" class="btn btn-outline btn-sm" title="View Registrations" style="padding:0.3rem 0.6rem;font-size:0.75rem">
                    <i class="fa fa-list-alt"></i>
                  </a>
                </div>
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
          <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
