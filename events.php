<?php
// ============================================================
// C Cube – Events Listing Page
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
session_boot();
$pdo = db();

// Filters
$search   = trim($_GET['search']   ?? '');
$cat_slug = trim($_GET['category'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9;

// Get categories
$categories = $pdo->query(
    "SELECT c.*, COUNT(e.id) AS event_count
     FROM categories c LEFT JOIN events e ON e.category_id=c.id AND e.status='published'
     GROUP BY c.id ORDER BY event_count DESC"
)->fetchAll();

// Category ID lookup
$cat_id = null;
if ($cat_slug) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug=?");
    $stmt->execute([$cat_slug]);
    $row = $stmt->fetch();
    if ($row) $cat_id = $row['id'];
}

// Build query
$where_parts = ["e.status = 'published'"];
$params      = [];
if ($search) {
    $where_parts[] = "(e.title LIKE ? OR e.short_description LIKE ?)";
    $params[]      = "%$search%";
    $params[]      = "%$search%";
}
if ($cat_id) {
    $where_parts[] = "e.category_id = ?";
    $params[]      = $cat_id;
}
$where = implode(' AND ', $where_parts);

$count_sql = "SELECT COUNT(*) FROM events e LEFT JOIN categories c ON e.category_id=c.id WHERE $where";
$data_sql  = "SELECT e.*, c.name AS category_name, c.icon AS category_icon
              FROM events e LEFT JOIN categories c ON e.category_id=c.id
              WHERE $where ORDER BY e.event_date ASC";

$stmt_c = $pdo->prepare($count_sql);
$stmt_c->execute($params);
$total = (int)$stmt_c->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page  = max(1, min($page, $pages));

$stmt_d = $pdo->prepare($data_sql . " LIMIT $per_page OFFSET " . (($page-1)*$per_page));
$stmt_d->execute($params);
$events = $stmt_d->fetchAll();

$page_title = 'Explore Events';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page header -->
<div style="padding: 7rem 0 3rem; background: linear-gradient(180deg, #0d0d22, #080818); border-bottom: 1px solid rgba(255,255,255,0.06);">
  <div class="container">
    <div class="section-header mb-0" data-reveal>
      <span class="section-label">All Competitions</span>
      <h1 class="section-title">Explore Events</h1>
      <p class="section-subtitle">Discover competitions across all categories. Filter by track or search by name.</p>
    </div>
  </div>
</div>

<section class="section-sm">
  <div class="container">
    <!-- Category pills -->
    <div class="categories-scroll mb-3" data-reveal>
      <a href="<?= BASE_URL ?>events.php" class="cat-pill <?= !$cat_slug ? 'active' : '' ?>">
        <i class="fa fa-th-large"></i> All
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>events.php?category=<?= urlencode($cat['slug']) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
           class="cat-pill <?= $cat['slug']===$cat_slug ? 'active' : '' ?>">
          <i class="fa <?= e($cat['icon']) ?>"></i> <?= e($cat['name']) ?>
          <span class="badge badge-secondary" style="font-size:0.65rem"><?= $cat['event_count'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="search-bar" data-reveal>
      <form method="GET" action="" style="display:flex;gap:0.75rem;flex:1;flex-wrap:wrap">
        <?php if ($cat_slug): ?><input type="hidden" name="category" value="<?= e($cat_slug) ?>"><?php endif; ?>
        <div class="search-input-wrapper" style="flex:1;min-width:220px">
          <i class="fa fa-search"></i>
          <input type="text" name="search" class="form-control" placeholder="Search competitions…" value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> Search</button>
        <?php if ($search || $cat_slug): ?>
          <a href="<?= BASE_URL ?>events.php" class="btn btn-outline"><i class="fa fa-times"></i> Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Results count -->
    <div class="text-sm text-muted mb-3">
      Showing <?= count($events) ?> of <?= $total ?> event<?= $total != 1 ? 's' : '' ?>
      <?= $cat_slug ? ' in <strong>' . e($cat_slug) . '</strong>' : '' ?>
      <?= $search ? ' matching <strong>"' . e($search) . '"</strong>' : '' ?>
    </div>

    <!-- Events grid -->
    <?php if (empty($events)): ?>
      <div class="empty-state">
        <i class="fa fa-search"></i>
        <h3>No events found</h3>
        <p>Try adjusting your search or category filter.</p>
        <a href="<?= BASE_URL ?>events.php" class="btn btn-outline mt-2">Clear Filters</a>
      </div>
    <?php else: ?>
      <div class="events-grid" data-reveal-stagger>
        <?php foreach ($events as $event): ?>
          <div class="event-card-wrap" data-category="<?= e($cat_slug) ?>">
            <?php include __DIR__ . '/includes/event_card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
        <div class="pagination mt-4" style="justify-content:center">
          <?php
          $qs = http_build_query(array_filter(['search'=>$search,'category'=>$cat_slug]));
          $qs = $qs ? '&'.$qs : '';
          ?>
          <?php if ($page > 1): ?>
            <a class="page-btn" href="?page=<?= $page-1 . $qs ?>"><i class="fa fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($p=1; $p<=$pages; $p++): ?>
            <a class="page-btn <?= $p===$page ? 'active' : '' ?>" href="?page=<?= $p . $qs ?>"><?= $p ?></a>
          <?php endfor; ?>
          <?php if ($page < $pages): ?>
            <a class="page-btn" href="?page=<?= $page+1 . $qs ?>"><i class="fa fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
