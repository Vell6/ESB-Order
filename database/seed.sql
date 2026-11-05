-- ============================================================
-- RM bu ani - Online Restaurant Ordering & Point of Sale System
-- Initial Seed Data
-- ============================================================

USE `db_restoran`;

-- Default Users (Password default: password123)
-- Hash generated via password_hash('password123', PASSWORD_BCRYPT)
INSERT INTO `users` (`name`, `email`, `password`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
('Administrator RM bu ani', 'admin@rmbuani.com', '$2y$10$tMhIeL3fX1E0P3mR2tU7e.y1yYq8pYw5cO8pZ1v2n3m4k5l6o7p8q', '081234567890', 'admin', 1, NOW(), NOW()),
('Kasir Utama', 'kasir@rmbuani.com', '$2y$10$tMhIeL3fX1E0P3mR2tU7e.y1yYq8pYw5cO8pZ1v2n3m4k5l6o7p8q', '081234567891', 'cashier', 1, NOW(), NOW()),
('Staf Dapur', 'dapur@rmbuani.com', '$2y$10$tMhIeL3fX1E0P3mR2tU7e.y1yYq8pYw5cO8pZ1v2n3m4k5l6o7p8q', '081234567892', 'kitchen', 1, NOW(), NOW()),
('Marvel', 'marvelmarvel3622@gmail.com', '$2y$10$tMhIeL3fX1E0P3mR2tU7e.y1yYq8pYw5cO8pZ1v2n3m4k5l6o7p8q', '083102315194', 'customer', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Default Menus
INSERT INTO `menus` (`id`, `name`, `description`, `price`, `image`, `category`, `supports_spicy`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 'Ayam Bakar Madu Bu Ani', 'Ayam panggang bumbu madu khas bu ani dengan cita rasa manis gurih meresap sampai ke tulang, disajikan dengan lalapan segar dan sambal terasi.', 28000.00, 'assets/images/ayam-bakar.svg', 'Makanan Utama', 1, 1, NOW(), NOW()),
(2, 'Bebek Goreng Kremes Sambal Korek', 'Bebek ungkep rempah tradisional digoreng renyah bertabur kremesan gurih, dipadukan sambal korek pedas nendang.', 35000.00, 'assets/images/bebek-goreng.svg', 'Makanan Utama', 1, 1, NOW(), NOW()),
(3, 'Mie Pedas Juara Bu Ani', 'Mie kenyal olahan rumahan dibalut bumbu rahasia gurih manis pedas, taburan ayam cincang dan pangsit goreng renyah.', 15000.00, 'assets/images/mie-pedas.svg', 'Makanan Utama', 1, 1, NOW(), NOW()),
(4, 'Nasi Goreng Spesial Bu Ani', 'Nasi goreng kampung dengan telur orak-arik, suwiran ayam kampung, bakso sapi, dan acar segar.', 22000.00, 'assets/images/nasi-goreng.svg', 'Makanan Utama', 1, 1, NOW(), NOW()),
(5, 'Iga Sapi Bakar Sambal Matah', 'Iga sapi empuk dibakar dengan olesan bumbu rempah kecap manis gurih, disajikan dengan sambal matah khas Bali.', 48000.00, 'assets/images/iga-bakar.svg', 'Makanan Utama', 1, 1, NOW(), NOW()),
(6, 'Ayam Geprek Sambal Bawang', 'Ayam goreng tepung renyah digeprek dengan ulekan cabai rawit merah dan bawang putih segar serta minyak panas gurih.', 20000.00, 'assets/images/ayam-geprek.svg', 'Makanan Utama', 1, 1, NOW(), NOW()),

-- Paket Hemat / Combat
(7, 'Paket Hemat Kenyang A', 'Paket hemat: Nasi Putih + Ayam Bakar Madu + Tahu Tempe Goreng + Es Teh Manis Jumbo.', 35000.00, 'assets/images/paket-a.svg', 'Paket Hemat', 1, 1, NOW(), NOW()),
(8, 'Paket Hemat Kenyang B', 'Paket komplit: Nasi Putih + Bebek Goreng Kremes + Sayur Asem + Es Jeruk Segar.', 42000.00, 'assets/images/paket-b.svg', 'Paket Hemat', 1, 1, NOW(), NOW()),
(9, 'Paket Mie Combat Spesial', '1 Porsi Mie Pedas Juara + 1 Siomay Ayam Kukus + 1 Udang Keju Crispy + 1 Es Teh Dingin.', 32000.00, 'assets/images/paket-c.svg', 'Paket Hemat', 1, 1, NOW(), NOW()),

-- Dimsum & Snack
(10, 'Siomay Ayam Kukus (3 pcs)', 'Olahan daging ayam dan udang segar dibungkus kulit pangsit lembut, disajikan dengan saus asam manis pedas.', 14000.00, 'assets/images/siomay.svg', 'Dimsum & Snack', 0, 1, NOW(), NOW()),
(11, 'Udang Keju Crispy (3 pcs)', 'Adonan udang lembut berbalut tepung roti krispi dengan lelehan keju mozzarella di dalamnya.', 16000.00, 'assets/images/udang-keju.svg', 'Dimsum & Snack', 0, 1, NOW(), NOW()),
(12, 'Pangsit Goreng Renyah (5 pcs)', 'Pangsit goreng isi ayam cincang renyah dan gurih, pelengkap sempurna untuk segala sajian makanan.', 12000.00, 'assets/images/pangsit.svg', 'Dimsum & Snack', 0, 1, NOW(), NOW()),
(13, 'Lumpia Udang Kulit Tahu', 'Lumpia udang istimewa dibungkus kulit tahu lembut digoreng keemasan.', 15000.00, 'assets/images/lumpia.svg', 'Dimsum & Snack', 0, 1, NOW(), NOW()),

-- Minuman Segar
(14, 'Es Teh Manis Jumbo', 'Teh melati seduh alami dengan gula tebu asli, disajikan dingin menyegarkan dalam gelas ukuran jumbo.', 6000.00, 'assets/images/es-teh.svg', 'Minuman Segar', 0, 1, NOW(), NOW()),
(15, 'Es Jeruk Peras Murni', 'Jeruk peras segar alami kaya vitamin C dengan es batu dingin pelepas dahaga.', 10000.00, 'assets/images/es-jeruk.svg', 'Minuman Segar', 0, 1, NOW(), NOW()),
(16, 'Es Cincau Gula Aren Santan', 'Cincau hitam kenyal dengan sirup gula aren murni dan santan kelapa gurih.', 12000.00, 'assets/images/es-cincau.svg', 'Minuman Segar', 0, 1, NOW(), NOW()),
(17, 'Kopi Susu Bu Ani Dingin', 'Kopi robusta racikan spesial bu ani dipadukan dengan susu kental manis dan creamer legit.', 14000.00, 'assets/images/kopi-susu.svg', 'Minuman Segar', 0, 1, NOW(), NOW()),
(18, 'Air Mineral Dingin (600ml)', 'Air mineral pegunungan botol 600ml disajikan dingin.', 5000.00, 'assets/images/air-mineral.svg', 'Minuman Segar', 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);
