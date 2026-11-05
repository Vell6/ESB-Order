<?php
/**
 * RM bu ani - CSRF Protection Layer
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or get current CSRF token
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF hidden input field for forms
 */
function csrf_field(): string {
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify CSRF token from POST parameter or HTTP Header
 */
function verify_csrf_token(?string $token = null): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }

    if ($token === null) {
        // Check POST parameter
        if (!empty($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        } 
        // Check JSON body if available
        else {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            if (is_array($data) && !empty($data['csrf_token'])) {
                $token = $data['csrf_token'];
            }
        }

        // Check HTTP headers
        if ($token === null) {
            $headers = getallheaders();
            $token = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        }
    }

    if (!$token) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Enforce CSRF check or respond with 403 Forbidden
 */
function require_csrf(): void {
    if (!verify_csrf_token()) {
        http_response_code(403);
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || 
            str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ||
            str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'CSRF token tidak valid atau telah kedaluwarsa. Silakan refresh halaman.'
            ]);
        } else {
            die('Error 403: Invalid CSRF Token.');
        }
        exit;
    }
}
