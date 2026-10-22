<?php
// ============================================================
// C Cube – Create Event
// ============================================================
$page_title = 'Create New Event';
require_once __DIR__ . '/admin-header.php';

$errors = [];
$form_data = [
    'category_id'           => '',
    'title'                 => '',
    'slug'                  => '',
    'short_description'     => '',
    'description'           => '',
    'event_date'            => '',
    'registration_deadline' => '',
    'venue'                 => '',
    'registration_fee'      => '0.00',
    'capacity'              => '',
    'minimum_team_size'     => 1,
    'maximum_team_size'     => 1,
    'eligibility'           => '',
    'rules'                 => '',
    'prize_information'     => '',
    'status'                => 'published',
];

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

    // Populate form data for redisplay on error
    $form_data = compact(
        'category_id','title','slug','short_desc','desc','event_date','deadline',
        'venue','fee','capacity','min_team','max_team','eligibility','rules','prizes','status'
    );
    $form_data['short_description'] = $short_desc;
    $form_data['registration_fee']  = $fee;

    // Validation
    if (!$title) {
        $errors[] = 'Event title is required.';
    }
    if (!$slug) {
        $slug = slugify($title);
    } else {
        $slug = slugify($slug);
    }

    // Check slug uniqueness
    $stmt = $pdo->prepare("SELECT id FROM events WHERE slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        $slug .= '-' . rand(100, 999);
    }

    if ($min_team > $max_team) {
        $errors[] = 'Minimum team size cannot be greater than maximum team size.';
    }

    // File Upload handling
    $banner_path = null;
    if (!empty($_FILES['banner']['name'])) {
        try {
            $banner_path = upload_event_banner($_FILES['banner']);
        } catch (RuntimeException $e) {
            $errors[] = 'Banner upload failed: ' . $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            $sql = "INSERT INTO events (
                category_id, title, slug, short_description, description, banner_path,
                event_date, registration_deadline, venue, registration_fee, capacity,
                minimum_team_size, maximum_team_size, eligibility, rules, prize_information, status
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

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
                $status
            ]);

            $new_id = (int)$pdo->lastInsertId();
            audit_log('event_created', 'events', $new_id, "Created competition: {$title} (Status: {$status})");

            redirect_with_message(BASE_URL . 'admin/events.php', 'success', "Competition '{$title}' has been successfully created!");
        } catch (Throwable $e) {
            error_log('[CCube Event Create] ' . $e->getMessage());
            $errors[] = 'Failed to create event. Database error occurred.';
        }
    }
}

// Categories list
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
?>

<div class="dashboard-header">
  <div>
    <h1 class="dashboard-title">Create New Event</h1>
    <p class="dashboard-subtitle">Configure event parameters, registration rules, team sizes, and fees</p>
  </div>
  <div>
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
          <input type="text" id="title" name="title" class="form-control" placeholder="e.g. CodeStorm 2025" value="<?= e($form_data['title']) ?>" required oninput="generateSlug(this.value)">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="slug">URL Slug</label>
          <input type="text" id="slug" name="slug" class="form-control" placeholder="e.g. codestorm-2025" value="<?= e($form_data['slug']) ?>">
          <small class="text-muted text-xs">Used in URL: <?= BASE_URL ?>event-details.php?slug=<strong><span id="slugPreview">codestorm-2025</span></strong></small>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="short_description">Short Summary</label>
          <textarea id="short_description" name="short_description" class="form-control" rows="2" placeholder="Brief 1–2 sentence overview for event cards"><?= e($form_data['short_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="description">Full Description</label>
          <textarea id="description" name="description" class="form-control" rows="7" placeholder="Detailed competition background, objectives, and schedule (HTML allowed)"><?= e($form_data['description'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-list-check text-accent"></i> Rules, Eligibility & Prizes</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="eligibility">Eligibility Criteria</label>
          <textarea id="eligibility" name="eligibility" class="form-control" rows="3" placeholder="Who is eligible? e.g. Open to all undergraduate university students"><?= e($form_data['eligibility'] ?? '') ?></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="rules">Competition Rules</label>
          <textarea id="rules" name="rules" class="form-control" rows="4" placeholder="Rule 1&#10;Rule 2&#10;Rule 3"><?= e($form_data['rules'] ?? '') ?></textarea>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="prize_information">Prize Pool & Awards</label>
          <textarea id="prize_information" name="prize_information" class="form-control" rows="3" placeholder="1st Place: PKR 50,000&#10;2nd Place: PKR 30,000"><?= e($form_data['prize_information'] ?? '') ?></textarea>
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
            <option value="published" <?= ($form_data['status']??'')==='published'?'selected':'' ?>>Published (Live)</option>
            <option value="draft" <?= ($form_data['status']??'')==='draft'?'selected':'' ?>>Draft (Hidden)</option>
            <option value="closed" <?= ($form_data['status']??'')==='closed'?'selected':'' ?>>Closed (No registrations)</option>
            <option value="cancelled" <?= ($form_data['status']??'')==='cancelled'?'selected':'' ?>>Cancelled</option>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="category_id">Category</label>
          <select id="category_id" name="category_id" class="form-control">
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ((int)($form_data['category_id']??0) === (int)$cat['id']) ? 'selected' : '' ?>>
                <?= e($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="banner">Event Banner Image</label>
          <input type="file" id="banner" name="banner" class="form-control" accept="image/jpeg,image/png,image/webp">
          <small class="text-muted text-xs">JPG, PNG, WebP (Max 5MB)</small>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="venue">Venue / Location</label>
          <input type="text" id="venue" name="venue" class="form-control" placeholder="e.g. Auditorium Hall B / Online" value="<?= e($form_data['venue'] ?? '') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="event_date">Event Date & Time</label>
          <input type="datetime-local" id="event_date" name="event_date" class="form-control" value="<?= e($form_data['event_date'] ?? '') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="registration_deadline">Registration Deadline</label>
          <input type="datetime-local" id="registration_deadline" name="registration_deadline" class="form-control" value="<?= e($form_data['registration_deadline'] ?? '') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="registration_fee">Registration Fee (PKR)</label>
          <input type="number" step="0.01" min="0" id="registration_fee" name="registration_fee" class="form-control" placeholder="0.00" value="<?= e($form_data['registration_fee']) ?>" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="capacity">Participant Capacity</label>
          <input type="number" min="1" id="capacity" name="capacity" class="form-control" placeholder="Optional max seats" value="<?= e($form_data['capacity'] ?? '') ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem" class="mb-4">
          <div class="form-group">
            <label class="form-label text-xs" for="minimum_team_size">Min Team Size</label>
            <input type="number" min="1" max="20" id="minimum_team_size" name="minimum_team_size" class="form-control" value="<?= e($form_data['minimum_team_size'] ?? 1) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label text-xs" for="maximum_team_size">Max Team Size</label>
            <input type="number" min="1" max="20" id="maximum_team_size" name="maximum_team_size" class="form-control" value="<?= e($form_data['maximum_team_size'] ?? 1) ?>" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">
          <i class="fa fa-save"></i> Save & Publish Event
        </button>
      </div>
    </div>

  </div>
</form>

<script>
function generateSlug(text) {
  const slug = text.toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '')
    .trim()
    .replace(/[\s-]+/g, '-');
  document.getElementById('slug').value = slug;
  document.getElementById('slugPreview').textContent = slug || 'event-slug';
}
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
