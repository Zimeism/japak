<?php 
require_once 'config/database.php';

echo "<h3 style='font-family: sans-serif; color: #0288d1;'>🔄 Memulai Hard Reset Database defaultdb...</h3>";

// 1. Matikan pengecekan foreign key agar tidak terblokir oleh relasi antar tabel
$db->query("SET FOREIGN_KEY_CHECKS = 0;");

// 2. Hapus tabel-tabel lama
$db->query("DROP TABLE IF EXISTS transaksi;");
$db->query("DROP TABLE IF EXISTS produk;");
$db->query("DROP TABLE IF EXISTS kategori;");
echo "<p style='color: orange; font-family: sans-serif;'>🗑️ Semua tabel lama berhasil dibersihkan dari defaultdb.</p>";

// 3. Buat ulang tabel dengan struktur JAPAK yang sinkron
$create_queries = [
    // Tabel Kategori
    "CREATE TABLE kategori (
        id_kategori INT AUTO_INCREMENT PRIMARY KEY,
        nama_kategori VARCHAR(100) NOT NULL
    ) ENGINE=InnoDB;",

    // Tabel Produk (Menggunakan id_kat)
    "CREATE TABLE produk (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_kat INT NULL,
        nama_produk VARCHAR(255) NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        stok INT NOT NULL,
        gambar VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (id_kat) REFERENCES kategori(id_kategori) ON DELETE SET NULL
    ) ENGINE=InnoDB;",

    // Tabel Transaksi
    "CREATE TABLE transaksi (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_produk INT NULL,
        jumlah INT NOT NULL,
        total_harga DECIMAL(10,2) NOT NULL,
        tanggal_transaksi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;"
];

$success = true;
foreach ($create_queries as $create_sql) {
    if (!$db->query($create_sql)) {
        echo "<p style='color: red; font-family: sans-serif;'>❌ Gagal membuat tabel: " . $db->error . "</p>";
        $success = false;
        break;
    }
}

// 4. Hidupkan kembali pengecekan foreign key
$db->query("SET FOREIGN_KEY_CHECKS = 1;");

if ($success) {
    echo "<div style='font-family: sans-serif; padding: 15px; background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; margin-top: 20px; border-radius: 5px;'>";
    echo "<h3>🎉 BOOM! Database defaultdb Berhasil Direset Total & Sinkron!</h3>";
    echo "<p>Sekarang, ikuti instruksi wajib di bawah ini secara berurutan:</p>";
    echo "<ol>";
    echo "<li>Buka halaman input kategori kamu di browser untuk mengisi data kategori baru.</li>";
    echo "<li>Setelah kategori terisi, buka kembali halaman <b><a href='produk-tambah.php'>produk-tambah.php</a></b>.</li>";
    echo "</ol>";
    echo "</div>";
}
?>