<?php
/**
 * RM bu ani - API: Menu List
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$category = trim($_GET['category'] ?? '');
$search = trim($_GET['search'] ?? '');
$includeUnavailable = !empty($_GET['include_unavailable']) && has_role('admin');

try {
    $db = getDB();

    $sql = "SELECT id, name, description, price, image, category, supports_spicy, is_available, created_at, updated_at FROM menus WHERE 1=1";
    $params = [];

    if (!$includeUnavailable) {
        $sql .= " AND is_available = 1";
    }

    if (!empty($category) && $category !== 'Semua') {
        $sql .= " AND category = :category";
        $params[':category'] = $category;
    }

    if (!empty($search)) {
        $sql .= " AND (name LIKE :search OR description LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY category ASC, id ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $menus = $stmt->fetchAll();

    // Grouping and category metadata
    $categories = $db->query("SELECT DISTINCT category FROM menus WHERE is_available = 1 ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

    $formattedMenus = array_map(function($m) {
        return [
            'id'             => (int)$m['id'],
            'name'           => $m['name'],
            'description'    => $m['description'],
            'price'          => (float)$m['price'],
            'formatted_price'=> format_rupiah($m['price']),
            'image'          => $m['image'] ?: 'assets/images/logo.svg',
            'category'       => $m['category'],
            'supports_spicy' => (bool)$m['supports_spicy'],
            'is_available'   => (bool)$m['is_available']
        ];
    }, $menus);

    json_response(true, 'Daftar menu berhasil diambil', [
        'categories' => $categories,
        'menus'      => $formattedMenus,
        'total'      => count($formattedMenus)
    ]);
} catch (Exception $e) {
    json_response(false, 'Gagal mengambil daftar menu: ' . $e->getMessage(), null, 500);
}
