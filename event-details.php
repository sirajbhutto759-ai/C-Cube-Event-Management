<?php
// ============================================================
// C Cube – Event Details Page
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
session_boot();
$pdo = db();

$slug  = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . BASE_URL . 'events.php'); exit; }

$stmt  = $pdo->prepare(
    "SELECT e.*, c.name AS category_name, c.icon AS category_icon, c.slug AS category_slug
     FROM events e LEFT JOIN categories c ON e.category_id=c.id
     WHERE e.slug = ? LIMIT 1"
);
$stmt->execute([$slug]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    $page_title = '404 – Event Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="empty-state section"><i class="fa fa-exclamation-triangle"></i><h3>Event not found</h3><a href="'.BASE_URL.'events.php" class="btn btn-primary mt-3">Browse Events</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Seats availability
$seats_left = null;
if ($event['capacity']) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND registration_status NOT IN ('rejected','cancelled')");
    $s->execute([$event['id']]);
    $seats_left = max(0, $event['capacity'] - (int)$s->fetchColumn());
}

$open            = is_registration_open($event);
$deadline_passed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
$user            = current_user();

// Check if already registered
$already_registered = false;
if ($user && $user['role'] === 'participant') {
    $s = $pdo->prepare("SELECT id FROM registrations WHERE user_id=? AND event_id=?");
    $s->execute([$user['id'], $event['id']]);
    $already_registered = (bool)$s->fetch();
}

$page_title = e($event['title']);
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<div class="event-detail-hero">
  <div class="container">
    <!-- Breadcrumb -->
    <nav style="margin-bottom:1.5rem;font-size:0.82rem;color:var(--clr-text-faint)">
      <a href="<?= BASE_URL ?>" style="color:var(--clr-text-faint)">Home</a>
      <i class="fa fa-chevron-right" style="margin:0 0.5rem;font-size:0.65rem"></i>
      <a href="<?= BASE_URL ?>events.php" style="color:var(--clr-text-faint)">Events</a>
      <i class="fa fa-chevron-right" style="margin:0 0.5rem;font-size:0.65rem"></i>
      <span style="color:var(--clr-text-muted)"><?= e($event['title']) ?></span>
    </nav>

    <!-- Banner -->
    <?php if (!empty($event['banner_path']) && file_exists(BASE_PATH.'/'.$event['banner_path'])): ?>
      <img class="event-detail-banner" src="<?= BASE_URL . e($event['banner_path']) ?>" alt="<?= e($event['title']) ?>">
    <?php else: ?>
      <div class="event-detail-banner" style="background:linear-gradient(135deg,#130d33,#0d1b40);display:flex;align-items:center;justify-content:center;font-size:5rem;color:rgba(108,99,255,0.3)">
        <i class="fa fa-<?= e($event['category_icon'] ?? 'trophy') ?>"></i>
      </div>
    <?php endif; ?>

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1.5rem;flex-wrap:wrap">
      <div>
        <?php if ($event['category_name']): ?>
          <a href="<?= BASE_URL ?>events.php?category=<?= e($event['category_slug']) ?>" class="event-card-category mb-2" style="display:inline-block">
            <i class="fa <?= e($event['category_icon'] ?? 'fa-tag') ?>"></i> <?= e($event['category_name']) ?>
          </a>
        <?php endif; ?>
        <h1 style="font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;margin-bottom:0.75rem"><?= e($event['title']) ?></h1>
        <p style="color:var(--clr-text-muted);max-width:650px"><?= e($event['short_description'] ?? '') ?></p>
      </div>
      <div style="display:flex;gap:0.6rem;flex-wrap:wrap;align-items:center">
        <?= status_badge($event['status']) ?>
        <?php if ($seats_left !== null): ?>
          <span class="badge <?= $seats_left > 0 ? 'badge-info' : 'badge-danger' ?>">
            <?= $seats_left > 0 ? $seats_left . ' seats left' : 'Full' ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Body -->
<div class="event-detail-body">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 340px;gap:2.5rem;align-items:start" class="detail-grid">

      <!-- Left: Details -->
      <div>
        <!-- Meta grid -->
        <div class="event-meta-grid" data-reveal-stagger>
          <?php if ($event['event_date']): ?>
          <div class="event-meta-item">
            <i class="fa fa-calendar-alt"></i>
            <div class="meta-label">Event Date</div>
            <div class="meta-value"><?= fmt_date($event['event_date'], 'M j, Y') ?></div>
            <div style="font-size:0.78rem;color:var(--clr-text-faint)"><?= fmt_date($event['event_date'], 'g:i A') ?></div>
          </div>
          <?php endif; ?>
          <?php if ($event['registration_deadline']): ?>
          <div class="event-meta-item">
            <i class="fa fa-hourglass-half"></i>
            <div class="meta-label">Registration Deadline</div>
            <div class="meta-value <?= $deadline_passed ? 'text-danger' : '' ?>"><?= fmt_date($event['registration_deadline'], 'M j, Y') ?></div>
            <div style="font-size:0.78rem;color:var(--clr-text-faint)"><?= $deadline_passed ? 'Closed' : 'Open' ?></div>
          </div>
          <?php endif; ?>
          <div class="event-meta-item">
            <i class="fa fa-tag"></i>
            <div class="meta-label">Registration Fee</div>
            <div class="meta-value"><?= fmt_money($event['registration_fee']) ?></div>
          </div>
          <?php if ($event['venue']): ?>
          <div class="event-meta-item">
            <i class="fa fa-map-marker-alt"></i>
            <div class="meta-label">Venue</div>
            <div class="meta-value" style="font-size:0.85rem"><?= e($event['venue']) ?></div>
          </div>
          <?php endif; ?>
          <?php if ($event['minimum_team_size'] > 1 || $event['maximum_team_size'] > 1): ?>
          <div class="event-meta-item">
            <i class="fa fa-users"></i>
            <div class="meta-label">Team Size</div>
            <div class="meta-value"><?= $event['minimum_team_size'] ?>–<?= $event['maximum_team_size'] ?> members</div>
          </div>
          <?php endif; ?>
          <?php if ($event['capacity']): ?>
          <div class="event-meta-item">
            <i class="fa fa-user-check"></i>
            <div class="meta-label">Capacity</div>
            <div class="meta-value"><?= $event['capacity'] ?> participants</div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Description -->
        <?php if ($event['description']): ?>
        <div class="card mb-3" data-reveal>
          <h3 style="margin-bottom:1rem">About This Event</h3>
          <div class="prose"><?= $event['description'] ?></div>
        </div>
        <?php endif; ?>

        <!-- Eligibility -->
        <?php if ($event['eligibility']): ?>
        <div class="card mb-3" data-reveal>
          <h3 style="margin-bottom:1rem"><i class="fa fa-id-card text-primary"></i> Eligibility</h3>
          <div class="prose" style="white-space:pre-line"><?= e($event['eligibility']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Rules -->
        <?php if ($event['rules']): ?>
        <div class="card mb-3" data-reveal>
          <h3 style="margin-bottom:1rem"><i class="fa fa-gavel text-primary"></i> Rules & Regulations</h3>
          <div class="prose" style="white-space:pre-line"><?= e($event['rules']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Prizes -->
        <?php if ($event['prize_information']): ?>
        <div class="card mb-3" data-reveal style="background:linear-gradient(135deg,rgba(245,158,11,0.06),rgba(108,99,255,0.06));border-color:rgba(245,158,11,0.2)">
          <h3 style="margin-bottom:1rem"><i class="fa fa-trophy" style="color:#fbbf24"></i> Prize Information</h3>
          <div class="prose" style="white-space:pre-line"><?= e($event['prize_information']) ?></div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right: Registration CTA -->
      <div style="position:sticky;top:90px" id="register">
        <div class="card animated-border" data-reveal>
          <div style="text-align:center;margin-bottom:1.5rem">
            <div style="font-size:1.8rem;font-weight:900;color:var(--clr-white)"><?= fmt_money($event['registration_fee']) ?></div>
            <div style="font-size:0.8rem;color:var(--clr-text-faint)">Registration Fee</div>
          </div>

          <?php if ($already_registered): ?>
            <div class="alert alert-info"><i class="fa fa-info-circle"></i> You're already registered for this event.</div>
            <a href="<?= BASE_URL ?>participant/my-registrations.php" class="btn btn-outline btn-block">View My Registration</a>

          <?php elseif (!$user): ?>
            <div class="alert alert-info"><i class="fa fa-info-circle"></i> You need to log in to register.</div>
            <a href="<?= BASE_URL ?>login.php?redirect=<?= urlencode(BASE_URL.'event-details.php?slug='.$slug) ?>" class="btn btn-primary btn-block mb-2">
              <i class="fa fa-sign-in-alt"></i> Login to Register
            </a>
            <a href="<?= BASE_URL ?>register.php" class="btn btn-outline btn-block">Create Account</a>

          <?php elseif ($user['role'] === 'admin'): ?>
            <div class="alert alert-warning"><i class="fa fa-shield-alt"></i> Admins cannot register for events.</div>

          <?php elseif (!$open): ?>
            <div class="alert alert-danger">
              <i class="fa fa-times-circle"></i>
              <?php
              if ($event['status'] === 'cancelled') echo 'This event has been cancelled.';
              elseif ($event['status'] === 'closed')  echo 'Registration is closed for this event.';
              elseif ($deadline_passed)                echo 'The registration deadline has passed.';
              elseif ($seats_left === 0)               echo 'This event is at full capacity.';
              else                                     echo 'Registration is currently unavailable.';
              ?>
            </div>

          <?php else: ?>
            <!-- Registration form -->
            <form method="POST" action="<?= BASE_URL ?>register-event.php" data-validate data-loading>
              <?= csrf_field() ?>
              <input type="hidden" name="event_id" value="<?= $event['id'] ?>">

              <div class="form-group">
                <label class="form-label">Full Name <span class="required">*</span></label>
                <input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
                <div class="form-error" style="display:none"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Email <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required>
                <div class="form-error" style="display:none"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Institution</label>
                <input type="text" name="institution" class="form-control" value="<?= e($user['institution'] ?? '') ?>">
              </div>

              <?php if ($event['maximum_team_size'] > 1): ?>
              <div class="form-group">
                <label class="form-label">Participation Type <span class="required">*</span></label>
                <select name="participation_type" class="form-control" id="participationType">
                  <option value="individual">Individual</option>
                  <option value="team">Team</option>
                </select>
              </div>
              <div id="teamSection" style="display:none">
                <div class="form-group">
                  <label class="form-label">Team Name <span class="required">*</span></label>
                  <input type="text" name="team_name" class="form-control">
                </div>
                <div id="membersList"></div>
                <button type="button" id="addMemberBtn" class="btn btn-outline btn-sm mb-2">
                  <i class="fa fa-plus"></i> Add Team Member
                </button>
              </div>
              <script>
              document.getElementById('participationType').addEventListener('change', function(){
                document.getElementById('teamSection').style.display = this.value==='team'?'':'none';
              });
              </script>
              <?php else: ?>
                <input type="hidden" name="participation_type" value="individual">
              <?php endif; ?>

              <div class="form-group">
                <label class="form-label">Additional Information</label>
                <textarea name="additional_info" class="form-control" rows="3" placeholder="Any specific requirements or notes…"></textarea>
              </div>

              <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fa fa-pen"></i> Register Now
              </button>
            </form>
          <?php endif; ?>

          <!-- Quick info -->
          <div style="margin-top:1.25rem;display:flex;flex-direction:column;gap:0.5rem">
            <?php if ($event['event_date']): ?>
            <div class="text-xs text-muted"><i class="fa fa-calendar text-primary" style="width:14px"></i> <?= fmt_date($event['event_date'], 'D, M j, Y') ?></div>
            <?php endif; ?>
            <?php if ($event['registration_deadline']): ?>
            <div class="text-xs text-muted"><i class="fa fa-clock text-primary" style="width:14px"></i> Deadline: <?= fmt_date($event['registration_deadline'], 'M j, Y') ?></div>
            <?php endif; ?>
            <?php if ($seats_left !== null): ?>
            <div class="text-xs <?= $seats_left <= 5 ? 'text-warning' : 'text-muted' ?>"><i class="fa fa-users text-primary" style="width:14px"></i> <?= $seats_left ?> seats remaining</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div><!-- /detail-grid -->
  </div>
</div>

<style>
@media(max-width:900px){
  .detail-grid { grid-template-columns: 1fr !important; }
  #register { position: static !important; }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
