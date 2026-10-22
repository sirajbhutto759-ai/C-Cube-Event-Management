<?php
// ============================================================
// C Cube – Secure Receipt Viewer
// Access restricted to:
// 1. The participant who owns the registration
// 2. Administrators
// ============================================================
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
session_boot();
require_login();

$user = current_user();
$pdo  = db();

$payment_id = (int)($_GET['payment_id'] ?? 0);
if (!$payment_id) {
    http_response_code(400);
    exit('Invalid payment request.');
}

// Fetch payment and associated registration
$stmt = $pdo->prepare(
    "SELECT p.receipt_path, p.payment_status, r.user_id, r.registration_reference
     FROM payments p
     JOIN registrations r ON r.id = p.registration_id
     WHERE p.id = ? LIMIT 1"
);
$stmt->execute([$payment_id]);
$pay = $stmt->fetch();

if (!$pay || empty($pay['receipt_path'])) {
    http_response_code(404);
    exit('Receipt not found.');
}

// Authorization check: Must be Admin OR the registration owner
$is_admin = ($user['role'] === 'admin');
$is_owner = ((int)$pay['user_id'] === (int)$user['id']);

if (!$is_admin && !$is_owner) {
    http_response_code(403);
    exit('Access Denied: You are not authorized to view this receipt.');
}

// Prevent path traversal
$receipt_rel = str_replace('\\', '/', $pay['receipt_path']);
$filename    = basename($receipt_rel);
$filepath    = BASE_PATH . '/uploads/payment-receipts/' . $filename;

if (!file_exists($filepath) || !is_file($filepath)) {
    http_response_code(404);
    exit('Receipt file does not exist on disk.');
}

// Determine MIME type securely
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($filepath);

// Allowed safe MIME types
$allowed_mimes = [
    'image/jpeg'      => 'image/jpeg',
    'image/png'       => 'image/png',
    'image/webp'      => 'image/webp',
    'image/gif'       => 'image/gif',
    'application/pdf' => 'application/pdf',
];

if (!isset($allowed_mimes[$mime])) {
    http_response_code(415);
    exit('Unsupported receipt format.');
}

// Set security and caching headers
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($filepath));
header('Content-Disposition: inline; filename="receipt_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $pay['registration_reference']) . '.' . pathinfo($filepath, PATHINFO_EXTENSION) . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

// Stream file
readfile($filepath);
exit;
