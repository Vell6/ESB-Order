<?php
/**
 * RM bu ani - API: Update Profile
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

require_login();
require_csrf();

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$name = trim($input['name'] ?? '');
$phone = trim($input['phone'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

$userId = (int)$_SESSION['user_id'];

if (empty($name)) {
    json_response(false, 'Nama tidak boleh kosong', null, 422);
}
if (!empty($email) && !validate_email($email)) {
    json_response(false, 'Format email tidak valid', null, 422);
}

try {
    $db = getDB();

    // If email is changed, check uniqueness
    if (!empty($email) && $email !== $_SESSION['user_email']) {
        $check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $check->execute([':email' => $email, ':id' => $userId]);
        if ($check->fetch()) {
            json_response(false, 'Email telah digunakan oleh akun lain', null, 409);
        }
    } else {
        $email = $_SESSION['user_email'];
    }

    if (!empty($password)) {
        if (!validate_password($password)) {
            json_response(false, 'Password minimal 8 karakter', null, 422);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET name = :name, phone = :phone, email = :email, password = :password, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':name' => $name, ':phone' => $phone ?: null, ':email' => $email, ':password' => $hash, ':id' => $userId]);
    } else {
        $stmt = $db->prepare("UPDATE users SET name = :name, phone = :phone, email = :email, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':name' => $name, ':phone' => $phone ?: null, ':email' => $email, ':id' => $userId]);
    }

    // Refresh session
    $_SESSION['user_name'] = $name;
    $_SESSION['user_phone'] = $phone;
    $_SESSION['user_email'] = $email;

    json_response(true, 'Profil berhasil diperbarui', [
        'name'  => $name,
        'email' => $email,
        'phone' => $phone
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal memperbarui profil: ' . $e->getMessage(), null, 500);
}
