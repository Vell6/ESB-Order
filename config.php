<?php
/**
 * RM bu ani - Online Restaurant Ordering & Point of Sale System
 * Central Configuration File
 */

// 1. Error Reporting (Development Mode: set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. Set Timezone Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// Global HTML Escape Helper
if (!function_exists('e')) {
    function e(?string $text): string {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// 3. Database Credentials
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_PORT') or define('DB_PORT', '3306');
defined('DB_NAME') or define('DB_NAME', 'db_restoran');
defined('DB_USER') or define('DB_USER', 'root');
defined('DB_PASS') or define('DB_PASS', '');

// 4. Application Details
defined('APP_NAME') or define('APP_NAME', 'RM bu ani');
defined('APP_TAGLINE') or define('APP_TAGLINE', 'Online Restaurant Ordering & Point of Sale System');
defined('APP_ADDRESS') or define('APP_ADDRESS', 'Jl. Kenanga No. 25, Pontianak - Kalimantan Barat');
defined('APP_PHONE') or define('APP_PHONE', '0812-3456-7890');
defined('APP_OPENING_HOURS') or define('APP_OPENING_HOURS', '09:00 - 22:00 WIB');

// 5. Dynamic Base URL Detection
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    
    // Calculate web root relative to script execution
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalize if running inside /admin, /api, etc.
    $parts = explode('/', trim($scriptDir, '/'));
    $cleanPath = '';
    
    // Filter out sub-sub directories like api/..., admin/...
    $appFolderNames = ['restoran', 'esb order', 'esb-order', 'rmbuani'];
    $found = false;
    foreach ($parts as $p) {
        if ($p === '') continue;
        if (in_array(strtolower($p), ['admin', 'api', 'includes', 'assets'])) {
            break;
        }
        $cleanPath .= '/' . $p;
        if (in_array(strtolower(urldecode($p)), $appFolderNames)) {
            $found = true;
            break;
        }
    }
    
    $cleanPath = rtrim($cleanPath, '/');
    $baseUrl = $protocol . $host . ($cleanPath ? $cleanPath . '/' : '/');
    define('BASE_URL', $baseUrl);
}

// 6. Spicy Level Mapping
const SPICY_LEVELS = [
    1 => 'Tidak Pedas (Level 1)',
    2 => 'Sedikit Pedas (Level 2)',
    3 => 'Sedang (Level 3)',
    4 => 'Pedas (Level 4)',
    5 => 'Sangat Pedas (Level 5)'
];

// 7. Order Status Definitions
const ORDER_STATUS_LABELS = [
    'unpaid'     => ['label' => 'Belum Dibayar', 'badge' => 'badge-unpaid', 'color' => '#f4a261'],
    'processing' => ['label' => 'Diproses',      'badge' => 'badge-processing', 'color' => '#2a9d8f'],
    'completed'  => ['label' => 'Selesai',       'badge' => 'badge-completed', 'color' => '#2eaf62'],
    'cancelled'  => ['label' => 'Dibatalkan',    'badge' => 'badge-cancelled', 'color' => '#d62828']
];

// 8. Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    // Only send cookies over HTTP, prevent JS access
    $cookieParams = [
        'lifetime' => 86400, // 24 hours
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    session_set_cookie_params($cookieParams);
    session_start();
}
