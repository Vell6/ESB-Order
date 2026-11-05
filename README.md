# RM bu ani - Online Restaurant Ordering & Point of Sale (POS) System

Sistem manajemen restoran dan pemesanan makanan berbasis web modern, responsif, dan terintegrasi untuk **RM bu ani**. Mengintegrasikan 3 lingkungan utama: **Pelanggan (Customer Web Ordering)**, **Kasir POS & Administrator**, dan **Layar Dapur (Kitchen Display System / KDS)** dalam satu database MySQL/MariaDB terpusat.

Dibuat menggunakan **Native PHP**, **Vanilla CSS (`theme.css`)**, **Vanilla JavaScript**, dan **Fetch API / AJAX Polling**.

---

## 📋 Fitur Utama Sesuai PRD v1.0

### 1. Pelanggan (Customer Web Ordering)
- **Pintu Masuk QR Code Meja**: Akses otomatis membaca nomor meja via URL parameter (contoh: `?meja=01`, `?meja=02`, dst).
- **Pilihan Layanan**:
  - *Makan di Tempat (Dine In)*: Wajib nomor meja.
  - *Bungkus (Take Away)*: Nomor meja otomatis NULL, dapur menerima penanda khusus "BUNGKUS".
- **Katalog Menu Interaktif (Mobile-First)**: Terinspirasi dari tampilan ESB Order modern. Banner outlet, info jam buka, kategori menu dengan tabs horizontal, dan grid 2 kolom.
- **Modal Tambah Menu**:
  - Foto makanan, nama, harga, deskripsi lengkap.
  - Pilihan Tingkat Kepedasan (Level 1 - Level 5) untuk menu yang mendukung pedas.
  - Catatan khusus makanan (misal: *tanpa seledri, pisah sambal*).
  - Quantity stepper `[-] 1 [+]` dengan kalkulasi total instan.
  - Animasi visual *Fly to Cart* dan *Cart Bounce*.
- **Keranjang Belanja (Cart)**: Pengelolaan jumlah pesanan, subtotal dinamis, dan validasi ketersediaan menu.
- **Checkout Pesanan**:
  - Kalkulasi total dihitung ulang 100% di sisi server PHP (mencegah manipulasi harga dari browser).
  - Penghasilan Order ID unik kriptografis (format: `RMA-YYYYMMDD-XXXXXX`).
  - Barcode CODE128 otomatis dihasilkan menggunakan JsBarcode.
- **Pesanan Saya (3 Tab)**:
  - *Belum Dibayar (`unpaid`)*: Menampilkan Order ID, Barcode CODE128, rincian menu, total tagihan, dan tombol pembatalan.
  - *Diproses (`processing`)*: Pemantauan proses memasak di dapur secara real-time via AJAX polling (interval 5 detik).
  - *Selesai (`completed`)*: Informasi pengambilan / pengantaran meja dan formulir ulasan & rating bintang 1-5.
- **Profil Pelanggan**: Tampilan profil akun (Avatar, Nama, Telepon, Email, Riwayat Pesanan, Ubah Profil).

### 2. Kasir (Point of Sale / POS)
- **Pencarian Transaksi Cepat**: Mendukung input manual Order ID maupun pemindaian menggunakan **Scanner Barcode USB**.
- **Konfirmasi Pembayaran Tunai (Cash)**:
  - Validasi ketat dan *Row-Level Locking* (`FOR UPDATE`) dalam transaksi database untuk mencegah pembayaran ganda (*double payment*).
  - Kalkulator uang diterima dan hitung kembalian otomatis dengan tombol preset pecahan uang.
  - Perubahan status pesanan otomatis menjadi `processing` dan diteruskan ke layar dapur.
- **Cetak Struk Pembayaran (Thermal Receipt)**:
  - Format cetak khusus printer thermal **58 mm**, **80 mm**, dan kertas **A4**.
  - Dilengkapi identitas RM bu ani, rincian makanan, waktu pembayaran, status lunas, barcode, dan ucapan terima kasih.

### 3. Bagian Dapur (Kitchen Display System / KDS)
- **Tampilan Khusus Dapur**: Kontras tinggi, teks dan kartu berukuran besar, dirancang khusus untuk tablet/layar dapur.
- **Hanya Menampilkan Pesanan yang Sudah Lunas**: Mencegah pemborosan bahan baku sebelum pembayaran diterima.
- **Pembeda Visual Jelas**:
  - *Take Away*: Badge oranye mencolok bertuliskan **BUNGKUS (TAKE AWAY)**.
  - *Makan di Tempat*: Badge biru dengan nomor meja berukuran besar (**MEJA 04**).
- **Detail Lengkap**: Jumlah porsi besar (`2x`), highlight level pedas merah, dan catatan khusus pelanggan.
- **Penyelesaian Pesanan**: Satu tombol besar "SELESAIKAN PESANAN" yang otomatis mengubah status menjadi `completed` dan mengirim notifikasi ke kasir dan pelanggan.
- **Auto-Refresh Polling**: Pembaruan antrean otomatis setiap 3–5 detik dilengkapi audio chime saat ada pesanan baru masuk.

### 4. Administrator
- **Dashboard Statistik Finansial**: Total omzet hari ini, omzet bulan ini, antrean pesanan, dan grafik transaksi.
- **Kelola Menu (CRUD)**: Tambah, edit, dan atur ketersediaan menu (Tersedia / Habis) serta dukungan level pedas.
- **Upload Gambar Aman**: Validasi MIME finfo (JPG, PNG, WEBP maks 2MB), penamaan acak, dan proteksi eksekusi PHP pada folder uploads.
- **Laporan Penjualan**: Filter rentang tanggal, total omzet, rata-rata transaksi, menu terlaris, rincian harian, dan cetak laporan.
- **Kelola Pengguna**: Pengaturan akun dan pergantian role (`customer`, `cashier`, `kitchen`, `admin`).

---

## 🛠️ Persyaratan Lingkungan (Environment)

- **OS**: Windows 10 / 11
- **XAMPP**: Apache + MySQL / MariaDB + PHP 8.1+
- **Browser Modern**: Chrome, Edge, Firefox, Safari

---

## 🚀 Panduan Instalasi & Menjalankan Aplikasi di XAMPP

### Langkah 1: Penempatan Folder Proyek
Pastikan folder proyek ini diletakkan pada direktori web server XAMPP:
```
C:\xampp\htdocs\restoran\
```
*(Atau folder `C:\xampp\htdocs\ESB Order\` sesuai nama folder workspace Anda).*

### Langkah 2: Aktifkan Service XAMPP
1. Buka **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache**.
3. Klik tombol **Start** pada modul **MySQL**.

### Langkah 3: Jalankan Web Installer Otomatis
Buka browser dan akses alamat instalasi:
```
http://localhost/restoran/setup_db.php
```
*(atau `http://localhost/ESB%20Order/setup_db.php`)*

1. Periksa konfigurasi MySQL (Default XAMPP: Host `localhost`, User `root`, Password kosong).
2. Tentukan password admin awal atau gunakan default `password123`.
3. Klik tombol **"Mulai Instalasi Sekarang"**.
4. Sistem installer akan:
   - Membuat database `db_restoran`.
   - Membuat seluruh tabel (`users`, `menus`, `orders`, `order_items`, `reviews`, `order_status_logs`).
   - Membuat index dan foreign key.
   - Mengisi data menu autentik lengkap dengan ilustrasi visual.
   - Membuat akun uji coba siap pakai.

---

## 👥 Akun Uji Coba Demo (Default)

Semua akun awal memiliki password default: `password123`

| Role | Email Login | Password | Akses Halaman |
|---|---|---|---|
| **Administrator** | `admin@rmbuani.com` | `password123` | Dashboard, Menu, Laporan, Pengguna, POS |
| **Kasir (Cashier)** | `kasir@rmbuani.com` | `password123` | POS Kasir, Pencarian Barcode, Pembayaran, Cetak Struk |
| **Dapur (Kitchen)** | `dapur@rmbuani.com` | `password123` | Layar Dapur KDS (Antrean Masak & Selesaikan) |
| **Pelanggan (Customer)** | `marvelmarvel3622@gmail.com` | `password123` | Menu, Keranjang, Checkout, Pesanan Saya, Profil |

> *Catatan: Pada halaman login (`login.php`), tersedia tombol cepat "Akses Cepat Pengujian Demo" untuk mengisi email dan password secara otomatis dalam satu klik!*

---

## 📱 Skenario Alur Pengujian Cepat

1. **Simulasi Scan QR Meja**:
   - Buka: `http://localhost/restoran/?meja=05`
   - Nomor meja 05 otomatis tersimpan dalam sesi pemesanan.
2. **Pilih Menu & Level Pedas**:
   - Buka menu **Ayam Bakar Madu Bu Ani** atau **Mie Pedas Juara Bu Ani**.
   - Pilih Level Pedas (Level 1 s/d Level 5) dan tambahkan catatan.
   - Klik **Tambah Pesanan** (perhatikan animasi *Fly to Cart* dan lonjakan ikon keranjang).
3. **Checkout**:
   - Buka Keranjang -> Lanjut ke Checkout -> Klik **Buat Pesanan Sekarang**.
   - Anda akan mendapatkan Order ID (contoh: `RMA-20261104-XXXXXX`) dan barcode CODE128.
4. **Bayar di Kasir**:
   - Buka portal kasir (`admin/orders.php`).
   - Scan atau ketik Order ID pada kolom pencarian kasir.
   - Klik **Terima Pembayaran**, masukkan nominal uang tunai, dan konfirmasi.
5. **Dapur Memasak (KDS)**:
   - Buka portal dapur (`admin/kitchen.php`).
   - Pesanan langsung muncul dengan label Meja 05 atau BUNGKUS beserta highlight level pedas.
   - Klik **SELESAIKAN PESANAN**.
6. **Notifikasi Pelanggan**:
   - Pada halaman pelanggan (`my-orders.php`), status otomatis berpindah ke tab **Selesai** tanpa perlu reload halaman.
   - Pelanggan dapat memberikan rating bintang 1-5 dan ulasan!

---

## 🔒 Fitur Keamanan

1. **Perlindungan CSRF**: Semua endpoint yang mengubah data (`POST`/`PUT`/`DELETE`) mewajibkan token CSRF valid via form token atau header `X-CSRF-Token`.
2. **Pencegahan SQL Injection**: 100% query database menggunakan PDO Prepared Statements.
3. **Pencegahan XSS**: Seluruh output string pengguna diescape menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
4. **Keamanan Autentikasi**: Password di-hash menggunakan algoritma `PASSWORD_DEFAULT` (bcrypt) dan session ID diregenerasi setelah login (`session_regenerate_id(true)`).
5. **Validasi File Upload**: Folder `uploads/` dilindungi file `.htaccess` yang menonaktifkan eksekusi skrip PHP, divalidasi dengan `finfo` MIME type, batasan ukuran 2MB, dan nama file diacak secara kriptografis.
6. **Data Ownership Check**: Pelanggan hanya dapat melihat, melacak, dan membatalkan pesanan miliknya sendiri (`WHERE user_id = :current_user_id`).
