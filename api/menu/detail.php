<?php
/**
 * RM bu ani - API: Menu Detail
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    json_response(false, 'ID menu tidak valid', null, 400);
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, description, price, image, category, supports_spicy, is_available FROM menus WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $menu = $stmt->fetch();

    if (!$menu) {
        json_response(false, 'Menu tidak ditemukan', null, 404);
    }

    $data = [
        'id'             => (int)$menu['id'],
        'name'           => $menu['name'],
        'description'    => $menu['description'],
        'price'          => (float)$menu['price'],
        'formatted_price'=> format_rupiah($menu['price']),
        'image'          => $menu['image'] ?: 'assets/images/logo.svg',
        'category'       => $menu['category'],
        'supports_spicy' => (bool)$menu['supports_spicy'],
        'spicy_levels'   => (bool)$menu['supports_spicy'] ? SPICY_LEVELS : [],
        'is_available'   => (bool)$menu['is_available']
    ];

    json_response(true, 'Detail menu', $data);
} catch (Exception $e) {
    json_response(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
