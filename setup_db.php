<?php
/**
 * RM bu ani - Database Setup & Installer
 * Idempotent installer: runs safely on fresh installs and updates
 */

require_once __DIR__ . '/config.php';

// Safe HTML escape helper
if (!function_exists('e')) {
    function e(?string $text): string {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$isCli = (php_sapi_name() === 'cli');
$installedFlagFile = __DIR__ . '/uploads/.installed';
$alreadyInstalled = file_exists($installedFlagFile);

$message = '';
$error = '';
$success = false;

// Handle form submission or CLI execution
if ($isCli || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_install']))) {
    $dbHost = $_POST['db_host'] ?? DB_HOST;
    $dbPort = $_POST['db_port'] ?? DB_PORT;
    $dbName = $_POST['db_name'] ?? DB_NAME;
    $dbUser = $_POST['db_user'] ?? DB_USER;
    $dbPass = $_POST['db_pass'] ?? DB_PASS;
    
    $adminEmail = trim($_POST['admin_email'] ?? 'admin@rmbuani.com');
    $adminPassword = $_POST['admin_password'] ?? 'password123';
    $seedSampleData = !empty($_POST['seed_data']) || $isCli;

    try {
        // Step 1: Connect to MySQL server without database
        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Step 2: Create Database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // Step 3: Run Schema SQL
        $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
        $pdo->exec($schemaSql);

        // Step 4: Create or update Admin User
        $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $checkAdmin = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $checkAdmin->execute([':email' => $adminEmail]);
        if ($checkAdmin->fetch()) {
            $updateAdmin = $pdo->prepare("UPDATE users SET password = :password, role = 'admin', is_active = 1 WHERE email = :email");
            $updateAdmin->execute([':password' => $adminHash, ':email' => $adminEmail]);
        } else {
            $insertAdmin = $pdo->prepare("
                INSERT INTO users (name, email, password, phone, role, is_active, created_at, updated_at)
                VALUES ('Administrator RM bu ani', :email, :password, '081234567890', 'admin', 1, NOW(), NOW())
            ");
            $insertAdmin->execute([':email' => $adminEmail, ':password' => $adminHash]);
        }

        // Step 5: Create default Staff accounts (Kasir, Kitchen, Customer) if not existing
        $defaultStaff = [
            ['name' => 'Kasir RM bu ani', 'email' => 'kasir@rmbuani.com', 'role' => 'cashier', 'phone' => '081234567891'],
            ['name' => 'Staf Dapur Bu Ani', 'email' => 'dapur@rmbuani.com', 'role' => 'kitchen', 'phone' => '081234567892'],
            ['name' => 'Marvel (Pelanggan)', 'email' => 'marvelmarvel3622@gmail.com', 'role' => 'customer', 'phone' => '083102315194']
        ];

        $defaultPassHash = password_hash('password123', PASSWORD_DEFAULT);
        foreach ($defaultStaff as $staff) {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $check->execute([':email' => $staff['email']]);
            if (!$check->fetch()) {
                $ins = $pdo->prepare("
                    INSERT INTO users (name, email, password, phone, role, is_active, created_at, updated_at)
                    VALUES (:name, :email, :password, :phone, :role, 1, NOW(), NOW())
                ");
                $ins->execute([
                    ':name'     => $staff['name'],
                    ':email'    => $staff['email'],
                    ':password' => $defaultPassHash,
                    ':phone'    => $staff['phone'],
                    ':role'     => $staff['role']
                ]);
            }
        }

        // Step 6: Seed initial authentic menus if requested and menus table is empty
        if ($seedSampleData) {
            $menuCount = (int)$pdo->query("SELECT COUNT(*) FROM menus")->fetchColumn();
            if ($menuCount === 0) {
                $seedSql = file_get_contents(__DIR__ . '/database/seed.sql');
                $pdo->exec($seedSql);
            }
        }

        // Step 7: Create installation flag file
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0755, true);
        }
        file_put_contents($installedFlagFile, date('Y-m-d H:i:s'));

        $success = true;
        $message = "Instalasi Database '{$dbName}' berhasil diselesaikan secara sempurna!";

        if ($isCli) {
            echo "\n[OK] " . $message . "\n";
            exit(0);
        }
    } catch (Exception $e) {
        $error = "Terjadi kesalahan saat instalasi: " . $e->getMessage();
        if ($isCli) {
            echo "\n[ERROR] " . $error . "\n";
            exit(1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Instalasi Database - <?= APP_NAME ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="theme.css">
  <style>
    body {
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }
    .setup-box {
      background: #ffffff;
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 580px;
      overflow: hidden;
      border: 1px solid var(--border);
    }
    .setup-header {
      background: var(--primary);
      color: #ffffff;
      padding: 2rem;
      text-align: center;
    }
    .setup-header h1 {
      color: #ffffff;
      font-size: 1.5rem;
      margin-bottom: 0.35rem;
    }
    .setup-body {
      padding: 2rem;
    }
    .account-preview {
      background-color: #f8fafc;
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 1rem;
      margin-top: 1.25rem;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>

<div class="setup-box">
  <div class="setup-header">
    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">
      <i class="fa-solid fa-utensils"></i>
    </div>
    <h1><?= APP_NAME ?></h1>
    <p style="color: rgba(255,255,255,0.85); font-size: 0.9rem;">Setup & Instalasi Database Terintegrasi</p>
  </div>

  <div class="setup-body">
    <?php if ($success): ?>
      <div style="background-color: var(--success-light); border: 1px solid #bce6cb; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; color: #1e7441;">
        <h4 style="color:#1e7441; margin-bottom:0.5rem; font-weight:700;"><i class="fa-solid fa-circle-check"></i> Instalasi Selesai!</h4>
        <p style="color:#1e7441; font-size:0.875rem; margin-bottom:0.75rem;"><?= e($message) ?></p>
        <p style="font-size:0.85rem;">Tabel <strong>users, menus, orders, order_items, reviews, order_status_logs</strong> berhasil dibuat dan diinisialisasi.</p>
      </div>

      <div class="account-preview">
        <h4 style="font-weight:700; margin-bottom:0.5rem;"><i class="fa-solid fa-key"></i> Kredensial Login Pengujian:</h4>
        <ul style="line-height: 1.8;">
          <li><strong>Admin:</strong> <code>admin@rmbuani.com</code> / <code>password123</code></li>
          <li><strong>Kasir:</strong> <code>kasir@rmbuani.com</code> / <code>password123</code></li>
          <li><strong>Dapur:</strong> <code>dapur@rmbuani.com</code> / <code>password123</code></li>
          <li><strong>Pelanggan:</strong> <code>marvelmarvel3622@gmail.com</code> / <code>password123</code></li>
        </ul>
      </div>

      <div style="display:flex; flex-direction:column; gap:0.75rem; margin-top:1.5rem;">
        <a href="<?= BASE_URL ?>index.php" class="sy-btn sy-btn-primary sy-btn-block">
          <i class="fa-solid fa-utensils"></i> Buka Menu Pemesanan Pelanggan
        </a>
        <a href="<?= BASE_URL ?>login.php" class="sy-btn sy-btn-outline sy-btn-block">
          <i class="fa-solid fa-right-to-bracket"></i> Masuk ke Portal Staff (Kasir / Dapur / Admin)
        </a>
      </div>

    <?php else: ?>
      <?php if ($error): ?>
        <div style="background-color: var(--danger-light); border: 1px solid #f6bebe; border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem; color: var(--danger);">
          <i class="fa-solid fa-triangle-exclamation"></i> <?= e($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($alreadyInstalled): ?>
        <div style="background-color: var(--warning-light); border: 1px solid #f9d8b8; border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.85rem; color: #a05007;">
          <i class="fa-solid fa-circle-info"></i> Aplikasi telah pernah diinstal. Menjalankan ulang instalasi bersifat aman dan idempoten (tidak akan menghapus pesanan transaksi yang sudah ada).
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <h3 style="font-size:1.05rem; font-weight:700; margin-bottom:1rem; border-bottom:1px solid var(--border-light); padding-bottom:0.5rem;">
          <i class="fa-solid fa-database"></i> Parameter Database MySQL (XAMPP)
        </h3>

        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">Host Database</label>
            <input type="text" name="db_host" class="form-control" value="<?= e(DB_HOST) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Port</label>
            <input type="text" name="db_port" class="form-control" value="<?= e(DB_PORT) ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nama Database</label>
          <input type="text" name="db_name" class="form-control" value="<?= e(DB_NAME) ?>" required>
          <span class="form-hint">Database akan otomatis dibuat jika belum ada.</span>
        </div>

        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:0.75rem;">
          <div class="form-group">
            <label class="form-label">User MySQL</label>
            <input type="text" name="db_user" class="form-control" value="<?= e(DB_USER) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password MySQL</label>
            <input type="password" name="db_pass" class="form-control" value="<?= e(DB_PASS) ?>" placeholder="(Kosong default XAMPP)">
          </div>
        </div>

        <h3 style="font-size:1.05rem; font-weight:700; margin:1.25rem 0 1rem 0; border-bottom:1px solid var(--border-light); padding-bottom:0.5rem;">
          <i class="fa-solid fa-user-shield"></i> Akun Administrator Utama
        </h3>

        <div class="form-group">
          <label class="form-label">Email Admin</label>
          <input type="email" name="admin_email" class="form-control" value="admin@rmbuani.com" required>
        </div>

        <div class="form-group">
          <label class="form-label">Password Admin</label>
          <input type="text" name="admin_password" class="form-control" value="password123" required>
          <span class="form-hint">Anda dapat mengubah password ini atau menggunakan password123 untuk pengembangan lokal.</span>
        </div>

        <div class="form-group" style="margin-top:1rem;">
          <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer; font-size:0.9rem;">
            <input type="checkbox" name="seed_data" value="1" checked style="width:18px; height:18px; accent-color:var(--primary);">
            <span>Isi data menu contoh otomatis (Ayam Bakar Madu, Mie Pedas, Bebek Kremes, dll)</span>
          </label>
        </div>

        <button type="submit" name="run_install" class="sy-btn sy-btn-primary sy-btn-block sy-btn-lg" style="margin-top:1.5rem;">
          <i class="fa-solid fa-play"></i> Mulai Instalasi Sekarang
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
