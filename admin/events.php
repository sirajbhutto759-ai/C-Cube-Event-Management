<?php
// ============================================================
// C Cube – Admin Events Management
// ============================================================
$page_title = 'Manage Events';
require_once __DIR__ . '/admin-header.php';

// Handle Delete Action via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    csrf_verify();
    $event_id = (int)($_POST['event_id'] ?? 0);

    // Get event title for audit
    $stmt = $pdo->prepare("SELECT title FROM events WHERE id=?");
    $stmt->execute([$event_id]);
    $ev_title = $stmt->fetchColumn();

    if ($ev_title) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id=?");
        $stmt->execute([$event_id]);

        audit_log('event_deleted', 'events', $event_id, "Deleted event: {$ev_title}");
        redirect_with_message(BASE_URL . 'admin/events.php', 'success', "Event '{$ev_title}' has been deleted.");
    } else {
        redirect_with_message(BASE_URL . 'admin/events.php', 'danger', "Event not found.");
    }
}

// Filters and Search
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

$where = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(e.title LIKE ? OR e.short_description LIKE ? OR e.venue LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category !== '') {
    $where[] = "c.slug = ?";
    $params[] = $category;
}
if ($status !== '') {
    $where[] = "e.status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

// Count
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM events e
     LEFT JOIN categories c ON c.id = e.category_id
     WHERE $where_sql"
);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

// Fetch events with registration counts
$data_sql = "
    SELECT e.*, c.name AS category_name, c.slug AS category_slug,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_count,
           (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id AND r.registration_status = 'approved') AS approved_count
    FROM events e
    LEFT JOIN categories c ON c.id = e.category_id
    WHERE $where_sql
    ORDER BY e.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Fetch categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">Competitions & Events</h1>
    <p class="dashboard-subtitle">Create, publish, edit, and manage all campus competitions</p>
  </div>
  <div>
    <a href="<?= BASE_URL ?>admin/event-create.php" class="btn btn-primary">
      <i class="fa fa-plus-circle"></i> Create New Event
    </a>
  </div>
</div>

<!-- Search & Filters Toolbar -->
<div class="dash-card mb-4" style="padding:1.25rem">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center">
    <div style="flex:1;min-width:220px;position:relative">
      <input type="text" name="search" class="form-control" placeholder="Search event title, venue..." value="<?= e($search) ?>" style="padding-left:2.5rem">
      <i class="fa fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
    </div>

    <div style="min-width:160px">
      <select name="category" class="form-control">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e($cat['slug']) ?>" <?= $category === $cat['slug'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width:140px">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm" style="height:42px">
      <i class="fa fa-filter"></i> Filter
    </button>
    <?php if ($search || $category || $status): ?>
      <a href="<?= BASE_URL ?>admin/events.php" class="btn btn-outline btn-sm" style="height:42px;display:flex;align-items:center">
        Reset
      </a>
    <?php endif; ?>
  </form>
</div>

<!-- Events Table -->
<div class="dash-card">
  <?php if (empty($events)): ?>
    <div style="text-align:center;padding:3.5rem 1rem">
      <i class="fa fa-calendar-times" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;display:block"></i>
      <h3 style="color:var(--text-primary);font-size:1.15rem;font-weight:600">No events found</h3>
      <p style="color:var(--text-secondary);max-width:380px;margin:0.5rem auto 1.5rem">
        Try adjusting your filter parameters or create a new event.
      </p>
      <a href="<?= BASE_URL ?>admin/event-create.php" class="btn btn-primary btn-sm"><i class="fa fa-plus-circle"></i> Create Event</a>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Event</th>
            <th>Category</th>
            <th>Schedule</th>
            <th>Fee</th>
            <th>Capacity & Regs</th>
            <th>Status</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $ev): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:0.75rem">
                  <?php if (!empty($ev['banner_path'])): ?>
                    <img src="<?= BASE_URL . e($ev['banner_path']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid rgba(255,255,255,0.1)">
                  <?php else: ?>
                    <div style="width:48px;height:48px;border-radius:var(--radius-sm);background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;color:var(--clr-primary-2)">
                      <i class="fa fa-trophy"></i>
                    </div>
                  <?php endif; ?>
                  <div>
                    <strong style="color:var(--text-primary);font-size:0.95rem"><?= e($ev['title']) ?></strong>
                    <div class="text-xs text-muted" style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                      <?= e($ev['short_description'] ?? 'No description') ?>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span class="badge badge-info text-xs"><?= e($ev['category_name'] ?? 'Uncategorized') ?></span>
              </td>
              <td>
                <span class="text-xs" style="color:var(--text-primary)"><i class="fa fa-calendar-alt text-muted"></i> <?= fmt_date($ev['event_date'], 'M j, Y') ?></span><br>
                <span class="text-xs text-muted"><i class="fa fa-hourglass-end"></i> Deadline: <?= fmt_date($ev['registration_deadline'], 'M j, Y') ?></span>
              </td>
              <td style="font-weight:600;color:#34d399">
                <?= fmt_money($ev['registration_fee']) ?>
              </td>
              <td>
                <span class="text-xs" style="font-weight:600;color:var(--text-primary)"><?= $ev['reg_count'] ?> Registered</span>
                <span class="text-xs text-muted">(<?= $ev['approved_count'] ?> Approved)</span>
                <?php if ($ev['capacity']): ?>
                  <div class="progress-bar-bg" style="width:100px;height:5px;background:rgba(255,255,255,0.1);border-radius:10px;margin-top:0.35rem;overflow:hidden">
                    <div style="height:100%;width:<?= min(100, round(($ev['reg_count'] / $ev['capacity']) * 100)) ?>%;background:var(--accent-gradient)"></div>
                  </div>
                  <span class="text-xs text-muted"><?= $ev['capacity'] ?> max</span>
                <?php endif; ?>
              </td>
              <td>
                <?= status_badge($ev['status']) ?>
              </td>
              <td style="text-align:right">
                <div style="display:inline-flex;gap:0.4rem">
                  <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($ev['slug']) ?>" target="_blank" class="btn-icon" title="View Public Page" style="width:32px;height:32px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#fff;border-radius:var(--radius-sm)">
                    <i class="fa fa-eye"></i>
                  </a>
                  <a href="<?= BASE_URL ?>admin/event-edit.php?id=<?= $ev['id'] ?>" class="btn-icon" title="Edit Event" style="width:32px;height:32px;background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);color:var(--clr-primary-2);border-radius:var(--radius-sm)">
                    <i class="fa fa-edit"></i>
                  </a>
                  <button type="button" class="btn-icon" title="Delete Event" onclick="confirmDelete(<?= $ev['id'] ?>, '<?= e(addslashes($ev['title'])) ?>')" style="width:32px;height:32px;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);color:#f87171;border-radius:var(--radius-sm);cursor:pointer">
                    <i class="fa fa-trash-alt"></i>
                  </button>
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
          <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-backdrop" id="deleteModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title text-danger"><i class="fa fa-exclamation-triangle"></i> Confirm Deletion</div>
      <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
    </div>
    <p style="color:var(--text-secondary);margin-bottom:1.25rem">
      Are you sure you want to delete <strong id="deleteEventTitle" style="color:#fff"></strong>?
      All associated participant registrations and payment submissions will also be deleted!
    </p>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete_event">
      <input type="hidden" name="event_id" id="deleteEventId" value="">
      <div style="display:flex;justify-content:flex-end;gap:0.75rem">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('deleteModal')">Cancel</button>
        <button type="submit" class="btn btn-danger btn-sm" style="background:#dc2626;color:#fff"><i class="fa fa-trash-alt"></i> Delete Permanently</button>
      </div>
    </form>
  </div>
</div>

<script>
function confirmDelete(id, title) {
  document.getElementById('deleteEventId').value = id;
  document.getElementById('deleteEventTitle').textContent = title;
  openModal('deleteModal');
}
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
