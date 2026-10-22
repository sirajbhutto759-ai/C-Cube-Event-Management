<?php
// ============================================================
// C Cube – General Functions
// ============================================================

// Ensure output buffering is active so redirects can send HTTP headers cleanly
if (!ob_get_level()) {
    ob_start();
}

/**
 * Escape output for HTML context.
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get a site setting from the DB (cached in memory).
 */
function setting(string $key, string $default = ''): string {
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = db()->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
            $cache = array_column($rows, 'setting_value', 'setting_key');
        } catch (Throwable) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

/**
 * Format a date/datetime string.
 */
function fmt_date(string $dt, string $format = 'M j, Y'): string {
    if (!$dt) return '—';
    try { return (new DateTime($dt))->format($format); } catch (Exception) { return $dt; }
}

/**
 * Format currency.
 */
function fmt_money(float $amount): string {
    $currency = setting('payment_currency', 'PKR');
    return $currency . ' ' . number_format($amount, 0);
}

/**
 * Return a status badge HTML.
 */
function status_badge(string $status): string {
    $map = [
        'pending_payment'  => ['Pending Payment',   'badge-warning'],
        'pending_approval' => ['Pending Approval',  'badge-info'],
        'approved'         => ['Approved',           'badge-success'],
        'rejected'         => ['Rejected',           'badge-danger'],
        'cancelled'        => ['Cancelled',          'badge-secondary'],
        'not_submitted'    => ['Not Submitted',      'badge-warning'],
        'pending'          => ['Pending',            'badge-info'],
        'verified'         => ['Verified',           'badge-success'],
        'draft'            => ['Draft',              'badge-secondary'],
        'published'        => ['Published',          'badge-success'],
        'closed'           => ['Closed',             'badge-warning'],
        'active'           => ['Active',             'badge-success'],
        'inactive'         => ['Inactive',           'badge-secondary'],
        'banned'           => ['Banned',             'badge-danger'],
    ];
    [$label, $class] = $map[$status] ?? [ucfirst($status), 'badge-secondary'];
    return "<span class=\"badge {$class}\">" . e($label) . "</span>";
}

/**
 * Redirect with a one-time flash message.
 */
function redirect_with_message(string $url, string $type, string $message): never {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    if (headers_sent()) {
        echo "<script>window.location.href=" . json_encode($url) . ";</script>\n";
        echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "\"></noscript>";
        exit;
    }
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Get and clear the flash message.
 */
function get_flash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Sanitize a string for use as a URL slug.
 */
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Handle event banner upload. Returns relative path or null.
 */
function upload_event_banner(array $file): ?string {
    return handle_file_upload($file, UPLOAD_BANNERS, ['image/jpeg','image/png','image/webp','image/gif'], 5);
}

/**
 * Handle payment receipt upload. Returns relative path or null.
 */
function upload_receipt(array $file): ?string {
    return handle_file_upload($file, UPLOAD_RECEIPTS, ['image/jpeg','image/png','image/webp','application/pdf'], 5);
}

/**
 * Generic secure file upload handler.
 */
function handle_file_upload(array $file, string $dir, array $allowed_mimes, int $max_mb): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > $max_mb * 1024 * 1024) {
        throw new RuntimeException("File exceeds {$max_mb}MB limit.");
    }

    // Validate MIME via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mimes, true)) {
        throw new RuntimeException("File type '{$mime}' is not allowed.");
    }

    // Validate extension
    $ext_map = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/gif'       => 'gif',
        'application/pdf' => 'pdf',
    ];
    $ext = $ext_map[$mime] ?? null;
    if (!$ext) throw new RuntimeException("Unsupported file type.");

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest     = $dir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException("Failed to save uploaded file.");
    }

    // Relative path stored in DB
    $rel_dir = str_contains($dir, 'event-banners') ? 'uploads/event-banners/' : 'uploads/payment-receipts/';
    return $rel_dir . $filename;
}

/**
 * Write an audit log entry.
 */
function audit_log(string $action, string $entity_type = '', int $entity_id = 0, string $details = ''): void {
    $admin_id = $_SESSION['user']['id'] ?? null;
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '';
    try {
        db()->prepare(
            "INSERT INTO audit_logs (admin_user_id, action, entity_type, entity_id, details, ip_address)
             VALUES (?,?,?,?,?,?)"
        )->execute([$admin_id, $action, $entity_type, $entity_id ?: null, $details, $ip]);
    } catch (Throwable $e) {
        error_log('[CCube Audit] ' . $e->getMessage());
    }
}

/**
 * Create a notification for a user.
 */
function notify_user(int $user_id, string $title, string $message): void {
    try {
        db()->prepare(
            "INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)"
        )->execute([$user_id, $title, $message]);
    } catch (Throwable) {}
}

/**
 * Is the event registration still open?
 */
function is_registration_open(array $event): bool {
    if ($event['status'] !== 'published') return false;
    if ($event['registration_deadline'] && strtotime($event['registration_deadline']) < time()) return false;
    return true;
}

/**
 * Paginate helper: returns [items, total, pages, current_page].
 */
function paginate(PDO $pdo, string $count_sql, string $data_sql, array $params, int $per_page, int $page): array {
    $total = (int)$pdo->prepare($count_sql)->execute($params) ? $pdo->prepare($count_sql)->fetchColumn() : 0;
    $stmt  = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();
    $pages = max(1, (int)ceil($total / $per_page));
    $page  = max(1, min($page, $pages));
    $offset = ($page - 1) * $per_page;

    $stmt2 = $pdo->prepare($data_sql . " LIMIT $per_page OFFSET $offset");
    $stmt2->execute($params);
    return [$stmt2->fetchAll(), $total, $pages, $page];
}

// ─── Path Constants ───────────────────────────────────────────
if (!defined('BASE_PATH')) {
    define('BASE_PATH',      realpath(__DIR__ . '/..'));
    define('UPLOAD_BANNERS', BASE_PATH . '/uploads/event-banners');
    define('UPLOAD_RECEIPTS',BASE_PATH . '/uploads/payment-receipts');

    // Determine BASE_URL dynamically
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script   = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // Walk up until we find the project root segment
    $parts    = explode('/', trim($script, '/'));
    $root_idx = array_search('ccube-event-management', $parts);
    $base     = ($root_idx !== false)
        ? '/' . implode('/', array_slice($parts, 0, $root_idx + 1)) . '/'
        : '/ccube-event-management/';
    define('BASE_URL', $scheme . '://' . $host . $base);
}
