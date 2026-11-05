<?php
/**
 * RM bu ani - View Helpers & Shortcuts
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

/**
 * Escape text for safe HTML output
 */
if (!function_exists('e')) {
    function e(?string $text): string {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Get active navigation class
 */
function active_nav(string $path): string {
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return ($current === $path) ? 'active' : '';
}

/**
 * Get available predefined tables list
 */
function get_restaurant_tables(): array {
    $tables = [];
    for ($i = 1; $i <= 20; $i++) {
        $tables[] = sprintf('%02d', $i);
    }
    return $tables;
}

/**
 * Get current table number saved in session
 */
function get_session_table(): ?string {
    return $_SESSION['selected_table'] ?? null;
}

/**
 * Get current order type saved in session (dine_in vs takeaway)
 */
function get_session_order_type(): string {
    return $_SESSION['order_type'] ?? 'dine_in';
}
