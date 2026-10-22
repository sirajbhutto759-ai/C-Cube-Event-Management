<?php
// ============================================================
// C Cube – Database Configuration
// ⚠️  Keep this file outside public web root in production.
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'ccube_event_management');
define('DB_USER', 'root');          // Change for production
define('DB_PASS', '');              // Change for production
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO instance.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log internally – never expose credentials
            error_log('[CCube DB] Connection failed: ' . $e->getMessage());
            http_response_code(503);
            die('<h2 style="font-family:sans-serif;text-align:center;margin-top:80px">Service temporarily unavailable. Please try again later.</h2>');
        }
    }
    return $pdo;
}
