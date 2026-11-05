<?php
/**
 * RM bu ani - API: Cashier Search Order by ID / Barcode
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin', 'cashier']);

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    json_response(false, 'Masukkan Order ID atau scan barcode terlebih dahulu', null, 400);
}

try {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT o.*, u.phone as customer_phone
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.order_code = :code OR o.order_code LIKE :likeCode
        ORDER BY o.id DESC LIMIT 1
    ");
    $stmt->execute([':code' => $code, ':likeCode' => '%' . $code . '%']);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(false, "Pesanan dengan kode '{$code}' tidak ditemukan", null, 404);
    }

    // Fetch items
    $itemStmt = $db->prepare("
        SELECT * FROM order_items WHERE order_id = :order_id ORDER BY id ASC
    ");
    $itemStmt->execute([':order_id' => $order['id']]);
    $items = [];
    while ($it = $itemStmt->fetch()) {
        $items[] = [
            'id'                 => (int)$it['id'],
            'menu_name'          => $it['menu_name'],
            'unit_price'         => (float)$it['unit_price'],
            'formatted_price'    => format_rupiah($it['unit_price']),
            'quantity'           => (int)$it['quantity'],
            'spicy_level'        => $it['spicy_level'] ? (int)$it['spicy_level'] : null,
            'spicy_label'        => $it['spicy_level'] ? (SPICY_LEVELS[(int)$it['spicy_level']] ?? null) : null,
            'item_notes'         => $it['item_notes'],
            'subtotal'           => (float)$it['subtotal'],
            'formatted_subtotal' => format_rupiah($it['subtotal'])
        ];
    }

    $result = [
        'id'               => (int)$order['id'],
        'order_code'       => $order['order_code'],
        'customer_name'    => $order['customer_name'],
        'customer_phone'   => $order['customer_phone'],
        'order_type'       => $order['order_type'],
        'order_type_label' => $order['order_type'] === 'dine_in' ? 'Makan di Tempat' : 'Take Away (Bungkus)',
        'table_number'     => $order['table_number'],
        'status'           => $order['status'],
        'status_label'     => ORDER_STATUS_LABELS[$order['status']]['label'] ?? ucfirst($order['status']),
        'status_badge'     => ORDER_STATUS_LABELS[$order['status']]['badge'] ?? 'badge-unpaid',
        'payment_status'   => $order['payment_status'],
        'total'            => (float)$order['total'],
        'formatted_total'  => format_rupiah($order['total']),
        'notes'            => $order['notes'],
        'created_at'       => $order['created_at'],
        'formatted_time'   => format_datetime($order['created_at']),
        'can_pay'          => ($order['status'] === 'unpaid' && $order['payment_status'] === 'unpaid'),
        'items'            => $items
    ];

    json_response(true, 'Pesanan ditemukan', $result);
} catch (Exception $e) {
    json_response(false, 'Gagal mencari pesanan: ' . $e->getMessage(), null, 500);
}
