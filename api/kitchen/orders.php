<?php
/**
 * RM bu ani - API: Kitchen Display System (KDS) Active Orders
 * Polling Interval: 3-5 seconds
 * Strictly shows orders that have been paid and are processing in the kitchen
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['kitchen', 'admin']);

try {
    $db = getDB();

    // Only orders with payment_status = 'paid' AND status = 'processing'
    $stmt = $db->prepare("
        SELECT id, order_code, customer_name, order_type, table_number, notes,
               created_at, paid_at, processing_at
        FROM orders 
        WHERE payment_status = 'paid' AND status = 'processing'
        ORDER BY processing_at ASC, id ASC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll();

    $orderIds = array_column($orders, 'id');
    $itemsByOrder = [];

    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemStmt = $db->prepare("
            SELECT order_id, menu_name, quantity, spicy_level, item_notes 
            FROM order_items 
            WHERE order_id IN ($placeholders)
            ORDER BY id ASC
        ");
        $itemStmt->execute($orderIds);
        while ($it = $itemStmt->fetch()) {
            $itemsByOrder[$it['order_id']][] = [
                'menu_name'   => $it['menu_name'],
                'quantity'    => (int)$it['quantity'],
                'spicy_level' => $it['spicy_level'] ? (int)$it['spicy_level'] : null,
                'spicy_label' => $it['spicy_level'] ? (SPICY_LEVELS[(int)$it['spicy_level']] ?? null) : null,
                'item_notes'  => $it['item_notes']
            ];
        }
    }

    $formatted = array_map(function($o) use ($itemsByOrder) {
        $oid = (int)$o['id'];
        $procTime = strtotime($o['processing_at'] ?: $o['created_at']);
        $elapsedMinutes = max(0, round((time() - $procTime) / 60));

        return [
            'id'              => $oid,
            'order_code'      => $o['order_code'],
            'customer_name'   => $o['customer_name'],
            'order_type'      => $o['order_type'],
            'is_takeaway'     => ($o['order_type'] === 'takeaway'),
            'table_number'    => $o['table_number'],
            'notes'           => $o['notes'],
            'processing_at'   => $o['processing_at'],
            'elapsed_minutes' => $elapsedMinutes,
            'time_label'      => $elapsedMinutes === 0 ? 'Baru saja' : "{$elapsedMinutes} menit lalu",
            'items'           => $itemsByOrder[$oid] ?? []
        ];
    }, $orders);

    json_response(true, 'Daftar pesanan aktif dapur', [
        'orders' => $formatted,
        'count'  => count($formatted)
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil antrean dapur: ' . $e->getMessage(), null, 500);
}
