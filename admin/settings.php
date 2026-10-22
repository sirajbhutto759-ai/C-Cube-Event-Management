<?php
// ============================================================
// C Cube – Admin Website & Payment Settings
// ============================================================
$page_title = 'Website & Payment Settings';
require_once __DIR__ . '/admin-header.php';

// Handle Save Settings via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $settings_keys = [
        'site_name',
        'site_tagline',
        'contact_email',
        'contact_phone',
        'contact_address',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'payment_instructions',
        'payment_currency',
        'maintenance_mode'
    ];

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );

        foreach ($settings_keys as $key) {
            $val = trim($_POST[$key] ?? '');
            if ($key === 'maintenance_mode') {
                $val = !empty($_POST['maintenance_mode']) ? '1' : '0';
            }
            $stmt->execute([$key, $val]);
        }

        audit_log('settings_updated', 'settings', 0, "Updated platform configuration and payment instructions");
        redirect_with_message(BASE_URL . 'admin/settings.php', 'success', 'System settings updated successfully!');
    } catch (Throwable $e) {
        error_log('[CCube Settings] ' . $e->getMessage());
        redirect_with_message(BASE_URL . 'admin/settings.php', 'danger', 'Failed to save settings.');
    }
}

// Fetch all current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
$current_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="dashboard-header">
  <div>
    <h1 class="dashboard-title">System Settings</h1>
    <p class="dashboard-subtitle">Configure site branding, bank transfer instructions, contact info, and social links</p>
  </div>
</div>

<form method="POST" action="">
  <?= csrf_field() ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start">
    
    <!-- Left Column: Site Identity & Contact -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">
      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-globe text-accent"></i> Website Branding</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="site_name">Website Name</label>
          <input type="text" id="site_name" name="site_name" class="form-control" value="<?= e($current_settings['site_name'] ?? 'C Cube') ?>" required>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="site_tagline">Tagline</label>
          <input type="text" id="site_tagline" name="site_tagline" class="form-control" value="<?= e($current_settings['site_tagline'] ?? 'Where Innovation Meets Competition') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="payment_currency">Currency Code</label>
          <input type="text" id="payment_currency" name="payment_currency" class="form-control" value="<?= e($current_settings['payment_currency'] ?? 'PKR') ?>" required>
        </div>

        <div class="form-group mb-3">
          <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;color:var(--text-primary)">
            <input type="checkbox" name="maintenance_mode" value="1" <?= (!empty($current_settings['maintenance_mode']) && $current_settings['maintenance_mode'] === '1') ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--clr-primary-2)">
            <span><strong>Maintenance Mode</strong> (Show maintenance notice to visitors)</span>
          </label>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-envelope text-accent"></i> Contact Information</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="contact_email">Public Contact Email</label>
          <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?= e($current_settings['contact_email'] ?? 'hello@ccube.dev') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="contact_phone">Contact Phone</label>
          <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?= e($current_settings['contact_phone'] ?? '+92-300-0000000') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="contact_address">Physical Address</label>
          <textarea id="contact_address" name="contact_address" class="form-control" rows="2"><?= e($current_settings['contact_address'] ?? 'Pakistan') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Right Column: Payment Instructions & Socials -->
    <div style="display:flex;flex-direction:column;gap:1.5rem">
      <div class="dash-card" id="payment_section">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-university text-accent"></i> Payment Instructions</div>
        </div>
        <p class="text-xs text-muted mb-3">
          These instructions are displayed to participants on the payment submission page after registration.
        </p>

        <div class="form-group mb-4">
          <label class="form-label" for="payment_instructions">Bank Details & Verification Guidelines</label>
          <textarea id="payment_instructions" name="payment_instructions" class="form-control font-mono" rows="8"><?= e($current_settings['payment_instructions'] ?? '') ?></textarea>
        </div>
      </div>

      <div class="dash-card">
        <div class="dash-card-header mb-3">
          <div class="dash-card-title"><i class="fa fa-share-alt text-accent"></i> Social Media Links</div>
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="facebook_url"><i class="fab fa-facebook text-muted"></i> Facebook URL</label>
          <input type="text" id="facebook_url" name="facebook_url" class="form-control" value="<?= e($current_settings['facebook_url'] ?? '#') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="instagram_url"><i class="fab fa-instagram text-muted"></i> Instagram URL</label>
          <input type="text" id="instagram_url" name="instagram_url" class="form-control" value="<?= e($current_settings['instagram_url'] ?? '#') ?>">
        </div>

        <div class="form-group mb-3">
          <label class="form-label" for="twitter_url"><i class="fab fa-twitter text-muted"></i> Twitter / X URL</label>
          <input type="text" id="twitter_url" name="twitter_url" class="form-control" value="<?= e($current_settings['twitter_url'] ?? '#') ?>">
        </div>

        <div class="form-group mb-4">
          <label class="form-label" for="linkedin_url"><i class="fab fa-linkedin text-muted"></i> LinkedIn URL</label>
          <input type="text" id="linkedin_url" name="linkedin_url" class="form-control" value="<?= e($current_settings['linkedin_url'] ?? '#') ?>">
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%">
          <i class="fa fa-save"></i> Save All Settings
        </button>
      </div>
    </div>

  </div>
</form>

<?php require_once __DIR__ . '/admin-footer.php'; ?>
