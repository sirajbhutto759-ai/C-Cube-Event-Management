<?php
// ============================================================
// C Cube – Edit Event
// ============================================================
$page_title = 'Edit Event';
require_once __DIR__ . '/admin-header.php';

$event_id = (int)($_GET['id'] ?? 0);
if (!$event_id) {
    redirect_with_message(BASE_URL . 'admin/events.php', 'danger', 'Invalid event ID.');
}

// Fetch event
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    redirect_with_message(BASE_URL . 'admin/events.php', 'danger', 'Event not found.');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title                 = trim($_POST['title'] ?? '');
    $slug                  = trim($_POST['slug'] ?? '');
    $category_id           = (int)($_POST['category_id'] ?? 0);
    $short_desc            = trim($_POST['short_description'] ?? '');
    $desc                  = trim($_POST['description'] ?? '');
    $event_date            = trim($_POST['event_date'] ?? '');
    $deadline              = trim($_POST['registration_deadline'] ?? '');
    $venue                 = trim($_POST['venue'] ?? '');
    $fee                   = (float)($_POST['registration_fee'] ?? 0);
    $capacity              = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;
    $min_team              = max(1, (int)($_POST['minimum_team_size'] ?? 1));
    $max_team              = max($min_team, (int)($_POST['maximum_team_size'] ?? 1));
    $eligibility           = trim($_POST['eligibility'] ?? '');
    $rules                 = trim($_POST['rules'] ?? '');
    $prizes                = trim($_POST['prize_information'] ?? '');
    $status                = in_array($_POST['status'] ?? '', ['draft','published','closed','cancelled']) ? $_POST['status'] : 'draft';

    // Validation
    if (!$title) {
        $errors[] = 'Event title is required.';
    }
    if (!$slug) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }

    // Check slug uniqueness excluding this event
    $stmt = $pdo->prepare("SELECT id FROM events WHERE slug = ? AND id != ? LIMIT 1");
    $stmt->execute([$slug, $event_id]);
    if ($stmt->fetch()) {
        $slug .= '-' . rand(100, 999);
    }

    if ($min_team > $max_team) {
        $errors[] = 'Minimum team size cannot be greater than maximum team size.';
    }

    // Banner file upload
    $banner_path = $event['banner_path'];
    if (!empty($_FILES['banner']['name'])) {
        try {
            $banner_path = upload_event_banner($_FILES['banner']);
        } catch (RuntimeException $e) {
            $errors[] = 'Banner upload failed: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            $sql = "UPDATE events SET
                category_id           = ?,
                title                 = ?,
                slug                  = ?,
                short_description     = ?,
                description           = ?,
                banner_path           = ?,
                event_date            = ?,
                registration_deadline = ?,
                venue                 = ?,
                registration_fee      = ?,
                capacity              = ?,
                minimum_team_size     = ?,
                maximum_team_size     = ?,
                eligibility           = ?,
                rules                 = ?,
                prize_information     = ?,
                status                = ?
                WHERE id = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $category_id ?: null,
                $title,
                $slug,
                $short_desc ?: null,
                $desc ?: null,
                $banner_path,
                $event_date ?: null,
                $deadline ?: null,
                $venue ?: null,
                $fee,
                $capacity,
                $min_team,
                $max_team,
                $eligibility ?: null,
                $rules ?: null,
                $prizes ?: null,
                $status,
                $event_id
            ]);

            audit_log('event_updated', 'events', $event_id, "Updated event: {$title} (Status: {$status})");
            redirect_with_message(BASE_URL . 'admin/events.php', 'success', "Event '{$title}' updated successfully!");
        } catch (Throwable $e) {
            error_log('[CCube Event Edit] ' . $e->getMessage());
            $errors[] = 'Failed to update event. Database error.';
        }
    }

    // Update current object in memory for form display
    $event = array_merge($event, [
        'title'                 => $title,
        'slug'                  => $slug,
        'category_id'           => $category_id,
        'short_description'     => $short_desc,
        'description'           => $desc,
        'event_date'            => $event_date,
        'registration_deadline' => $deadline,
        'venue'                 => $venue,
        'registration_fee'      => $fee,
        'capacity'              => $capacity,
        'minimum_team_size'     => $min_team,
        'maximum_team_size'     => $max_team,
        'eligibility'           => $eligibility,
        'rules'                 => $rules,
        'prize_information'     => $prizes,
        'status'                => $status,
    ]);
}

// Categories list
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="dashboard-header">
  <div>
    <h1 class="dashboard-title">Edit Event: <?= e($event['title']) ?></h1>
    <p class="dashboard-subtitle">Update competition specifications, deadlines, fees, or publication status</p>
  </div>
  <div style="display:flex;gap:0.75rem">
    <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($event['slug']) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="fa fa-external-link-alt"></i> View Public Page
    </a>
    <a href="<?= BASE_URL ?>admin/events.php" class="btn btn-outline btn-sm">
      <i class="fa fa-arrow-left"></i> Back to Events
    </a>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert-danger mb-4">
    <i class="fa fa-exclamation-circle"></i>
    <div>
      <?php foreach ($errors as $err): ?>
        <div><?= e($err) ?></div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start">
    
    <!-- Left Column: Core Event Information -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">
      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-info-circle text-accent"></i> Basic Information</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="title">Event Title <span class="text-danger">*</span></label>
          <input type="text" id="title" name="title" class="form-control" value="<?= e($event['title']) ?>" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="slug">URL Slug</label>
          <input type="text" id="slug" name="slug" class="form-control" value="<?= e($event['slug']) ?>">
          <small class="text-muted text-xs">Used in URL: <?= BASE_URL ?>event-details.php?slug=<strong><?= e($event['slug']) ?></strong></small>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="short_description">Short Summary</label>
          <textarea id="short_description" name="short_description" class="form-control" rows="2"><?= e($event['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="description">Full Description</label>
          <textarea id="description" name="description" class="form-control" rows="7"><?= e($event['description'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-list-check text-accent"></i> Rules, Eligibility & Prizes</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="eligibility">Eligibility Criteria</label>
          <textarea id="eligibility" name="eligibility" class="form-control" rows="3"><?= e($event['eligibility'] ?? '') ?></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="rules">Competition Rules</label>
          <textarea id="rules" name="rules" class="form-control" rows="4"><?= e($event['rules'] ?? '') ?></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="prize_information">Prize Pool & Awards</label>
          <textarea id="prize_information" name="prize_information" class="form-control" rows="3"><?= e($event['prize_information'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Right Column: Settings & Meta -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">
      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-sliders-h text-accent"></i> Event Configuration</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="status">Publication Status <span class="text-danger">*</span></label>
          <select id="status" name="status" class="form-control" required>
            <option value="published" <?= $event['status']==='published'?'selected':'' ?>>Published (Live)</option>
            <option value="draft" <?= $event['status']==='draft'?'selected':'' ?>>Draft (Hidden)</option>
            <option value="closed" <?= $event['status']==='closed'?'selected':'' ?>>Closed (No registrations)</option>
            <option value="cancelled" <?= $event['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="category_id">Category</label>
          <select id="category_id" name="category_id" class="form-control">
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ((int)$event['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="banner">Event Banner Image</label>
          <?php if (!empty($event['banner_path'])): ?>
            <div style="margin-bottom:0.75rem">
              <img src="<?= BASE_URL . e($event['banner_path']) ?>" alt="" style="width:100%;max-height:120px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid rgba(255,255,255,0.1)">
            </div>
          <?php endif; ?>
          <input type="file" id="banner" name="banner" class="form-control" accept="image/jpeg,image/png,image/webp">
          <small class="text-muted text-xs">Leave blank to keep existing banner.</small>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="venue">Venue / Location</label>
          <input type="text" id="venue" name="venue" class="form-control" value="<?= e($event['venue'] ?? '') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="event_date">Event Date & Time</label>
          <input type="datetime-local" id="event_date" name="event_date" class="form-control" value="<?= !empty($event['event_date']) ? date('Y-m-d\TH:i', strtotime($event['event_date'])) : '' ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="registration_deadline">Registration Deadline</label>
          <input type="datetime-local" id="registration_deadline" name="registration_deadline" class="form-control" value="<?= !empty($event['registration_deadline']) ? date('Y-m-d\TH:i', strtotime($event['registration_deadline'])) : '' ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="registration_fee">Registration Fee (PKR)</label>
          <input type="number" step="0.01" min="0" id="registration_fee" name="registration_fee" class="form-control" value="<?= e($event['registration_fee']) ?>" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="capacity">Participant Capacity</label>
          <input type="number" min="1" id="capacity" name="capacity" class="form-control" value="<?= e($event['capacity'] ?? '') ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem" class="mb-4">
          <div class="form-group">
            <label class="form-label text-xs" for="minimum_team_size">Min Team Size</label>
            <input type="number" min="1" max="20" id="minimum_team_size" name="minimum_team_size" class="form-control" value="<?= e($event['minimum_team_size']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label text-xs" for="maximum_team_size">Max Team Size</label>
            <input type="number" min="1" max="20" id="maximum_team_size" name="maximum_team_size" class="form-control" value="<?= e($event['maximum_team_size']) ?>" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">
          <i class="fa fa-save"></i> Save Changes
        </button>
      </div>
    </div>

  </div>
</form>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
