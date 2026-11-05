<?php
/**
 * RM bu ani - Admin Portal Entry Point
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

require_login('login.php');

$role = user_role();

if ($role === 'kitchen') {
    header("Location: " . BASE_URL . "admin/kitchen.php");
} elseif ($role === 'cashier') {
    header("Location: " . BASE_URL . "admin/orders.php");
} elseif ($role === 'admin') {
    header("Location: " . BASE_URL . "admin/dashboard.php");
} else {
    // Customer
    header("Location: " . BASE_URL . "menu.php");
}
exit;
