<?php
/**
 * RM bu ani - API: Customer My Orders
 * Returns user orders grouped or filtered by status
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_login();

$userId = (int)$_SESSION['user_id'];
$filterStatus = trim($_GET['status'] ?? ''); // unpaid, processing, completed, or all

try {
    $db = getDB();

    $sql = "
        SELECT o.*, r.rating, r.comment as review_comment, r.id as review_id
        FROM orders o
        LEFT JOIN reviews r ON r.order_id = o.id
        WHERE o.user_id = :user_id
    ";
    $params = [':user_id' => $userId];

    if (!empty($filterStatus) && in_array($filterStatus, ['unpaid', 'processing', 'completed', 'cancelled'])) {
        $sql .= " AND o.status = :status";
        $params[':status'] = $filterStatus;
    }

    $sql .= " ORDER BY o.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    // Fetch items for all these orders
    $orderIds = array_column($orders, 'id');
    $itemsByOrder = [];

    if (!empty($orderIds)) {
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $itemStmt = $db->prepare("
            SELECT oi.*, m.image 
            FROM order_items oi
            LEFT JOIN menus m ON m.id = oi.menu_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.id ASC
        ");
        $itemStmt->execute($orderIds);
        while ($item = $itemStmt->fetch()) {
            $itemsByOrder[$item['order_id']][] = [
                'id'          => (int)$item['id'],
                'menu_name'   => $item['menu_name'],
                'unit_price'  => (float)$item['unit_price'],
                'quantity'    => (int)$item['quantity'],
                'spicy_level' => $item['spicy_level'] ? (int)$item['spicy_level'] : null,
                'spicy_label' => $item['spicy_level'] ? (SPICY_LEVELS[(int)$item['spicy_level']] ?? null) : null,
                'item_notes'  => $item['item_notes'],
                'subtotal'    => (float)$item['subtotal'],
                'image'       => $item['image'] ?: 'assets/images/logo.svg'
            ];
        }
    }

    // Format output
    $formatted = array_map(function($o) use ($itemsByOrder) {
        $oid = (int)$o['id'];
        return [
            'id'              => $oid,
            'order_code'      => $o['order_code'],
            'customer_name'   => $o['customer_name'],
            'order_type'      => $o['order_type'],
            'order_type_label'=> $o['order_type'] === 'dine_in' ? 'Makan di Tempat' : 'Take Away (Bungkus)',
            'table_number'    => $o['table_number'],
            'status'          => $o['status'],
            'status_label'    => ORDER_STATUS_LABELS[$o['status']]['label'] ?? ucfirst($o['status']),
            'status_badge'    => ORDER_STATUS_LABELS[$o['status']]['badge'] ?? 'badge-unpaid',
            'payment_status'  => $o['payment_status'],
            'payment_method'  => $o['payment_method'],
            'total'           => (float)$o['total'],
            'formatted_total' => format_rupiah($o['total']),
            'notes'           => $o['notes'],
            'created_at'      => $o['created_at'],
            'formatted_time'  => format_datetime($o['created_at']),
            'paid_at'         => $o['paid_at'] ? format_datetime($o['paid_at']) : null,
            'processing_at'   => $o['processing_at'] ? format_datetime($o['processing_at']) : null,
            'completed_at'    => $o['completed_at'] ? format_datetime($o['completed_at']) : null,
            'cancelled_at'    => $o['cancelled_at'] ? format_datetime($o['cancelled_at']) : null,
            'has_reviewed'    => !empty($o['review_id']),
            'review'          => !empty($o['review_id']) ? [
                'rating'  => (int)$o['rating'],
                'comment' => $o['review_comment']
            ] : null,
            'items'           => $itemsByOrder[$oid] ?? []
        ];
    }, $orders);

    json_response(true, 'Data pesanan berhasil diambil', [
        'orders' => $formatted,
        'count'  => count($formatted)
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal memuat pesanan: ' . $e->getMessage(), null, 500);
}
