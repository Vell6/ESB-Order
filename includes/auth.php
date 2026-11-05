<?php
/**
 * RM bu ani - Authentication & Authorization Layer
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

/**
 * Check if a user is currently logged in
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Get currently logged-in user array
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }

    return [
        'id'    => $_SESSION['user_id'] ?? null,
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'phone' => $_SESSION['user_phone'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'customer'
    ];
}

/**
 * Get current user role
 */
function user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if current user has an admin-level or specific role
 */
function has_role(string|array $roles): bool {
    if (!is_logged_in()) return false;
    $currentRole = user_role();
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return $currentRole === $roles;
}

/**
 * Require user to be logged in
 */
function require_login(string $redirectUrl = 'login.php'): void {
    if (!is_logged_in()) {
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/') || 
                 str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Anda harus login terlebih dahulu untuk mengakses layanan ini.'
            ]);
            exit;
        }

        // Save return URL
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: " . BASE_URL . $redirectUrl);
        exit;
    }
}

/**
 * Require specific roles (e.g. ['admin'], ['admin', 'cashier'], ['kitchen'])
 */
function require_role(string|array $roles, string $fallbackUrl = 'index.php'): void {
    require_login();

    if (!has_role($roles)) {
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/') || 
                 str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Akses ditolak: Anda tidak memiliki izin untuk fitur ini.'
            ]);
            exit;
        }

        header("Location: " . BASE_URL . $fallbackUrl . "?error=unauthorized");
        exit;
    }
}

/**
 * Log in a user securely and regenerate session ID
 */
function login_user(array $user): void {
    // Regenerate session to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_phone'] = $user['phone'] ?? '';
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['last_activity'] = time();
}

/**
 * Log out user securely
 */
function logout_user(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
