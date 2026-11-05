<?php
/**
 * RM bu ani - API: Customer Cancel Order
 * Strictly enforces cancellation only for unpaid orders by their owner
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode request tidak diizinkan', null, 405);
}

require_login();
require_csrf();

$currentUser = current_user();
$userId = (int)$currentUser['id'];

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$orderCode = trim($input['order_code'] ?? '');
if (empty($orderCode)) {
    json_response(false, 'Order code tidak valid', null, 400);
}

try {
    $db = getDB();

    // Check order and enforce ownership and unpaid status
    $stmt = $db->prepare("
        SELECT id, order_code, user_id, status, payment_status 
        FROM orders 
        WHERE order_code = :code AND user_id = :user_id 
        LIMIT 1
    ");
    $stmt->execute([':code' => $orderCode, ':user_id' => $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(false, 'Pesanan tidak ditemukan atau bukan milik Anda', null, 404);
    }

    if ($order['status'] !== 'unpaid' || $order['payment_status'] !== 'unpaid') {
        json_response(false, 'Pesanan yang telah dibayar atau sedang diproses tidak dapat dibatalkan oleh pelanggan.', null, 409);
    }

    // Cancel order
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW() 
        WHERE id = :id AND status = 'unpaid'
    ");
    $updateStmt->execute([':id' => $order['id']]);

    // Audit log
    log_order_status((int)$order['id'], 'unpaid', 'cancelled', $userId, 'Dibatalkan oleh pelanggan');

    json_response(true, 'Pesanan berhasil dibatalkan', [
        'order_code' => $orderCode,
        'status'     => 'cancelled'
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal membatalkan pesanan: ' . $e->getMessage(), null, 500);
}
