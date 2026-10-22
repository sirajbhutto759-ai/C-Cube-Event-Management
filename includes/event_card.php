<?php
// ============================================================
// C Cube – Reusable Event Card Partial
// Expects: $event (array from events + categories join)
// ============================================================
$cat_slug = $event['category_name']
    ? strtolower(preg_replace('/\s+/','-', trim($event['category_name'])))
    : 'general';
$open       = is_registration_open($event);
$seats_left = null;
if ($event['capacity']) {
    $taken      = (int)$pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND registration_status NOT IN ('rejected','cancelled')")->execute([$event['id']]) ? 0 : 0;
    $stmt_seats = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE event_id=? AND registration_status NOT IN ('rejected','cancelled')");
    $stmt_seats->execute([$event['id']]);
    $seats_left = max(0, $event['capacity'] - (int)$stmt_seats->fetchColumn());
}
?>
<div class="event-card">
  <!-- Banner -->
  <div class="event-card-banner">
    <?php if (!empty($event['banner_path']) && file_exists(BASE_PATH . '/' . $event['banner_path'])): ?>
      <img src="<?= BASE_URL . e($event['banner_path']) ?>" alt="<?= e($event['title']) ?>" loading="lazy">
    <?php else: ?>
      <div class="event-card-banner-placeholder">
        <i class="fa fa-<?= e($event['icon'] ?? 'trophy') ?>"></i>
      </div>
    <?php endif; ?>
    <div class="event-card-status"><?= status_badge($event['status']) ?></div>
  </div>

  <!-- Body -->
  <div class="event-card-body">
    <?php if ($event['category_name']): ?>
      <div class="event-card-category">
        <i class="fa fa-tag"></i> <?= e($event['category_name']) ?>
      </div>
    <?php endif; ?>
    <div class="event-card-title"><?= e($event['title']) ?></div>
    <div class="event-card-desc"><?= e($event['short_description'] ?? '') ?></div>

    <div class="event-card-meta">
      <?php if ($event['event_date']): ?>
        <span><i class="fa fa-calendar"></i> <?= fmt_date($event['event_date'], 'M j, Y') ?></span>
      <?php endif; ?>
      <?php if ($event['registration_deadline']): ?>
        <span><i class="fa fa-hourglass-half"></i> Deadline: <?= fmt_date($event['registration_deadline'], 'M j, Y') ?></span>
      <?php endif; ?>
      <?php if ($event['venue']): ?>
        <span><i class="fa fa-map-marker-alt"></i> <?= e(mb_substr($event['venue'], 0, 30)) ?>…</span>
      <?php endif; ?>
      <?php if ($seats_left !== null): ?>
        <span><i class="fa fa-users"></i> <?= $seats_left ?> seats left</span>
      <?php endif; ?>
    </div>
  </div>

  <!-- Footer -->
  <div class="event-card-footer">
    <div class="event-fee">
      <?= fmt_money($event['registration_fee']) ?>
      <span>/ person</span>
    </div>
    <div class="d-flex gap-1">
      <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($event['slug']) ?>" class="btn btn-outline btn-sm">
        <i class="fa fa-eye"></i> Details
      </a>
      <?php if ($open): ?>
        <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($event['slug']) ?>#register" class="btn btn-primary btn-sm">
          <i class="fa fa-pen"></i> Register
        </a>
      <?php else: ?>
        <button class="btn btn-sm" style="background:rgba(255,255,255,0.05);color:var(--clr-text-faint);cursor:not-allowed" disabled>Closed</button>
      <?php endif; ?>
    </div>
  </div>
</div>
