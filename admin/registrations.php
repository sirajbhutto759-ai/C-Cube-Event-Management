<?php
// ============================================================
// C Cube – Admin Registrations Management
// ============================================================
$page_title = 'Manage Registrations';
require_once __DIR__ . '/admin-header.php';

// Handle Status Decision via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action   = $_POST['action'] ?? '';
    $reg_id   = (int)($_POST['registration_id'] ?? 0);
    $note     = trim($_POST['admin_note'] ?? '');

    // Fetch registration details
    $stmt = $pdo->prepare(
        "SELECT r.*, u.full_name, u.email, e.title AS event_title
         FROM registrations r
         JOIN users u ON u.id = r.user_id
         JOIN events e ON e.id = r.event_id
         WHERE r.id = ? LIMIT 1"
    );
    $stmt->execute([$reg_id]);
    $reg = $stmt->fetch();

    if ($reg) {
        if ($action === 'approve') {
            $stmt = $pdo->prepare(
                "UPDATE registrations SET
                    registration_status = 'approved',
                    admin_note = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$note ?: null, $admin_user['id'], $reg_id]);

            // Notify participant
            notify_user(
                $reg['user_id'],
                'Registration Approved!',
                "Congratulations! Your registration for \"{$reg['event_title']}\" (Ref: {$reg['registration_reference']}) has been approved." . ($note ? " Admin Note: {$note}" : '')
            );

            audit_log('registration_approved', 'registrations', $reg_id, "Approved registration {$reg['registration_reference']} for {$reg['full_name']}. Note: {$note}");
            redirect_with_message(BASE_URL . 'admin/registrations.php', 'success', "Registration {$reg['registration_reference']} has been approved!");
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare(
                "UPDATE registrations SET
                    registration_status = 'rejected',
                    admin_note = ?,
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$note ?: null, $admin_user['id'], $reg_id]);

            // Notify participant
            notify_user(
                $reg['user_id'],
                'Registration Status Update',
                "Your registration for \"{$reg['event_title']}\" (Ref: {$reg['registration_reference']}) was rejected." . ($note ? " Reason: {$note}" : '')
            );

            audit_log('registration_rejected', 'registrations', $reg_id, "Rejected registration {$reg['registration_reference']} for {$reg['full_name']}. Reason: {$note}");
            redirect_with_message(BASE_URL . 'admin/registrations.php', 'warning', "Registration {$reg['registration_reference']} marked as rejected.");
        }
    }
}

// Filters & Pagination
$search   = trim($_GET['search'] ?? '');
$event_id = (int)($_GET['event_id'] ?? 0);
$status   = trim($_GET['status'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;

$where  = ["1=1"];
$params = [];

if ($search !== '') {
    $where[] = "(r.registration_reference LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR r.team_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($event_id > 0) {
    $where[] = "r.event_id = ?";
    $params[] = $event_id;
}
if ($status !== '') {
    $where[] = "r.registration_status = ?";
    $params[] = $status;
}

$where_sql = implode(' AND ', $where);

// Count
$count_stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM registrations r
     JOIN users u ON u.id = r.user_id
     JOIN events e ON e.id = r.event_id
     WHERE $where_sql"
);
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = min($page, $pages);
$offset = ($page - 1) * $per_page;

// Fetch registrations
$data_sql = "
    SELECT r.*, u.full_name, u.email, u.phone, u.institution,
           e.title AS event_title, e.slug AS event_slug, e.registration_fee,
           p.payment_status, p.transaction_reference, p.amount AS paid_amount, p.id AS payment_id, p.receipt_path,
           rev.full_name AS reviewer_name
    FROM registrations r
    JOIN users u ON u.id = r.user_id
    JOIN events e ON e.id = r.event_id
    LEFT JOIN payments p ON p.registration_id = r.id
    LEFT JOIN users rev ON rev.id = r.reviewed_by
    WHERE $where_sql
    ORDER BY r.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($data_sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

// Team members map
$reg_ids = array_column($registrations, 'id');
$team_members = [];
if (!empty($reg_ids)) {
    $in_clause = implode(',', array_fill(0, count($reg_ids), '?'));
    $t_stmt = $pdo->prepare("SELECT * FROM team_members WHERE registration_id IN ($in_clause)");
    $t_stmt->execute($reg_ids);
    foreach ($t_stmt->fetchAll() as $tm) {
        $team_members[$tm['registration_id']][] = $tm;
    }
}

// Events for filter dropdown
$all_events = $pdo->query("SELECT id, title FROM events ORDER BY title ASC")->fetchAll();
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">Participant Registrations</h1>
    <p class="dashboard-subtitle">Review applications, inspect team rosters, and approve or reject participants</p>
  </div>
  <div>
    <a href="<?= BASE_URL ?>admin/reports.php" class="btn btn-secondary btn-sm">
      <i class="fa fa-file-export"></i> Export to CSV
    </a>
  </div>
</div>

<!-- Filters Toolbar -->
<div class="dash-card mb-4" style="padding:1.25rem">
  <form method="GET" action="" style="display:flex;flex-wrap:wrap;gap:0.75rem;align-items:center">
    <div style="flex:1;min-width:220px;position:relative">
      <input type="text" name="search" class="form-control" placeholder="Search reference, name, email, team..." value="<?= e($search) ?>" style="padding-left:2.5rem">
      <i class="fa fa-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--text-muted)"></i>
    </div>

    <div style="min-width:180px">
      <select name="event_id" class="form-control">
        <option value="">All Events</option>
        <?php foreach ($all_events as $ev): ?>
          <option value="<?= $ev['id'] ?>" <?= $event_id === (int)$ev['id'] ? 'selected' : '' ?>>
            <?= e($ev['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="min-width:160px">
      <select name="status" class="form-control">
        <option value="">All Statuses</option>
        <option value="pending_payment" <?= $status === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
        <option value="pending_approval" <?= $status === 'pending_approval' ? 'selected' : '' ?>>Pending Approval</option>
        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>

    <button type="submit" class="btn btn-secondary btn-sm" style="height:42px">
      <i class="fa fa-filter"></i> Filter
    </button>
    <?php if ($search || $event_id || $status): ?>
      <a href="<?= BASE_URL ?>admin/registrations.php" class="btn btn-outline btn-sm" style="height:42px;display:flex;align-items:center">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Registrations Table -->
<div class="dash-card">
  <?php if (empty($registrations)): ?>
    <div style="text-align:center;padding:3.5rem 1rem">
      <i class="fa fa-clipboard-list" style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;display:block"></i>
      <h3 style="color:var(--text-primary);font-size:1.15rem;font-weight:600">No registrations found</h3>
      <p style="color:var(--text-secondary);max-width:380px;margin:0.5rem auto 1.5rem">
        Try adjusting your filter search criteria.
      </p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Reference</th>
            <th>Participant</th>
            <th>Competition</th>
            <th>Type</th>
            <th>Payment</th>
            <th>Reg Status</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($registrations as $reg): ?>
            <tr>
              <td>
                <strong class="font-mono" style="color:var(--text-primary);font-size:0.85rem"><?= e($reg['registration_reference']) ?></strong>
                <div class="text-xs text-muted"><?= fmt_date($reg['created_at'], 'M j, Y') ?></div>
              </td>
              <td>
                <strong style="color:var(--text-primary)"><?= e($reg['full_name']) ?></strong><br>
                <span class="text-xs text-muted"><?= e($reg['email']) ?></span>
                <?php if ($reg['phone']): ?>
                  <span class="text-xs text-muted"> • <?= e($reg['phone']) ?></span>
                <?php endif; ?>
                <?php if ($reg['institution']): ?>
                  <div class="text-xs text-muted"><?= e($reg['institution']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span style="color:var(--text-primary);font-weight:500"><?= e($reg['event_title']) ?></span><br>
                <span class="text-xs" style="color:#34d399;font-weight:600"><?= fmt_money($reg['registration_fee']) ?></span>
              </td>
              <td>
                <span class="badge badge-info text-xs"><?= ucfirst($reg['participation_type']) ?></span>
                <?php if ($reg['participation_type'] === 'team'): ?>
                  <div class="text-xs text-muted mt-1" style="font-weight:600">
                    <?= e($reg['team_name'] ?: 'Team') ?>
                  </div>
                  <?php $t_count = count($team_members[$reg['id']] ?? []); ?>
                  <span class="text-xs text-muted">(<?= $t_count ?> members)</span>
                <?php endif; ?>
              </td>
              <td>
                <?= status_badge($reg['payment_status'] ?? 'not_submitted') ?>
                <?php if ($reg['transaction_reference']): ?>
                  <div class="font-mono text-xs text-muted mt-1"><?= e($reg['transaction_reference']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?= status_badge($reg['registration_status']) ?>
                <?php if ($reg['admin_note']): ?>
                  <div class="text-xs text-muted mt-1" title="<?= e($reg['admin_note']) ?>" style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <i class="fa fa-comment"></i> <?= e($reg['admin_note']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td style="text-align:right">
                <div style="display:inline-flex;gap:0.4rem">
                  <button type="button" class="btn btn-outline btn-sm" onclick='viewDetails(<?= json_encode([
                    "ref" => $reg["registration_reference"],
                    "user" => $reg["full_name"],
                    "email" => $reg["email"],
                    "phone" => $reg["phone"],
                    "inst" => $reg["institution"],
                    "event" => $reg["event_title"],
                    "fee" => fmt_money($reg["registration_fee"]),
                    "type" => $reg["participation_type"],
                    "team_name" => $reg["team_name"],
                    "members" => $team_members[$reg["id"]] ?? [],
                    "additional_info" => $reg["additional_info"],
                    "status" => $reg["registration_status"],
                    "payment_status" => $reg["payment_status"] ?? "not_submitted",
                    "tx_ref" => $reg["transaction_reference"],
                    "admin_note" => $reg["admin_note"],
                    "reviewer" => $reg["reviewer_name"],
                    "date" => fmt_date($reg["created_at"], "M j, Y g:i A")
                  ]) ?>)' title="View Full Details" style="padding:0.3rem 0.6rem;font-size:0.75rem">
                    <i class="fa fa-info-circle"></i>
                  </button>

                  <?php if ($reg['registration_status'] !== 'approved'): ?>
                    <button type="button" class="btn btn-primary btn-sm" onclick="promptAction('approve', <?= $reg['id'] ?>, '<?= e(addslashes($reg['registration_reference'])) ?>')" title="Approve Registration" style="padding:0.3rem 0.6rem;font-size:0.75rem;background:#10b981;border-color:#10b981">
                      <i class="fa fa-check"></i>
                    </button>
                  <?php endif; ?>

                  <?php if ($reg['registration_status'] !== 'rejected'): ?>
                    <button type="button" class="btn btn-outline btn-sm text-danger" onclick="promptAction('reject', <?= $reg['id'] ?>, '<?= e(addslashes($reg['registration_reference'])) ?>')" title="Reject Registration" style="padding:0.3rem 0.6rem;font-size:0.75rem;border-color:rgba(239,68,68,0.3)">
                      <i class="fa fa-times"></i>
                    </button>
                  <?php endif; ?>
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
          <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&event_id=<?= $event_id ?>&status=<?= urlencode($status) ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<!-- Details Modal -->
<div class="modal-backdrop" id="detailsModal">
  <div class="modal-box" style="max-width:620px">
    <div class="modal-header">
      <div class="modal-title"><i class="fa fa-clipboard-check text-accent"></i> Registration Dossier: <span id="mRef" class="font-mono text-accent"></span></div>
      <button class="modal-close" onclick="closeModal('detailsModal')">&times;</button>
    </div>
    <div id="modalBody" style="font-size:0.875rem;color:var(--text-secondary);display:flex;flex-direction:column;gap:1rem">
      <!-- Populated dynamically via JS -->
    </div>
  </div>
</div>

<!-- Action Modal (Approve / Reject) -->
<div class="modal-backdrop" id="actionModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title" id="actionTitle">Decision</div>
      <button class="modal-close" onclick="closeModal('actionModal')">&times;</button>
    </div>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="actionType" value="">
      <input type="hidden" name="registration_id" id="actionRegId" value="">

      <p id="actionDesc" style="color:var(--text-secondary);margin-bottom:1rem"></p>

      <div class="form-group mb-4">
        <label class="form-label" for="admin_note">Admin Note / Feedback (Optional for approval, recommended for rejection)</label>
        <textarea id="admin_note" name="admin_note" class="form-control" rows="3" placeholder="Enter optional internal note or reason communicated to the participant"></textarea>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:0.75rem">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('actionModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="actionBtnSubmit">Confirm Decision</button>
      </div>
    </form>
  </div>
</div>

<script>
function promptAction(type, regId, ref) {
  document.getElementById('actionType').value = type;
  document.getElementById('actionRegId').value = regId;
  const titleEl = document.getElementById('actionTitle');
  const descEl = document.getElementById('actionDesc');
  const btnEl = document.getElementById('actionBtnSubmit');

  if (type === 'approve') {
    titleEl.textContent = 'Approve Registration';
    titleEl.className = 'modal-title text-success';
    descEl.innerHTML = `Are you sure you want to approve registration <strong style="color:#fff">${ref}</strong>? The participant will be notified immediately.`;
    btnEl.className = 'btn btn-primary btn-sm';
    btnEl.style.background = '#10b981';
    btnEl.style.borderColor = '#10b981';
    btnEl.textContent = 'Approve Registration';
  } else {
    titleEl.textContent = 'Reject Registration';
    titleEl.className = 'modal-title text-danger';
    descEl.innerHTML = `Are you sure you want to reject registration <strong style="color:#fff">${ref}</strong>?`;
    btnEl.className = 'btn btn-danger btn-sm';
    btnEl.style.background = '#dc2626';
    btnEl.style.borderColor = '#dc2626';
    btnEl.textContent = 'Reject Registration';
  }
  openModal('actionModal');
}

function viewDetails(data) {
  document.getElementById('mRef').textContent = data.ref;
  let membersHtml = '';
  if (data.type === 'team' && data.members && data.members.length > 0) {
    membersHtml = `
      <div style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);padding:0.75rem;border-radius:6px;margin-top:0.5rem">
        <strong style="color:#fff;display:block;margin-bottom:0.35rem">Team Members (${data.team_name || 'Team'}):</strong>
        <ul style="padding-left:1.25rem;margin:0">
          ${data.members.map(m => `<li><strong>${m.member_name}</strong> - ${m.member_email || 'No email'} (${m.member_institution || 'N/A'})</li>`).join('')}
        </ul>
      </div>
    `;
  }

  document.getElementById('modalBody').innerHTML = `
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
      <div><span class="text-muted">Participant:</span> <strong style="color:#fff">${data.user}</strong></div>
      <div><span class="text-muted">Email:</span> ${data.email}</div>
      <div><span class="text-muted">Phone:</span> ${data.phone || '—'}</div>
      <div><span class="text-muted">Institution:</span> ${data.inst || '—'}</div>
      <div><span class="text-muted">Competition:</span> <strong style="color:#fff">${data.event}</strong></div>
      <div><span class="text-muted">Fee:</span> ${data.fee}</div>
      <div><span class="text-muted">Participation:</span> ${data.type.toUpperCase()}</div>
      <div><span class="text-muted">Submitted At:</span> ${data.date}</div>
      <div><span class="text-muted">Payment Status:</span> <span class="badge badge-info">${data.payment_status}</span></div>
      <div><span class="text-muted">Tx Reference:</span> ${data.tx_ref || 'None'}</div>
    </div>
    ${membersHtml}
    ${data.additional_info ? `<div class="mt-2"><span class="text-muted">Additional Information:</span><div style="background:rgba(255,255,255,0.03);padding:0.5rem;border-radius:4px;color:#fff">${data.additional_info}</div></div>` : ''}
    ${data.admin_note ? `<div class="mt-2"><span class="text-muted">Admin Note:</span><div style="background:rgba(99,102,241,0.08);padding:0.5rem;border-radius:4px;color:#fff">${data.admin_note}</div></div>` : ''}
  `;
  openModal('detailsModal');
}
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
