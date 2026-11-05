<?php
/**
 * RM bu ani - API: Delete / Deactivate Menu (Admin Only)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');
require_csrf();

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$id = (int)($input['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'ID menu tidak valid', null, 400);
}

try {
    $db = getDB();

    // Check if menu is referenced in past order items
    $orderCheck = $db->prepare("SELECT COUNT(*) FROM order_items WHERE menu_id = :id");
    $orderCheck->execute([':id' => $id]);
    $usageCount = (int)$orderCheck->fetchColumn();

    if ($usageCount > 0) {
        // Soft delete / disable availability to maintain transaction integrity
        $stmt = $db->prepare("UPDATE menus SET is_available = 0, updated_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $id]);
        json_response(true, 'Menu telah dinonaktifkan (karena telah tercatat dalam riwayat transaksi)', ['soft_deleted' => true]);
    } else {
        // Can be safely deleted
        $stmt = $db->prepare("DELETE FROM menus WHERE id = :id");
        $stmt->execute([':id' => $id]);
        json_response(true, 'Menu berhasil dihapus permanen', ['deleted' => true]);
    }
} catch (Exception $e) {
    json_response(false, 'Gagal menghapus menu: ' . $e->getMessage(), null, 500);
}
