<?php 
require_once 'config/database.php';

// Menjalankan query pembuatan tabel secara berurutan dengan skema JAPAK yang sinkron
$queries = [
    // 1. Tabel Kategori (Primary Key menggunakan id_kategori)
    "CREATE TABLE IF NOT EXISTS kategori (
        id_kategori INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    // 2. Tabel Produk (Foreign Key menggunakan id_kat mengarah ke id_kategori)
    "CREATE TABLE IF NOT EXISTS produk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kat INT NULL,
        nama_produk VARCHAR(255) NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        gambar VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (id_kat) REFERENCES kategori(id_kategori) ON DELETE SET NULL
    ) ENGINE=InnoDB;",

    // 3. Tabel Transaksi
    "CREATE TABLE IF NOT EXISTS transaksi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_produk INT NULL,
        jumlah INT NOT NULL,
        total_harga DECIMAL(10,2) NOT NULL,
        tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;"
];

$success = true;
foreach ($queries as $sql) {
    if (!$db->query($sql)) {
        echo "<p style='color: red;'>❌ Gagal mengeksekusi query: " . $db->error . "</p>";
        $success = false;
        break;
    }
}

if ($success) {
    echo "<div style='font-family: sans-serif; padding: 20px; background: #e8f5e9; border-radius: 5px; color: #2e7d32; border: 1px solid #a5d6a7;'>";
    echo "<h3>🎉 Sukses! Semua struktur tabel JAPAK berhasil disinkronkan di database Aiven!</h3>";
    echo "<p>Sekarang silakan buka kembali halaman <b><a href='produk-tambah.php'>produk-tambah.php</a></b> atau <b><a href='produk-list.php'>produk-list.php</a></b> kamu.</p>";
    echo "</div>";
}
?>