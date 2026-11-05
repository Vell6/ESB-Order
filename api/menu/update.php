<?php
/**
 * RM bu ani - API: Update Menu (Admin Only)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');
require_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'ID menu tidak valid', null, 400);
}

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

try {
    $db = getDB();
    
    // Check if menu exists
    $existingStmt = $db->prepare("SELECT id, image FROM menus WHERE id = :id LIMIT 1");
    $existingStmt->execute([':id' => $id]);
    $existing = $existingStmt->fetch();
    if (!$existing) {
        json_response(false, 'Menu tidak ditemukan', null, 404);
    }

    $imagePath = $existing['image'];

    // Handle new image upload if provided
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
            json_response(false, 'Format gambar tidak didukung (gunakan JPG, PNG, WEBP)', null, 422);
        }

        $ext = $allowedMimes[$mime];
        $randomName = 'menu_' . bin2hex(random_bytes(10)) . '.' . $ext;
        $uploadDir = __DIR__ . '/../../uploads/menus/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $targetFile = $uploadDir . $randomName;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            // Remove previous custom uploaded image if exists and not default svg
            if ($existing['image'] && str_starts_with($existing['image'], 'uploads/menus/')) {
                $oldPath = __DIR__ . '/../../' . $existing['image'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $imagePath = 'uploads/menus/' . $randomName;
        }
    }

    $stmt = $db->prepare("
        UPDATE menus 
        SET name = :name, 
            description = :description, 
            price = :price, 
            image = :image, 
            category = :category, 
            supports_spicy = :supports_spicy, 
            is_available = :is_available, 
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':name'           => $name,
        ':description'    => $description ?: null,
        ':price'          => $price,
        ':image'          => $imagePath,
        ':category'       => $category,
        ':supports_spicy' => $supportsSpicy,
        ':is_available'   => $isAvailable,
        ':id'             => $id
    ]);

    json_response(true, 'Menu berhasil diperbarui', ['id' => $id]);
} catch (Exception $e) {
    json_response(false, 'Gagal memperbarui menu: ' . $e->getMessage(), null, 500);
}
