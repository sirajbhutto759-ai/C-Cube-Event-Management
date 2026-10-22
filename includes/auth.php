<?php
// ============================================================
// C Cube – Authentication Helpers
// ============================================================

require_once __DIR__ . '/../config/database.php';

/**
 * Boot a secure session (call once, very early).
 */
function session_boot(): void {
    if (!ob_get_level()) {
        ob_start();
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,   // Set true when served over HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Returns the currently logged-in user array or null.
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if the visitor is logged in.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user']['id']);
}

/**
 * Require the visitor to be logged in; redirect otherwise.
 */
function require_login(string $redirect = '/login.php'): void {
    if (!is_logged_in()) {
        $url = BASE_URL . ltrim($redirect, '/');
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
}

/**
 * Require admin role; redirect otherwise.
 */
function require_admin(): void {
    require_login('/login.php');
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

/**
 * Require participant role.
 */
function require_participant(): void {
    require_login('/login.php');
}

/**
 * Log in a user: verify credentials, check rate limit, regenerate session.
 * Returns ['success'=>bool, 'message'=>string, 'user'=>array|null]
 */
function login_user(string $email, string $password, string $ip): array {
    // Rate limiting: max 10 attempts per IP per 15 minutes
    $pdo = db();
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    $stmt->execute([$ip]);
    if ($stmt->fetchColumn() >= 10) {
        return ['success' => false, 'message' => 'Too many login attempts. Please wait 15 minutes and try again.'];
    }

    // Record attempt
    $pdo->prepare("INSERT INTO login_attempts (ip_address, email) VALUES (?,?)")->execute([$ip, $email]);

    // Fetch user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    if ($user['account_status'] !== 'active') {
        return ['success' => false, 'message' => 'Your account is inactive. Please contact support.'];
    }

    // Regenerate session to prevent fixation
    session_regenerate_id(true);
    unset($user['password_hash']);
    $_SESSION['user'] = $user;

    return ['success' => true, 'message' => 'Welcome back, ' . $user['full_name'] . '!', 'user' => $user];
}

/**
 * Register a new participant.
 */
function register_user(array $data): array {
    $pdo = db();

    // Check duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'An account with this email already exists.'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, phone, institution)
         VALUES (:full_name, :email, :hash, :phone, :institution)"
    );
    $stmt->execute([
        ':full_name'   => $data['full_name'],
        ':email'       => $data['email'],
        ':hash'        => $hash,
        ':phone'       => $data['phone'] ?? null,
        ':institution' => $data['institution'] ?? null,
    ]);
    $userId = (int)$pdo->lastInsertId();

    // Send welcome notification
    $pdo->prepare(
        "INSERT INTO notifications (user_id, title, message) VALUES (?,?,?)"
    )->execute([
        $userId,
        'Welcome to C Cube!',
        'Your account has been created successfully. Explore upcoming competitions and register today!'
    ]);

    return ['success' => true, 'message' => 'Account created successfully!', 'user_id' => $userId];
}

/**
 * Generate a unique registration reference number.
 */
function generate_reference(): string {
    $pdo = db();
    do {
        $ref = 'CCR-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM registrations WHERE registration_reference = ?");
        $stmt->execute([$ref]);
    } while ($stmt->fetch());
    return $ref;
}

/**
 * Get unread notification count for current user.
 */
function unread_notification_count(): int {
    if (!is_logged_in()) return 0;
    $stmt = db()->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->execute([$_SESSION['user']['id']]);
    return (int)$stmt->fetchColumn();
}
