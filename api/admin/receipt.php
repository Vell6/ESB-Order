<?php
/**
 * RM bu ani - API: Receipt Data
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin', 'cashier']);

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    json_response(false, 'Order code tidak valid', null, 400);
}

try {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT o.*, u.name as customer_real_name, u.phone as customer_phone
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE o.order_code = :code LIMIT 1
    ");
    $stmt->execute([':code' => $code]);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(false, 'Pesanan tidak ditemukan', null, 404);
    }

    if ($order['payment_status'] !== 'paid') {
        json_response(false, 'Struk hanya dapat dibuat untuk pesanan yang telah dibayar lunas', null, 409);
    }

    $itemStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :oid ORDER BY id ASC");
    $itemStmt->execute([':oid' => $order['id']]);
    $items = $itemStmt->fetchAll();

    json_response(true, 'Data struk pembayaran', [
        'store' => [
            'name'    => APP_NAME,
            'address' => APP_ADDRESS,
            'phone'   => APP_PHONE
        ],
        'order' => [
            'order_code'     => $order['order_code'],
            'customer_name'  => $order['customer_name'],
            'order_type'     => $order['order_type'],
            'table_number'   => $order['table_number'],
            'paid_at'        => $order['paid_at'],
            'payment_method' => $order['payment_method'],
            'subtotal'       => (float)$order['subtotal'],
            'total'          => (float)$order['total'],
            'items'          => array_map(function($it) {
                return [
                    'name'        => $it['menu_name'],
                    'qty'         => (int)$it['quantity'],
                    'unit_price'  => (float)$it['unit_price'],
                    'spicy_level' => $it['spicy_level'] ? (int)$it['spicy_level'] : null,
                    'notes'       => $it['item_notes'],
                    'subtotal'    => (float)$it['subtotal']
                ];
            }, $items)
        ]
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal memuat struk: ' . $e->getMessage(), null, 500);
}
