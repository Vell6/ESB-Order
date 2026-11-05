<?php
/**
 * RM bu ani - API: Cart Validation
 * Validates local cart items against database availability & prices
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;
$items = $input['items'] ?? [];

if (!is_array($items) || count($items) === 0) {
    json_response(true, 'Keranjang kosong', [
        'valid'       => true,
        'items'       => [],
        'total'       => 0,
        'item_count'  => 0
    ]);
}

try {
    $db = getDB();
    $menuIds = array_unique(array_filter(array_map(fn($it) => (int)($it['id'] ?? 0), $items)));

    if (empty($menuIds)) {
        json_response(false, 'Format item tidak valid', null, 422);
    }

    $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
    $stmt = $db->prepare("SELECT id, name, price, supports_spicy, is_available FROM menus WHERE id IN ($placeholders)");
    $stmt->execute(array_values($menuIds));
    $dbMenus = [];
    while ($m = $stmt->fetch()) {
        $dbMenus[(int)$m['id']] = $m;
    }

    $validated = [];
    $total = 0.00;
    $warnings = [];

    foreach ($items as $it) {
        $mid = (int)($it['id'] ?? 0);
        $qty = max(1, (int)($it['quantity'] ?? 1));
        $spicy = isset($it['spicy_level']) && $it['spicy_level'] !== '' ? (int)$it['spicy_level'] : null;
        $notes = trim($it['notes'] ?? '');

        if (!isset($dbMenus[$mid])) {
            $warnings[] = "Item dengan ID {$mid} tidak lagi tersedia dan telah dihapus.";
            continue;
        }

        $dbMenu = $dbMenus[$mid];
        if ((int)$dbMenu['is_available'] !== 1) {
            $warnings[] = "Menu '{$dbMenu['name']}' saat ini sedang habis.";
            continue;
        }

        if (!$dbMenu['supports_spicy']) {
            $spicy = null;
        }

        $price = (float)$dbMenu['price'];
        $subtotal = $price * $qty;
        $total += $subtotal;

        $validated[] = [
            'id'          => $mid,
            'name'        => $dbMenu['name'],
            'price'       => $price,
            'quantity'    => $qty,
            'spicy_level' => $spicy,
            'notes'       => $notes,
            'subtotal'    => $subtotal
        ];
    }

    json_response(true, 'Keranjang berhasil divalidasi', [
        'valid'           => count($warnings) === 0,
        'items'           => $validated,
        'total'           => $total,
        'formatted_total' => format_rupiah($total),
        'warnings'        => $warnings
    ]);
} catch (Exception $e) {
    json_response(false, 'Validasi keranjang gagal: ' . $e->getMessage(), null, 500);
}
