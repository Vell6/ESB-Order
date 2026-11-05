<?php
/**
 * RM bu ani - API: Logout
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

logout_user();

if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    json_response(true, 'Anda telah berhasil logout', ['redirect' => BASE_URL . 'index.php']);
} else {
    header("Location: " . BASE_URL . "index.php?msg=logged_out");
    exit;
}
