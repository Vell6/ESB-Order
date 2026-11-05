<?php
/**
 * RM bu ani - Core Business Functions & Utilities
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';

/**
 * Generate cryptographically secure unique Order Code
 * Format: RMA-YYYYMMDD-XXXXXX (e.g. RMA-20261104-A8K29F)
 */
function generate_order_code(): string {
    $datePart = date('Ymd');
    // Generate 6 uppercase alphanumeric characters
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Exclude ambiguous chars like 0, 1, I, O
    $randomPart = '';
    $maxIndex = strlen($chars) - 1;
    for ($i = 0; $i < 6; $i++) {
        $randomPart .= $chars[random_int(0, $maxIndex)];
    }
    return 'RMA-' . $datePart . '-' . $randomPart;
}

/**
 * Format currency to Rupiah string (e.g. Rp 28.000)
 */
function format_rupiah(float|int|string $amount, bool $showPrefix = true): string {
    $num = (float)$amount;
    $formatted = number_format($num, 0, ',', '.');
    return $showPrefix ? 'Rp ' . $formatted : $formatted;
}

/**
 * Format timestamp to readable Indonesian format
 */
function format_datetime(?string $datetime, bool $showTime = true): string {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    if (!$time) return '-';

    $months = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];

    $d = date('j', $time);
    $m = $months[(int)date('n', $time)];
    $y = date('Y', $time);

    if ($showTime) {
        return sprintf('%s %s %s, %s WIB', $d, $m, $y, date('H:i', $time));
    }
    return sprintf('%s %s %s', $d, $m, $y);
}

/**
 * Log order status transitions for complete auditability
 */
function log_order_status(int $orderId, ?string $oldStatus, string $newStatus, ?int $changedBy = null, ?string $notes = null): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO order_status_logs (order_id, old_status, new_status, changed_by, notes, created_at)
            VALUES (:order_id, :old_status, :new_status, :changed_by, :notes, NOW())
        ");
        return $stmt->execute([
            ':order_id'   => $orderId,
            ':old_status' => $oldStatus,
            ':new_status' => $newStatus,
            ':changed_by' => $changedBy,
            ':notes'      => $notes
        ]);
    } catch (Exception $e) {
        error_log("Failed to log order status change: " . $e->getMessage());
        return false;
    }
}

/**
 * Return JSON response and exit
 */
function json_response(bool $success, string $message, mixed $data = null, int $httpCode = 200, array $errors = []): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    
    $payload = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $payload['data'] = $data;
    }
    if (!empty($errors)) {
        $payload['errors'] = $errors;
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Helper to render an HTML badge for order status
 */
function render_status_badge(string $status): string {
    $info = ORDER_STATUS_LABELS[$status] ?? ['label' => ucfirst($status), 'badge' => 'badge-unpaid'];
    return '<span class="sy-badge ' . htmlspecialchars($info['badge']) . '">' . htmlspecialchars($info['label']) . '</span>';
}
