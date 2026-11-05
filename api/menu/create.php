<?php
/**
 * RM bu ani - API: Create Menu (Admin Only)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');
require_csrf();

$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$category = trim($_POST['category'] ?? '');
$supportsSpicy = !empty($_POST['supports_spicy']) ? 1 : 0;
$isAvailable = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

if (empty($name)) {
    json_response(false, 'Nama menu wajib diisi', null, 422);
}
if ($price <= 0) {
    json_response(false, 'Harga menu harus lebih besar dari Rp 0', null, 422);
}
if (empty($category)) {
    json_response(false, 'Kategori menu wajib diisi', null, 422);
}

// Handle file upload securely per PRD Section 10.6
$imagePath = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    if ($file['size'] > $maxSize) {
        json_response(false, 'Ukuran gambar maksimal 2 MB', null, 422);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!array_key_exists($mime, $allowedMimes)) {
        json_response(false, 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.', null, 422);
    }

    $ext = $allowedMimes[$mime];
    $randomName = 'menu_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $uploadDir = __DIR__ . '/../../uploads/menus/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $targetFile = $uploadDir . $randomName;
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        json_response(false, 'Gagal mengunggah gambar', null, 500);
    }

    $imagePath = 'uploads/menus/' . $randomName;
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO menus (name, description, price, image, category, supports_spicy, is_available, created_at, updated_at)
        VALUES (:name, :description, :price, :image, :category, :supports_spicy, :is_available, NOW(), NOW())
    ");
    $stmt->execute([
        ':name'           => $name,
        ':description'    => $description ?: null,
        ':price'          => $price,
        ':image'          => $imagePath,
        ':category'       => $category,
        ':supports_spicy' => $supportsSpicy,
        ':is_available'   => $isAvailable
    ]);

    $id = (int)$db->lastInsertId();

    json_response(true, 'Menu baru berhasil ditambahkan', ['id' => $id], 201);
} catch (Exception $e) {
    json_response(false, 'Gagal menambahkan menu: ' . $e->getMessage(), null, 500);
}
