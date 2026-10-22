<?php
// ============================================================
// C Cube – Admin Category Management
// ============================================================
$page_title = 'Event Categories';
require_once __DIR__ . '/admin-header.php';

$errors = [];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_category') {
        $name = trim($_POST['name'] ?? '');
        $slug = slugify(trim($_POST['slug'] ?? $name));
        $desc = trim($_POST['description'] ?? '');
        $icon = trim($_POST['icon'] ?? 'fa-trophy');

        if (!$name) {
            $errors[] = 'Category name is required.';
        }

        // Check unique slug
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $errors[] = 'Category slug already exists. Please pick a different name.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, icon) VALUES (?,?,?,?)");
            $stmt->execute([$name, $slug, $desc ?: null, $icon]);
            audit_log('category_created', 'categories', (int)$pdo->lastInsertId(), "Created category: {$name}");
            redirect_with_message(BASE_URL . 'admin/categories.php', 'success', "Category '{$name}' created successfully!");
        }
    } elseif ($action === 'update_category') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $slug   = slugify(trim($_POST['slug'] ?? $name));
        $desc   = trim($_POST['description'] ?? '');
        $icon   = trim($_POST['icon'] ?? 'fa-trophy');

        if (!$name) $errors[] = 'Category name is required.';

        $stmt = $pdo->prepare("SELECT id FROM categories WHERE slug = ? AND id != ? LIMIT 1");
        $stmt->execute([$slug, $cat_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Category slug already taken.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=?, icon=? WHERE id=?");
            $stmt->execute([$name, $slug, $desc ?: null, $icon, $cat_id]);
            audit_log('category_updated', 'categories', $cat_id, "Updated category: {$name}");
            redirect_with_message(BASE_URL . 'admin/categories.php', 'success', "Category '{$name}' updated successfully!");
        }
    } elseif ($action === 'delete_category') {
        $cat_id = (int)($_POST['category_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id=?");
        $stmt->execute([$cat_id]);
        $name = $stmt->fetchColumn();

        if ($name) {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$cat_id]);
            audit_log('category_deleted', 'categories', $cat_id, "Deleted category: {$name}");
            redirect_with_message(BASE_URL . 'admin/categories.php', 'success', "Category '{$name}' has been deleted.");
        }
    }
}

// Fetch categories with event counts
$categories = $pdo->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM events e WHERE e.category_id = c.id) AS event_count
    FROM categories c
    ORDER BY c.name ASC
")->fetchAll();
?>

<div class="dashboard-header" style="flex-wrap:wrap;gap:1rem">
  <div>
    <h1 class="dashboard-title">Competition Categories</h1>
    <p class="dashboard-subtitle">Manage competition disciplines, hackathon tracks, icons, and descriptions</p>
  </div>
  <div>
    <button type="button" class="btn btn-primary" onclick="openCreateModal()">
      <i class="fa fa-plus-circle"></i> Add Category
    </button>
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

<!-- Categories Table -->
<div class="dash-card">
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Icon & Name</th>
          <th>Slug</th>
          <th>Description</th>
          <th>Active Competitions</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:0.75rem">
                <div style="width:38px;height:38px;border-radius:var(--radius-sm);background:rgba(99,102,241,0.15);display:flex;align-items:center;justify-content:center;color:var(--clr-primary-2);font-size:1.1rem">
                  <i class="fa <?= e($cat['icon'] ?: 'fa-trophy') ?>"></i>
                </div>
                <strong style="color:var(--text-primary);font-size:0.95rem"><?= e($cat['name']) ?></strong>
              </div>
            </td>
            <td>
              <span class="font-mono text-xs" style="background:rgba(255,255,255,0.06);padding:0.2rem 0.5rem;border-radius:4px"><?= e($cat['slug']) ?></span>
            </td>
            <td class="text-xs" style="max-width:320px;color:var(--text-secondary)">
              <?= e($cat['description'] ?: 'No description provided') ?>
            </td>
            <td>
              <span style="font-weight:600;color:var(--text-primary)"><?= $cat['event_count'] ?></span>
              <span class="text-xs text-muted">events</span>
            </td>
            <td style="text-align:right">
              <div style="display:inline-flex;gap:0.4rem">
                <button type="button" class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($cat) ?>)' title="Edit Category" style="padding:0.3rem 0.6rem;font-size:0.75rem">
                  <i class="fa fa-edit"></i>
                </button>
                <form method="POST" action="" style="display:inline" onsubmit="return confirm('Delete this category? Any associated events will become uncategorized.')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete_category">
                  <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="btn btn-outline btn-sm text-danger" title="Delete Category" style="border-color:rgba(239,68,68,0.3);padding:0.3rem 0.6rem;font-size:0.75rem">
                    <i class="fa fa-trash-alt"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Category Modal (Create / Edit) -->
<div class="modal-backdrop" id="categoryModal">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title" id="catModalTitle"><i class="fa fa-tag text-accent"></i> Add Category</div>
      <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
    </div>
    <form method="POST" action="">
      <?= csrf_field() ?>
      <input type="hidden" name="action" id="catAction" value="create_category">
      <input type="hidden" name="category_id" id="catId" value="">

      <div class="form-group mb-3">
        <label class="form-label" for="catName">Category Name <span class="text-danger">*</span></label>
        <input type="text" id="catName" name="name" class="form-control" placeholder="e.g. Cybersecurity, Robotics" required oninput="document.getElementById('catSlug').value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'')">
      </div>

      <div class="form-group mb-3">
        <label class="form-label" for="catSlug">URL Slug</label>
        <input type="text" id="catSlug" name="slug" class="form-control" placeholder="e.g. cybersecurity">
      </div>

      <div class="form-group mb-3">
        <label class="form-label" for="catIcon">FontAwesome Icon Class</label>
        <div style="position:relative">
          <input type="text" id="catIcon" name="icon" class="form-control" placeholder="e.g. fa-code, fa-brain, fa-robot" value="fa-trophy">
        </div>
        <small class="text-muted text-xs">Font Awesome 6 icon name (e.g. fa-code, fa-laptop-code, fa-brain)</small>
      </div>

      <div class="form-group mb-4">
        <label class="form-label" for="catDesc">Description</label>
        <textarea id="catDesc" name="description" class="form-control" rows="3" placeholder="Brief overview of competitions hosted under this track"></textarea>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:0.75rem">
        <button type="button" class="btn btn-outline btn-sm" onclick="closeModal('categoryModal')">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" id="catSubmitBtn"><i class="fa fa-save"></i> Save Category</button>
      </div>
    </form>
  </div>
</div>

<script>
function openCreateModal() {
  document.getElementById('catModalTitle').innerHTML = '<i class="fa fa-plus-circle text-accent"></i> Add New Category';
  document.getElementById('catAction').value = 'create_category';
  document.getElementById('catId').value = '';
  document.getElementById('catName').value = '';
  document.getElementById('catSlug').value = '';
  document.getElementById('catIcon').value = 'fa-trophy';
  document.getElementById('catDesc').value = '';
  document.getElementById('catSubmitBtn').textContent = 'Create Category';
  openModal('categoryModal');
}

function openEditModal(cat) {
  document.getElementById('catModalTitle').innerHTML = '<i class="fa fa-edit text-accent"></i> Edit Category';
  document.getElementById('catAction').value = 'update_category';
  document.getElementById('catId').value = cat.id;
  document.getElementById('catName').value = cat.name;
  document.getElementById('catSlug').value = cat.slug;
  document.getElementById('catIcon').value = cat.icon || 'fa-trophy';
  document.getElementById('catDesc').value = cat.description || '';
  document.getElementById('catSubmitBtn').textContent = 'Save Changes';
  openModal('categoryModal');
}
</script>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
