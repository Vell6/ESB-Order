<?php
/**
 * RM bu ani - API: Login
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode request tidak diizinkan', null, 405);
}

require_csrf();

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    json_response(false, 'Email dan password wajib diisi', null, 422);
}

if (!validate_email($email)) {
    json_response(false, 'Format email tidak valid', null, 422);
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, email, password, phone, role, is_active FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        json_response(false, 'Email atau password salah', null, 401);
    }

    if ((int)$user['is_active'] !== 1) {
        json_response(false, 'Akun Anda dinonaktifkan. Silakan hubungi admin.', null, 403);
    }

    if (!password_verify($password, $user['password'])) {
        json_response(false, 'Email atau password salah', null, 401);
    }

    // Login user
    login_user($user);

    // Determine redirect target based on role
    $redirect = 'menu.php';
    if ($user['role'] === 'admin') {
        $redirect = 'admin/dashboard.php';
    } elseif ($user['role'] === 'cashier') {
        $redirect = 'admin/orders.php';
    } elseif ($user['role'] === 'kitchen') {
        $redirect = 'admin/kitchen.php';
    }

    if (!empty($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
    }

    json_response(true, 'Login berhasil!', [
        'user' => [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role']
        ],
        'redirect' => BASE_URL . $redirect
    ]);
} catch (Exception $e) {
    json_response(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
