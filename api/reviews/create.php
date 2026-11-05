<?php
/**
 * RM bu ani - API: Submit Review
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode request tidak diizinkan', null, 405);
}

require_login();
require_csrf();

$currentUser = current_user();
$userId = (int)$currentUser['id'];

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$orderCode = trim($input['order_code'] ?? '');
$rating = (int)($input['rating'] ?? 0);
$comment = trim($input['comment'] ?? '');

if (empty($orderCode)) {
    json_response(false, 'Order code tidak valid', null, 400);
}
if ($rating < 1 || $rating > 5) {
    json_response(false, 'Rating harus bernilai antara 1 sampai 5 bintang', null, 422);
}

try {
    $db = getDB();

    // Check order ownership and completion
    $stmt = $db->prepare("
        SELECT id, user_id, status 
        FROM orders 
        WHERE order_code = :code AND user_id = :user_id 
        LIMIT 1
    ");
    $stmt->execute([':code' => $orderCode, ':user_id' => $userId]);
    $order = $stmt->fetch();

    if (!$order) {
        json_response(false, 'Pesanan tidak ditemukan atau bukan milik Anda', null, 404);
    }

    if ($order['status'] !== 'completed') {
        json_response(false, 'Hanya pesanan yang sudah berstatus Selesai yang dapat diberi ulasan.', null, 409);
    }

    $orderId = (int)$order['id'];

    // Check if review already exists
    $revCheck = $db->prepare("SELECT id FROM reviews WHERE order_id = :order_id");
    $revCheck->execute([':order_id' => $orderId]);
    if ($revCheck->fetch()) {
        json_response(false, 'Anda sudah memberikan ulasan untuk pesanan ini.', null, 409);
    }

    // Insert review
    $ins = $db->prepare("
        INSERT INTO reviews (order_id, user_id, rating, comment, created_at, updated_at)
        VALUES (:order_id, :user_id, :rating, :comment, NOW(), NOW())
    ");
    $ins->execute([
        ':order_id' => $orderId,
        ':user_id'  => $userId,
        ':rating'   => $rating,
        ':comment'  => $comment ?: null
    ]);

    json_response(true, 'Terima kasih atas ulasan dan penilaian Anda!', [
        'order_code' => $orderCode,
        'rating'     => $rating
    ], 201);
} catch (Exception $e) {
    json_response(false, 'Gagal menyimpan ulasan: ' . $e->getMessage(), null, 500);
}
