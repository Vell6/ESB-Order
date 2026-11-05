<?php
/**
 * RM bu ani - API: Current User Info
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user = current_user();

if (!$user) {
    json_response(false, 'Belum login', null, 401);
}

json_response(true, 'Data user aktif', [
    'user'            => $user,
    'selected_table'  => $_SESSION['selected_table'] ?? null,
    'order_type'      => $_SESSION['order_type'] ?? 'dine_in'
]);
