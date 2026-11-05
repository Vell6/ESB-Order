<?php
/**
 * RM bu ani - API: Register Customer
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

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

$errors = [];

if (empty($name)) {
    $errors['name'] = 'Nama lengkap wajib diisi';
}
if (empty($email) || !validate_email($email)) {
    $errors['email'] = 'Email tidak valid';
}
if (!empty($phone) && !validate_phone($phone)) {
    $errors['phone'] = 'Nomor telepon tidak valid (minimal 9 digit angka)';
}
if (empty($password) || !validate_password($password)) {
    $errors['password'] = 'Password minimal 8 karakter';
}
if ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Konfirmasi password tidak cocok';
}

if (!empty($errors)) {
    json_response(false, 'Validasi gagal', null, 422, $errors);
}

try {
    $db = getDB();

    // Check if email already registered
    $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $checkStmt->execute([':email' => $email]);
    if ($checkStmt->fetch()) {
        json_response(false, 'Email sudah terdaftar. Silakan gunakan email lain atau login.', null, 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $db->prepare("
        INSERT INTO users (name, email, password, phone, role, is_active, created_at, updated_at)
        VALUES (:name, :email, :password, :phone, 'customer', 1, NOW(), NOW())
    ");
    $insertStmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':password' => $hash,
        ':phone'    => $phone ?: null
    ]);

    $newUserId = (int)$db->lastInsertId();

    // Automatically log in the customer
    $user = [
        'id'    => $newUserId,
        'name'  => $name,
        'email' => $email,
        'phone' => $phone,
        'role'  => 'customer'
    ];
    login_user($user);

    json_response(true, 'Registrasi berhasil! Selamat datang di RM bu ani.', [
        'user'     => $user,
        'redirect' => BASE_URL . 'menu.php'
    ], 201);
} catch (Exception $e) {
    json_response(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
