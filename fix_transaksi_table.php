<?php
// fix_transaksi_table.php — jalankan SEKALI lalu HAPUS
require_once 'admin-panel/config/database.php';
$koneksi = isset($db) ? $db : $conn;

echo "<div style='font-family:sans-serif;padding:20px;'>";
echo "<h2>🔧 Fix Tabel Transaksi</h2>";

$steps = [

"Drop tabel transaksi_item lama" => "DROP TABLE IF EXISTS `transaksi_item`",
"Drop tabel transaksi lama"      => "DROP TABLE IF EXISTS `transaksi`",

"Buat tabel transaksi baru" => "
CREATE TABLE `transaksi` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`          VARCHAR(50) NOT NULL UNIQUE,
    `user_id`           INT NOT NULL,
    `total_harga`       DECIMAL(12,2) NOT NULL,
    `biaya_aplikasi`    DECIMAL(10,2) DEFAULT 2000,
    `total_bayar`       DECIMAL(12,2) NOT NULL,
    `metode_bayar`      ENUM('midtrans','cod') DEFAULT 'cod',
    `status_bayar`      ENUM('pending','paid','failed') DEFAULT 'pending',
    `status_pesanan`    ENUM('dikemas','dikirim','selesai','dibatalkan') DEFAULT 'dikemas',
    `nama_penerima`     VARCHAR(150),
    `alamat_pengiriman` TEXT,
    `catatan`           TEXT,
    `midtrans_token`    VARCHAR(255),
    `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
",

"Buat tabel transaksi_item baru" => "
CREATE TABLE `transaksi_item` (
    `id`            INT AUTO_INCREMENT PRIMARY KEY,
    `transaksi_id`  INT NOT NULL,
    `order_id`      VARCHAR(50) NOT NULL,
    `produk_id`     INT NOT NULL,
    `nama_produk`   VARCHAR(255) NOT NULL,
    `foto_produk`   VARCHAR(255),
    `ukuran`        VARCHAR(20),
    `harga_satuan`  DECIMAL(10,2) NOT NULL,
    `jumlah`        INT NOT NULL,
    `subtotal`      DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
"

];

foreach ($steps as $label => $sql) {
    if ($koneksi->query($sql)) {
        echo "<p style='color:green'>✅ $label — berhasil</p>";
    } else {
        echo "<p style='color:red'>❌ $label — " . $koneksi->error . "</p>";
    }
}

// Verifikasi struktur
foreach (['transaksi', 'transaksi_item'] as $tabel) {
    echo "<hr><h3>📋 Struktur $tabel:</h3>";
    $cols = $koneksi->query("SHOW COLUMNS FROM $tabel");
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-size:13px;margin-bottom:10px'>";
    echo "<tr style='background:#eee'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while($r = $cols->fetch_assoc()) {
        echo "<tr><td><b>{$r['Field']}</b></td><td>{$r['Type']}</td><td>{$r['Null']}</td><td>{$r['Default']}</td></tr>";
    }
    echo "</table>";
}

echo "<p style='color:#888;font-size:12px;margin-top:20px'>⚠️ Hapus file ini setelah selesai!</p>";
echo "</div>";
?>