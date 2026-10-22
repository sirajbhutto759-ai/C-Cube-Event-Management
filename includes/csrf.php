<?php
// ============================================================
// C Cube – CSRF Protection Helper
// ============================================================

/**
 * Generate (or retrieve) the CSRF token for the current session.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate the submitted CSRF token.
 * Terminates with 403 on failure.
 */
function csrf_verify(): void {
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(csrf_token(), $submitted)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']));
    }
}
