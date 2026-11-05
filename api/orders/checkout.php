<?php
/**
 * RM bu ani - API: Checkout Order
 * Creates a new order with atomic database transactions and server-side pricing recalculation
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/validation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Metode request tidak diizinkan', null, 405);
}

require_login();
require_csrf();

$currentUser = current_user();
$userId = (int)$currentUser['id'];

$raw = file_get_contents('php://input');
$input = json_decode($raw, true) ?? $_POST;

$customerName = trim($input['customer_name'] ?? $currentUser['name']);
$orderType = trim($input['order_type'] ?? 'dine_in');
$tableNumber = sanitize_table_number($input['table_number'] ?? null);
$orderNotes = trim($input['notes'] ?? '');
$items = $input['items'] ?? [];

// 1. Validate customer name
if (empty($customerName)) {
    json_response(false, 'Nama pemesan wajib diisi', null, 422);
}

// 2. Validate order type
if (!validate_order_type($orderType)) {
    json_response(false, 'Jenis pesanan tidak valid (harus dine_in atau takeaway)', null, 422);
}

// 3. Validate table number (Mandatory for dine_in, MUST be NULL for takeaway)
if ($orderType === 'dine_in') {
    if (empty($tableNumber)) {
        json_response(false, 'Nomor meja wajib diisi untuk pesanan Makan di Tempat', null, 422);
    }
} else {
    $tableNumber = null;
}

// 4. Validate items array
if (!is_array($items) || count($items) === 0) {
    json_response(false, 'Keranjang pesanan masih kosong', null, 422);
}

try {
    $db = getDB();

    // 5. Fetch fresh prices and availability from database
    $menuIds = array_unique(array_filter(array_map(fn($it) => (int)($it['id'] ?? 0), $items)));
    if (empty($menuIds)) {
        json_response(false, 'Item keranjang tidak valid', null, 422);
    }

    $placeholders = implode(',', array_fill(0, count($menuIds), '?'));
    $menuQuery = $db->prepare("
        SELECT id, name, price, supports_spicy, is_available 
        FROM menus 
        WHERE id IN ($placeholders)
    ");
    $menuQuery->execute(array_values($menuIds));
    $dbMenus = [];
    while ($row = $menuQuery->fetch()) {
        $dbMenus[(int)$row['id']] = $row;
    }

    // 6. Verify each item and calculate server total
    $subtotal = 0.00;
    $validatedItems = [];

    foreach ($items as $it) {
        $menuId = (int)($it['id'] ?? 0);
        $qty = (int)($it['quantity'] ?? 1);
        $spicyLevel = isset($it['spicy_level']) && $it['spicy_level'] !== '' ? (int)$it['spicy_level'] : null;
        $itemNotes = substr(trim($it['notes'] ?? ''), 0, 255);

        if (!isset($dbMenus[$menuId])) {
            json_response(false, "Menu dengan ID {$menuId} tidak ditemukan", null, 404);
        }

        $dbMenu = $dbMenus[$menuId];

        // Ensure menu is still available
        if ((int)$dbMenu['is_available'] !== 1) {
            json_response(false, "Maaf, menu '{$dbMenu['name']}' saat ini sedang habis.", null, 409);
        }

        if ($qty < 1) {
            $qty = 1;
        }

        // Validate spicy level
        if (!$dbMenu['supports_spicy']) {
            $spicyLevel = null;
        } elseif ($spicyLevel !== null && ($spicyLevel < 1 || $spicyLevel > 5)) {
            $spicyLevel = 1;
        }

        $unitPrice = (float)$dbMenu['price'];
        $itemSubtotal = $unitPrice * $qty;
        $subtotal += $itemSubtotal;

        $validatedItems[] = [
            'menu_id'     => $menuId,
            'menu_name'   => $dbMenu['name'],
            'unit_price'  => $unitPrice,
            'quantity'    => $qty,
            'spicy_level' => $spicyLevel,
            'item_notes'  => $itemNotes ?: null,
            'subtotal'    => $itemSubtotal
        ];
    }

    $total = $subtotal; // In v1.0, subtotal equals total (no extra tax/service)

    // 7. Begin Database Transaction
    $db->beginTransaction();

    // 8. Generate unique order code (RMA-YYYYMMDD-XXXXXX)
    $orderCode = '';
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $candidate = generate_order_code();
        $chk = $db->prepare("SELECT id FROM orders WHERE order_code = :code LIMIT 1");
        $chk->execute([':code' => $candidate]);
        if (!$chk->fetch()) {
            $orderCode = $candidate;
            break;
        }
    }

    if (empty($orderCode)) {
        throw new Exception("Gagal menghasilkan Order ID unik. Silakan coba kembali.");
    }

    // 9. Insert orders record
    $insertOrder = $db->prepare("
        INSERT INTO orders (
            order_code, user_id, customer_name, order_type, table_number,
            status, payment_status, subtotal, total, notes, created_at, updated_at
        ) VALUES (
            :order_code, :user_id, :customer_name, :order_type, :table_number,
            'unpaid', 'unpaid', :subtotal, :total, :notes, NOW(), NOW()
        )
    ");
    $insertOrder->execute([
        ':order_code'     => $orderCode,
        ':user_id'        => $userId,
        ':customer_name'  => $customerName,
        ':order_type'     => $orderType,
        ':table_number'   => $tableNumber,
        ':subtotal'       => $subtotal,
        ':total'          => $total,
        ':notes'          => $orderNotes ?: null
    ]);

    $orderId = (int)$db->lastInsertId();

    // 10. Insert order_items records (snapshot of name & price)
    $insertItem = $db->prepare("
        INSERT INTO order_items (
            order_id, menu_id, menu_name, unit_price, quantity, spicy_level, item_notes, subtotal, created_at
        ) VALUES (
            :order_id, :menu_id, :menu_name, :unit_price, :quantity, :spicy_level, :item_notes, :subtotal, NOW()
        )
    ");

    foreach ($validatedItems as $vItem) {
        $insertItem->execute([
            ':order_id'    => $orderId,
            ':menu_id'     => $vItem['menu_id'],
            ':menu_name'   => $vItem['menu_name'],
            ':unit_price'  => $vItem['unit_price'],
            ':quantity'    => $vItem['quantity'],
            ':spicy_level' => $vItem['spicy_level'],
            ':item_notes'  => $vItem['item_notes'],
            ':subtotal'    => $vItem['subtotal']
        ]);
    }

    // 11. Log order status creation
    log_order_status($orderId, null, 'unpaid', $userId, 'Pesanan dibuat oleh pelanggan');

    // 12. Commit transaction
    $db->commit();

    json_response(true, 'Pesanan berhasil dibuat! Silakan lakukan pembayaran di kasir.', [
        'order_id'         => $orderId,
        'order_code'       => $orderCode,
        'customer_name'    => $customerName,
        'order_type'       => $orderType,
        'table_number'     => $tableNumber,
        'total'            => $total,
        'formatted_total'  => format_rupiah($total),
        'status'           => 'unpaid',
        'payment_status'   => 'unpaid',
        'detail_url'       => BASE_URL . 'order-detail.php?code=' . urlencode($orderCode)
    ], 201);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    json_response(false, 'Gagal memproses pesanan: ' . $e->getMessage(), null, 500);
}
