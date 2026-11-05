<?php
/**
 * RM bu ani - Admin Order Detail Redirector
 */
require_once __DIR__ . '/../config.php';
$code = $_GET['code'] ?? '';
header("Location: " . BASE_URL . "order-detail.php?code=" . urlencode($code));
exit;
