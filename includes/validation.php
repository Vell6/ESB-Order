<?php
/**
 * RM bu ani - Input Validation and Sanitization Layer
 */

/**
 * Sanitize regular string input
 */
function sanitize_string(?string $val): string {
    if ($val === null) return '';
    return trim(strip_tags($val));
}

/**
 * Validate email format
 */
function validate_email(string $email): bool {
    return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
}

/**
 * Validate password requirements (min 8 characters)
 */
function validate_password(string $password): bool {
    return mb_strlen($password) >= 8;
}

/**
 * Validate phone number format (Indonesian format 08... or +62...)
 */
function validate_phone(string $phone): bool {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 9 && strlen($phone) <= 15;
}

/**
 * Validate spicy level (1 to 5 or null)
 */
function validate_spicy_level(?int $level): bool {
    if ($level === null) return true;
    return $level >= 1 && $level <= 5;
}

/**
 * Validate order type
 */
function validate_order_type(string $type): bool {
    return in_array($type, ['dine_in', 'takeaway'], true);
}

/**
 * Format and sanitize table number
 */
function sanitize_table_number(?string $table): ?string {
    if ($table === null) return null;
    $table = trim($table);
    if ($table === '') return null;
    // Allow alphanumeric characters (e.g. "01", "Meja 1", "T-05", "VIP 2")
    return substr(preg_replace('/[^a-zA-Z0-9\s\-]/', '', $table), 0, 20);
}
