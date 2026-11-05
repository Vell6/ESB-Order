<?php
/**
 * RM bu ani - API: Cashier Payment Confirmation
 * Uses database transaction and row locking to prevent double payments
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode request tidak diizinkan', null, 405);
}

require_role(['admin', 'cashier']);
require_csrf();

$currentUser = current_user();
$staffId = (int)$currentUser['id'];

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$orderCode = trim($input['order_code'] ?? '');
$cashReceived = isset($input['cash_received']) ? (float)$input['cash_received'] : null;
$paymentMethod = trim($input['payment_method'] ?? 'Tunai');

if (empty($orderCode)) {
    json_response(false, 'Order code tidak valid', null, 400);
}

try {
    $db = getDB();
    $db->beginTransaction();

    // 1. Fetch order with exclusive row lock FOR UPDATE
    $stmt = $db->prepare("
        SELECT id, order_code, customer_name, total, status, payment_status 
        FROM orders 
        WHERE order_code = :code 
        FOR UPDATE
    ");
    $stmt->execute([':code' => $orderCode]);
    $order = $stmt->fetch();

    if (!$order) {
        $db->rollBack();
        json_response(false, 'Pesanan tidak ditemukan', null, 404);
    }

    // 2. Prevent paying cancelled orders (PRD Section 14.3 TC-21)
    if ($order['status'] === 'cancelled') {
        $db->rollBack();
        json_response(false, 'Pesanan ini telah dibatalkan dan tidak dapat dibayar.', null, 409);
    }

    // 3. Prevent double payments (PRD Section 6.5 & TC-19)
    if ($order['payment_status'] === 'paid' || $order['status'] !== 'unpaid') {
        $db->rollBack();
        json_response(false, 'Pembayaran ditolak: Pesanan ini sudah pernah dibayar sebelumnya.', null, 409);
    }

    $total = (float)$order['total'];
    $change = 0.00;

    // Check cash received if provided
    if ($cashReceived !== null) {
        if ($cashReceived < $total) {
            $db->rollBack();
            json_response(false, 'Jumlah uang yang diterima kurang dari total pembayaran (' . format_rupiah($total) . ')', null, 422);
        }
        $change = $cashReceived - $total;
    }

    // 4. Update order to paid & processing
    $updateStmt = $db->prepare("
        UPDATE orders 
        SET payment_status = 'paid',
            status = 'processing',
            payment_method = :payment_method,
            paid_at = NOW(),
            processing_at = NOW(),
            updated_at = NOW()
        WHERE id = :id AND payment_status = 'unpaid'
    ");
    $updateStmt->execute([
        ':payment_method' => $paymentMethod,
        ':id'             => $order['id']
    ]);

    // 5. Audit log
    $logNote = "Pembayaran dikonfirmasi oleh {$currentUser['name']} (" . ucfirst($currentUser['role']) . ")";
    if ($cashReceived !== null) {
        $logNote .= " [Uang: " . format_rupiah($cashReceived) . ", Kembali: " . format_rupiah($change) . "]";
    }
    log_order_status((int)$order['id'], 'unpaid', 'processing', $staffId, $logNote);

    // 6. Commit transaction
    $db->commit();

    json_response(true, 'Pembayaran berhasil dikonfirmasi! Pesanan telah diteruskan ke dapur.', [
        'order_code'        => $orderCode,
        'customer_name'     => $order['customer_name'],
        'total'             => $total,
        'formatted_total'   => format_rupiah($total),
        'cash_received'     => $cashReceived,
        'formatted_cash'    => $cashReceived !== null ? format_rupiah($cashReceived) : null,
        'change'            => $change,
        'formatted_change'  => format_rupiah($change),
        'status'            => 'processing',
        'payment_status'    => 'paid',
        'receipt_url'       => BASE_URL . 'admin/receipt.php?code=' . urlencode($orderCode)
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    json_response(false, 'Gagal memproses pembayaran: ' . $e->getMessage(), null, 500);
}
