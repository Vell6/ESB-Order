<?php
/**
 * RM bu ani - Logout Handler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

logout_user();
header("Location: " . BASE_URL . "index.php?msg=logged_out");
exit;
