<?php
/**
 * RM bu ani - API: Lightweight Order Status Polling for Customers
 * Interval: 5 seconds
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$orderCode = trim($_GET['code'] ?? '');

try {
    $db = getDB();

    if (!empty($orderCode)) {
        $stmt = $db->prepare("
            SELECT id, order_code, order_type, table_number, status, payment_status, updated_at, completed_at
            FROM orders 
            WHERE order_code = :code AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':code' => $orderCode, ':user_id' => $userId]);
    } else {
        // Get user's most recent active (unpaid or processing) order
        $stmt = $db->prepare("
            SELECT id, order_code, order_type, table_number, status, payment_status, updated_at, completed_at
            FROM orders 
            WHERE user_id = :user_id AND status IN ('unpaid', 'processing')
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([':user_id' => $userId]);
    }

    $order = $stmt->fetch();

    if (!$order) {
        json_response(true, 'Tidak ada pesanan aktif', ['has_active' => false]);
    }

    $dineInMessage = "Makanan Anda selesai disiapkan. Pelayan akan segera mengantarkannya ke meja " . ($order['table_number'] ?: '') . ".";
    $takeawayMessage = "Pesanan Anda sudah siap. Silakan ambil makanan di kasir.";

    $notificationText = '';
    if ($order['status'] === 'completed') {
        $notificationText = ($order['order_type'] === 'takeaway') ? $takeawayMessage : $dineInMessage;
    } elseif ($order['status'] === 'processing') {
        $notificationText = 'Pembayaran terkonfirmasi. Makanan sedang disiapkan di dapur.';
    }

    json_response(true, 'Status pesanan aktif', [
        'has_active'        => true,
        'order_id'          => (int)$order['id'],
        'order_code'        => $order['order_code'],
        'order_type'        => $order['order_type'],
        'table_number'      => $order['table_number'],
        'status'            => $order['status'],
        'status_label'      => ORDER_STATUS_LABELS[$order['status']]['label'] ?? ucfirst($order['status']),
        'status_badge'      => ORDER_STATUS_LABELS[$order['status']]['badge'] ?? 'badge-unpaid',
        'payment_status'    => $order['payment_status'],
        'notification_text' => $notificationText,
        'updated_at'        => $order['updated_at']
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil status pesanan: ' . $e->getMessage(), null, 500);
}
