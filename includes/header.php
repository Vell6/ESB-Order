<?php
/**
 * RM bu ani - Common HTML Header
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME . ' | ' . APP_TAGLINE;
$csrfToken = get_csrf_token();
$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Sistem Pemesanan Makanan & POS Restoran Modern RM bu ani">
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <meta name="base-url" content="<?= e(BASE_URL) ?>">

  <!-- Google Fonts: Poppins (400, 500, 600, 700) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Master Centralized Theme CSS -->
  <link rel="stylesheet" href="<?= BASE_URL ?>theme.css?v=<?= time() ?>">
</head>
<body class="<?= isset($bodyClass) ? e($bodyClass) : '' ?>">
