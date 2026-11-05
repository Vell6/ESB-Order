<?php
/**
 * RM bu ani - API: Kitchen Complete Order
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode request tidak diizinkan', null, 405);
}

require_role(['kitchen', 'admin']);
require_csrf();

$currentUser = current_user();
$staffId = (int)$currentUser['id'];

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$orderId = (int)($input['order_id'] ?? 0);
$orderCode = trim($input['order_code'] ?? '');

if ($orderId <= 0 && empty($orderCode)) {
    json_response(false, 'ID pesanan tidak valid', null, 400);
}

try {
    $db = getDB();

    $where = ($orderId > 0) ? "id = :val" : "order_code = :val";
    $val = ($orderId > 0) ? $orderId : $orderCode;

    $stmt = $db->prepare("SELECT id, order_code, customer_name, order_type, table_number, status, payment_status FROM orders WHERE {$where} LIMIT 1");
    $stmt->execute([':val' => $val]);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(false, 'Pesanan tidak ditemukan', null, 404);
    }

    // Must be paid and processing
    if ($order['payment_status'] !== 'paid') {
        json_response(false, 'Gagal: Pesanan belum dibayar, dapur tidak dapat menyelesaikannya.', null, 409);
    }

    if ($order['status'] !== 'processing') {
        json_response(false, 'Pesanan tidak dalam status diproses dapur.', null, 409);
    }

    // Complete the order
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET status = 'completed', completed_at = NOW(), updated_at = NOW() 
        WHERE id = :id AND status = 'processing'
    ");
    $updateStmt->execute([':id' => $order['id']]);

    // Audit log
    $logNote = "Pesanan diselesaikan oleh dapur ({$currentUser['name']})";
    log_order_status((int)$order['id'], 'processing', 'completed', $staffId, $logNote);

    json_response(true, 'Pesanan ' . $order['order_code'] . ' selesai disiapkan!', [
        'order_id'     => (int)$order['id'],
        'order_code'   => $order['order_code'],
        'order_type'   => $order['order_type'],
        'table_number' => $order['table_number'],
        'status'       => 'completed'
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal menyelesaikan pesanan: ' . $e->getMessage(), null, 500);
}
