<?php
/**
 * RM bu ani - API: Order Detail
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$orderCode = trim($_GET['code'] ?? '');
if (empty($orderCode)) {
    json_response(false, 'Order code tidak valid', null, 400);
}

$currentUser = current_user();
$isStaff = has_role(['admin', 'cashier', 'kitchen']);

try {
    $db = getDB();

    $sql = "
        SELECT o.*, u.email as user_email, u.phone as user_phone,
               r.rating, r.comment as review_comment, r.id as review_id
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN reviews r ON r.order_id = o.id
        WHERE o.order_code = :code
    ";
    $params = [':code' => $orderCode];

    // Data ownership restriction per PRD Section 10.8
    if (!$isStaff) {
        $sql .= " AND o.user_id = :user_id";
        $params[':user_id'] = (int)$currentUser['id'];
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(false, 'Pesanan tidak ditemukan atau Anda tidak memiliki hak akses', null, 404);
    }

    // Fetch items
    $itemStmt = $db->prepare("
        SELECT oi.*, m.image 
        FROM order_items oi
        LEFT JOIN menus m ON m.id = oi.menu_id
        WHERE oi.order_id = :order_id
        ORDER BY oi.id ASC
    ");
    $itemStmt->execute([':order_id' => $order['id']]);
    $items = [];
    while ($it = $itemStmt->fetch()) {
        $items[] = [
            'id'          => (int)$it['id'],
            'menu_id'     => (int)$it['menu_id'],
            'menu_name'   => $it['menu_name'],
            'unit_price'  => (float)$it['unit_price'],
            'formatted_price' => format_rupiah($it['unit_price']),
            'quantity'    => (int)$it['quantity'],
            'spicy_level' => $it['spicy_level'] ? (int)$it['spicy_level'] : null,
            'spicy_label' => $it['spicy_level'] ? (SPICY_LEVELS[(int)$it['spicy_level']] ?? null) : null,
            'item_notes'  => $it['item_notes'],
            'subtotal'    => (float)$it['subtotal'],
            'formatted_subtotal' => format_rupiah($it['subtotal']),
            'image'       => $it['image'] ?: 'assets/images/logo.svg'
        ];
    }

    $result = [
        'id'               => (int)$order['id'],
        'order_code'       => $order['order_code'],
        'customer_name'    => $order['customer_name'],
        'customer_phone'   => $order['user_phone'],
        'order_type'       => $order['order_type'],
        'order_type_label' => $order['order_type'] === 'dine_in' ? 'Makan di Tempat' : 'Take Away (Bungkus)',
        'table_number'     => $order['table_number'],
        'status'           => $order['status'],
        'status_label'     => ORDER_STATUS_LABELS[$order['status']]['label'] ?? ucfirst($order['status']),
        'status_badge'     => ORDER_STATUS_LABELS[$order['status']]['badge'] ?? 'badge-unpaid',
        'payment_status'   => $order['payment_status'],
        'payment_method'   => $order['payment_method'],
        'subtotal'         => (float)$order['subtotal'],
        'total'            => (float)$order['total'],
        'formatted_total'  => format_rupiah($order['total']),
        'notes'            => $order['notes'],
        'created_at'       => $order['created_at'],
        'formatted_time'   => format_datetime($order['created_at']),
        'paid_at'          => $order['paid_at'] ? format_datetime($order['paid_at']) : null,
        'processing_at'    => $order['processing_at'] ? format_datetime($order['processing_at']) : null,
        'completed_at'     => $order['completed_at'] ? format_datetime($order['completed_at']) : null,
        'cancelled_at'     => $order['cancelled_at'] ? format_datetime($order['cancelled_at']) : null,
        'has_reviewed'     => !empty($order['review_id']),
        'review'           => !empty($order['review_id']) ? [
            'rating'  => (int)$order['rating'],
            'comment' => $order['review_comment']
        ] : null,
        'items'            => $items
    ];

    json_response(true, 'Detail pesanan', $result);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil detail pesanan: ' . $e->getMessage(), null, 500);
}
