<?php
// File: create_table_keranjang.php
// Jalankan file ini SEKALI via browser: localhost/japak/create_table_keranjang.php
// Setelah berhasil, HAPUS file ini dari server untuk keamanan.

require_once 'admin-panel/config/database.php';

$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

$sql = "
    CREATE TABLE IF NOT EXISTS `keranjang_db` (
        `id`           INT AUTO_INCREMENT PRIMARY KEY,
        `user_id`      INT NOT NULL,
        `cart_key`     VARCHAR(50) NOT NULL,
        `id_produk`    INT NOT NULL,
        `nama_produk`  VARCHAR(255),
        `harga_produk` DECIMAL(10,2),
        `foto_produk`  VARCHAR(255),
        `ukuran`       VARCHAR(20),
        `jumlah`       INT DEFAULT 1,
        UNIQUE KEY `unique_cart` (`user_id`, `cart_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($koneksi->query($sql)) {
    echo "<p style='font-family:sans-serif; color:green; font-size:18px;'>
            ✅ Tabel <strong>keranjang_db</strong> berhasil dibuat!<br>
            <small style='color:#555;'>Silakan hapus file ini dari server setelah ini.</small>
          </p>";
} else {
    echo "<p style='font-family:sans-serif; color:red; font-size:18px;'>
            ❌ Gagal membuat tabel: " . $koneksi->error . "
          </p>";
}

$koneksi->close();
?>