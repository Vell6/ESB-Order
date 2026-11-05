<?php
/**
 * RM bu ani - API: Notifications Polling
 * Dynamic role-based polling endpoint
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$role = user_role();
$userId = (int)($_SESSION['user_id'] ?? 0);

try {
    $db = getDB();
    $data = [];

    if ($role === 'cashier' || $role === 'admin') {
        // Cashier monitors unpaid orders and kitchen completed notifications
        $unpaidCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'unpaid'")->fetchColumn();
        $processingCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
        $recentCompleted = $db->query("
            SELECT order_code, customer_name, order_type, table_number, completed_at 
            FROM orders 
            WHERE status = 'completed' AND completed_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
            ORDER BY completed_at DESC LIMIT 5
        ")->fetchAll();

        $data['unpaid_count'] = $unpaidCount;
        $data['processing_count'] = $processingCount;
        $data['recent_completed'] = $recentCompleted;
    }

    if ($role === 'kitchen' || $role === 'admin') {
        // Kitchen monitors orders ready to prepare
        $kitchenOrdersCount = (int)$db->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'paid' AND status = 'processing'")->fetchColumn();
        $data['kitchen_queue_count'] = $kitchenOrdersCount;
    }

    if ($userId > 0) {
        // Customer monitors their active orders
        $activeOrders = $db->prepare("
            SELECT id, order_code, order_type, table_number, status, payment_status, completed_at
            FROM orders
            WHERE user_id = :uid AND status IN ('unpaid', 'processing')
            ORDER BY id DESC LIMIT 3
        ");
        $activeOrders->execute([':uid' => $userId]);
        $data['my_active_orders'] = $activeOrders->fetchAll();
    }

    $data['timestamp'] = date('Y-m-d H:i:s');
    json_response(true, 'Polling data', $data);
} catch (Exception $e) {
    json_response(false, 'Polling error: ' . $e->getMessage(), null, 500);
}
