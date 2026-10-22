<?php
// ============================================================
// C Cube – API: Notifications Endpoint
// ============================================================
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
session_boot();

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user = current_user();
$pdo  = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark a specific notification or all as read
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? '';

    if ($action === 'mark_read') {
        $notif_id = (int)($input['notification_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $user['id']]);
        echo json_encode(['status' => 'success', 'message' => 'Notification marked as read']);
        exit;
    } elseif ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        echo json_encode(['status' => 'success', 'message' => 'All notifications marked as read']);
        exit;
    }
}

// GET: return unread count and latest 5 unread notifications
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $unread_count = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT id, title, message, created_at FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$user['id']]);
    $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'        => 'success',
        'unread_count'  => $unread_count,
        'notifications' => $notifs
    ], JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
