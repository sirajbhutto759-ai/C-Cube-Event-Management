<?php
// ============================================================
// C Cube – Homepage (index.php)
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
session_boot();

$pdo = db();

// ─── Live DB stats ─────────────────────────────────────────
$stats = [
    'events'       => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status='published'")->fetchColumn(),
    'participants' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='participant' AND account_status='active'")->fetchColumn(),
    'registrations'=> (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn(),
    'categories'   => (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
];

// ─── Featured events (latest 6 published) ──────────────────
$featured = $pdo->query(
    "SELECT e.*, c.name AS category_name
     FROM events e LEFT JOIN categories c ON e.category_id=c.id
     WHERE e.status='published'
     ORDER BY e.created_at DESC LIMIT 6"
)->fetchAll();

// ─── Upcoming events (soonest event dates in future) ───────
$upcoming = $pdo->query(
    "SELECT e.*, c.name AS category_name
     FROM events e LEFT JOIN categories c ON e.category_id=c.id
     WHERE e.status='published' AND e.event_date > NOW()
     ORDER BY e.event_date ASC LIMIT 3"
)->fetchAll();

// ─── Categories with event counts ──────────────────────────
$categories = $pdo->query(
    "SELECT c.*, COUNT(e.id) AS event_count
     FROM categories c
     LEFT JOIN events e ON e.category_id=c.id AND e.status='published'
     GROUP BY c.id ORDER BY event_count DESC"
)->fetchAll();

$page_title  = 'Home';
$body_class  = 'page-enter';
require_once __DIR__ . '/includes/header.php';
?>

<!-- ─── HERO ──────────────────────────────────────────────────── -->
<section class="hero animated-gradient-bg" id="home">
  <div class="hero-bg">
    <div class="hero-grid"></div>
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-orb hero-orb-3"></div>
    <div class="hero-shape hero-shape-1"></div>
    <div class="hero-shape hero-shape-2"></div>
    <div class="hero-shape hero-shape-3"></div>
  </div>
  <div class="container">
    <div class="hero-content" data-reveal>
      <div class="hero-badge">
        <span>Live Competitions Open for Registration</span>
      </div>
      <h1 class="hero-title">
        Where <span class="gradient-text">Innovation</span><br>
        Meets Competition.
      </h1>
      <p class="hero-subtitle">
        C Cube is Pakistan's premier student competition platform — from programming marathons
        to hackathons, design sprints, and AI challenges. Showcase your talent and win big.
      </p>
      <div class="hero-cta">
        <a href="<?= BASE_URL ?>events.php" class="btn btn-primary btn-lg">
          <i class="fa fa-rocket"></i> Explore Competitions
        </a>
        <a href="<?= BASE_URL ?>register.php" class="btn btn-outline btn-lg">
          <i class="fa fa-user-plus"></i> Register Now
        </a>
      </div>

      <!-- Live stats from DB -->
      <div class="hero-stats" data-reveal-stagger>
        <div>
          <div class="hero-stat-value" data-counter data-target="<?= $stats['events'] ?>"><?= $stats['events'] ?></div>
          <div class="hero-stat-label">Active Events</div>
        </div>
        <div>
          <div class="hero-stat-value" data-counter data-target="<?= $stats['participants'] ?>"><?= $stats['participants'] ?></div>
          <div class="hero-stat-label">Participants</div>
        </div>
        <div>
          <div class="hero-stat-value" data-counter data-target="<?= $stats['registrations'] ?>"><?= $stats['registrations'] ?></div>
          <div class="hero-stat-label">Registrations</div>
        </div>
        <div>
          <div class="hero-stat-value" data-counter data-target="<?= $stats['categories'] ?>"><?= $stats['categories'] ?></div>
          <div class="hero-stat-label">Categories</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ─── FEATURED EVENTS ─────────────────────────────────────── -->
<section class="section" id="events">
  <div class="container">
    <div class="section-header" data-reveal>
      <span class="section-label">Competitions</span>
      <h2 class="section-title">Featured Events</h2>
      <p class="section-subtitle">Explore our hand-picked competitions across technology, design, and engineering.</p>
    </div>

    <!-- Category pills -->
    <div class="categories-scroll" data-reveal>
      <button class="cat-pill active" data-filter="all"><i class="fa fa-th-large"></i> All Events</button>
      <?php foreach ($categories as $cat): ?>
        <button class="cat-pill" data-filter="<?= e($cat['slug']) ?>">
          <i class="fa <?= e($cat['icon']) ?>"></i> <?= e($cat['name']) ?>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if (empty($featured)): ?>
      <div class="empty-state">
        <i class="fa fa-calendar-times"></i>
        <h3>No events yet</h3>
        <p>Check back soon — new competitions are being added!</p>
      </div>
    <?php else: ?>
      <div class="events-grid" data-reveal-stagger>
        <?php foreach ($featured as $event): ?>
          <div class="event-card-wrap" data-category="<?= e($event['category_name'] ? strtolower(str_replace(' ','',implode('-',explode(' ',$event['category_name'])))) : '') ?>">
            <?php include __DIR__ . '/includes/event_card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="text-center mt-4">
        <a href="<?= BASE_URL ?>events.php" class="btn btn-outline">
          <i class="fa fa-compass"></i> View All Events
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ─── EVENT CATEGORIES ────────────────────────────────────── -->
<section class="section section-dark" id="categories">
  <div class="container">
    <div class="section-header" data-reveal>
      <span class="section-label">Categories</span>
      <h2 class="section-title">Competition Tracks</h2>
      <p class="section-subtitle">Every skill has a stage. Find your track and compete with the best.</p>
    </div>
    <div class="cards-grid-3" data-reveal-stagger>
      <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>events.php?category=<?= e($cat['slug']) ?>" class="feature-card" style="text-decoration:none">
          <div class="feature-icon">
            <i class="fa <?= e($cat['icon']) ?>"></i>
          </div>
          <div class="feature-title"><?= e($cat['name']) ?></div>
          <p class="feature-desc"><?= e($cat['description'] ?? '') ?></p>
          <div class="mt-2 text-xs text-muted"><?= $cat['event_count'] ?> event<?= $cat['event_count'] != 1 ? 's' : '' ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ─── HOW IT WORKS ────────────────────────────────────────── -->
<section class="section section-gradient" id="how-it-works">
  <div class="container">
    <div class="section-header" data-reveal>
      <span class="section-label">Process</span>
      <h2 class="section-title">How It Works</h2>
      <p class="section-subtitle">From discovery to approval — your journey in three simple steps.</p>
    </div>
    <div class="steps-grid" data-reveal-stagger>
      <div class="step-card">
        <div class="step-number">1</div>
        <div class="step-icon"><i class="fa fa-search"></i></div>
        <div class="step-title">Explore a Competition</div>
        <p class="step-desc">Browse events by category, date, or skill level. Read competition details, rules, and prize information.</p>
      </div>
      <div class="step-card">
        <div class="step-number">2</div>
        <div class="step-icon"><i class="fa fa-credit-card"></i></div>
        <div class="step-title">Register & Submit Payment</div>
        <p class="step-desc">Create your account, fill the registration form, and submit your payment reference for manual verification.</p>
      </div>
      <div class="step-card">
        <div class="step-number">3</div>
        <div class="step-icon"><i class="fa fa-check-circle"></i></div>
        <div class="step-title">Get Confirmed</div>
        <p class="step-desc">Our admin team verifies your payment and approves your registration. You receive a confirmation with your reference number.</p>
      </div>
    </div>
  </div>
</section>

<!-- ─── UPCOMING EVENTS ──────────────────────────────────────── -->
<?php if (!empty($upcoming)): ?>
<section class="section" id="upcoming">
  <div class="container">
    <div class="section-header" data-reveal>
      <span class="section-label">Coming Soon</span>
      <h2 class="section-title">Upcoming Events</h2>
      <p class="section-subtitle">Don't miss these approaching competitions. Register before seats fill up.</p>
    </div>
    <div class="cards-grid-3" data-reveal-stagger>
      <?php foreach ($upcoming as $event): ?>
        <?php
          $deadline_passed = $event['registration_deadline'] && strtotime($event['registration_deadline']) < time();
          $days_left = $event['event_date'] ? max(0, ceil((strtotime($event['event_date']) - time()) / 86400)) : null;
        ?>
        <div class="card animated-border" style="display:flex;flex-direction:column;gap:0.75rem;">
          <div class="d-flex justify-between align-center">
            <?= status_badge($event['status']) ?>
            <?php if ($days_left !== null): ?>
              <span class="badge badge-primary"><i class="fa fa-clock"></i> <?= $days_left ?> day<?= $days_left != 1 ? 's' : '' ?></span>
            <?php endif; ?>
          </div>
          <div class="fw-700"><?= e($event['title']) ?></div>
          <div class="text-sm text-muted"><?= e($event['short_description'] ?? '') ?></div>
          <div class="text-xs text-faint">
            <i class="fa fa-calendar text-primary"></i> <?= fmt_date($event['event_date'], 'M j, Y g:i A') ?>
          </div>
          <div class="text-xs text-faint">
            <i class="fa fa-tag text-primary"></i> <?= fmt_money($event['registration_fee']) ?> registration fee
          </div>
          <a href="<?= BASE_URL ?>event-details.php?slug=<?= e($event['slug']) ?>" class="btn btn-primary btn-sm mt-1">
            <i class="fa fa-eye"></i> View Details
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ─── WHY C CUBE ───────────────────────────────────────────── -->
<section class="section section-dark" id="about">
  <div class="container">
    <div class="section-header" data-reveal>
      <span class="section-label">Why C Cube</span>
      <h2 class="section-title">Built for Competitors</h2>
      <p class="section-subtitle">Everything you need to compete, collaborate, and grow — in one premium platform.</p>
    </div>
    <div class="cards-grid-4" data-reveal-stagger>
      <?php
      $features = [
        ['fa-bolt',         'Easy Registration',         'Register in minutes. Our streamlined process handles all types of events — individual or team.'],
        ['fa-trophy',       'Multiple Competitions',     'From coding contests to robotics, we host a wide range of competitions across every technical discipline.'],
        ['fa-shield-alt',   'Secure Payments',           'Manual payment verification ensures every transaction is checked and confirmed by our admin team.'],
        ['fa-map-pin',      'Status Tracking',           'Track your registration and payment status in real time from your personal dashboard.'],
        ['fa-users',        'Team Support',              'Compete alone or build a team. Our platform handles team registrations with member management.'],
        ['fa-certificate',  'Certificates',              'Receive digital certificates and acknowledgements for every competition you participate in.'],
        ['fa-headset',      'Admin Support',             'Our dedicated admin team reviews every registration and payment to ensure a fair process.'],
        ['fa-mobile-alt',   'Mobile Ready',              'Access your dashboard, check event details, and track status from any device.'],
      ];
      foreach ($features as [$icon, $title, $desc]):
      ?>
        <div class="feature-card">
          <div class="feature-icon"><i class="fa <?= $icon ?>"></i></div>
          <div class="feature-title"><?= $title ?></div>
          <p class="feature-desc"><?= $desc ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ─── CTA BANNER ──────────────────────────────────────────── -->
<section class="section section-gradient" id="contact">
  <div class="container">
    <div class="card-glass text-center" style="padding:3.5rem 2rem;max-width:700px;margin:0 auto;" data-reveal>
      <div class="section-label mb-3">Ready to Compete?</div>
      <h2 style="font-size:2rem;font-weight:900;margin-bottom:1rem">Join <span class="gradient-text">C Cube</span> Today</h2>
      <p class="text-muted mb-4">Create your free account, explore competitions, and start your journey toward recognition and prizes.</p>
      <div class="hero-cta" style="justify-content:center">
        <?php if (!is_logged_in()): ?>
          <a href="<?= BASE_URL ?>register.php" class="btn btn-primary btn-lg"><i class="fa fa-user-plus"></i> Create Account</a>
          <a href="<?= BASE_URL ?>login.php"    class="btn btn-outline btn-lg"><i class="fa fa-sign-in-alt"></i> Sign In</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>events.php"               class="btn btn-primary btn-lg"><i class="fa fa-rocket"></i> Explore Events</a>
          <a href="<?= BASE_URL ?>participant/dashboard.php" class="btn btn-outline btn-lg"><i class="fa fa-tachometer-alt"></i> My Dashboard</a>
        <?php endif; ?>
      </div>
      <div class="mt-4 text-xs text-faint">
        <i class="fa fa-envelope"></i> Questions? <a href="mailto:<?= e(setting('contact_email')) ?>" style="color:var(--clr-primary-2)"><?= e(setting('contact_email')) ?></a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
