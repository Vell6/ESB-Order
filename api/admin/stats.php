<?php
/**
 * RM bu ani - API: Admin Dashboard Statistics
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role(['admin', 'cashier']);

try {
    $db = getDB();

    // 1. Revenue Today (only paid transactions)
    $stmtToday = $db->query("
        SELECT COALESCE(SUM(total), 0) FROM orders 
        WHERE payment_status = 'paid' 
          AND status != 'cancelled' 
          AND DATE(paid_at) = CURDATE()
    ");
    $revenueToday = (float)$stmtToday->fetchColumn();

    // 2. Revenue This Month
    $stmtMonth = $db->query("
        SELECT COALESCE(SUM(total), 0) FROM orders 
        WHERE payment_status = 'paid' 
          AND status != 'cancelled' 
          AND YEAR(paid_at) = YEAR(CURDATE()) 
          AND MONTH(paid_at) = MONTH(CURDATE())
    ");
    $revenueMonth = (float)$stmtMonth->fetchColumn();

    // 3. Counts by status
    $countsStmt = $db->query("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) as unpaid_count,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_count,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
        FROM orders
    ");
    $counts = $countsStmt->fetch();

    // 4. Menu count
    $menuCount = (int)$db->query("SELECT COUNT(*) FROM menus WHERE is_available = 1")->fetchColumn();

    json_response(true, 'Statistik dashboard', [
        'revenue_today'           => $revenueToday,
        'formatted_revenue_today' => format_rupiah($revenueToday),
        'revenue_month'           => $revenueMonth,
        'formatted_revenue_month' => format_rupiah($revenueMonth),
        'total_orders'            => (int)($counts['total_orders'] ?? 0),
        'unpaid_orders'           => (int)($counts['unpaid_count'] ?? 0),
        'processing_orders'       => (int)($counts['processing_count'] ?? 0),
        'completed_orders'        => (int)($counts['completed_count'] ?? 0),
        'cancelled_orders'        => (int)($counts['cancelled_count'] ?? 0),
        'active_menus'            => $menuCount
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil statistik: ' . $e->getMessage(), null, 500);
}
