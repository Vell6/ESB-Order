<?php
/**
 * RM bu ani - API: Admin & Cashier Orders List
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin', 'cashier']);

$status = trim($_GET['status'] ?? 'unpaid');
$search = trim($_GET['search'] ?? '');

try {
    $db = getDB();

    $sql = "
        SELECT o.*, u.email as user_email, u.phone as user_phone,
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as total_items
        FROM orders o
        JOIN users u ON u.id = o.user_id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($status) && in_array($status, ['unpaid', 'processing', 'completed', 'cancelled'])) {
        $sql .= " AND o.status = :status";
        $params[':status'] = $status;
    }

    if (!empty($search)) {
        $sql .= " AND (o.order_code LIKE :search OR o.customer_name LIKE :search OR o.table_number LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY o.id DESC LIMIT 100";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    $formatted = array_map(function($o) {
        return [
            'id'              => (int)$o['id'],
            'order_code'      => $o['order_code'],
            'customer_name'   => $o['customer_name'],
            'customer_phone'  => $o['user_phone'],
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
            'total_items'     => (int)$o['total_items'],
            'notes'           => $o['notes'],
            'created_at'      => $o['created_at'],
            'formatted_time'  => format_datetime($o['created_at']),
            'paid_at'         => $o['paid_at'] ? format_datetime($o['paid_at']) : null,
            'processing_at'   => $o['processing_at'] ? format_datetime($o['processing_at']) : null,
            'completed_at'    => $o['completed_at'] ? format_datetime($o['completed_at']) : null
        ];
    }, $orders);

    json_response(true, 'Daftar pesanan admin/kasir', [
        'orders' => $formatted,
        'count'  => count($formatted)
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil data pesanan: ' . $e->getMessage(), null, 500);
}
