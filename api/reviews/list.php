<?php
/**
 * RM bu ani - API: Reviews List
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

try {
    $db = getDB();

    $stmt = $db->query("
        SELECT r.id, r.rating, r.comment, r.created_at, u.name as customer_name,
               o.order_code
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        JOIN orders o ON o.id = r.order_id
        ORDER BY r.id DESC
        LIMIT 20
    ");
    $reviews = $stmt->fetchAll();

    $avgRating = (float)$db->query("SELECT COALESCE(AVG(rating), 5) FROM reviews")->fetchColumn();
    $totalReviews = (int)$db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

    $formatted = array_map(function($r) {
        return [
            'id'             => (int)$r['id'],
            'rating'         => (int)$r['rating'],
            'comment'        => $r['comment'],
            'customer_name'  => $r['customer_name'],
            'order_code'     => $r['order_code'],
            'created_at'     => $r['created_at'],
            'formatted_time' => format_datetime($r['created_at'], false)
        ];
    }, $reviews);

    json_response(true, 'Daftar ulasan pelanggan', [
        'average_rating' => round($avgRating, 1),
        'total_reviews'  => $totalReviews,
        'reviews'        => $formatted
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil ulasan: ' . $e->getMessage(), null, 500);
}
