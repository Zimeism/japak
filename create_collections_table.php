<?php
// Menggunakan file database di dalam folder admin-panel sesuai instruksimu
require_once 'admin-panel/config/database.php'; 

$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

if (!$koneksi) {
    die("Koneksi database gagal. Periksa admin-panel/config/database.php Anda.");
}

// FIX TOTAL: Menggunakan nama tabel asli di Aiven: 'pengguna' dan 'produk'
$sql = "CREATE TABLE IF NOT EXISTS collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, 
    product_id INT NOT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES pengguna(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;";

if ($koneksi->query($sql) === TRUE) {
    echo "<div style='padding: 20px; background: #d4edda; color: #155724; font-family: sans-serif; border-radius: 5px; margin: 20px;'>
            <strong>Berhasil!</strong> Tabel 'collections' sukses dibuat di database Aiven menggunakan relasi tabel pengguna dan produk.
          </div>";
} else {
    echo "<div style='padding: 20px; background: #f8d7da; color: #721c24; font-family: sans-serif; border-radius: 5px; margin: 20px;'>
            <strong>Gagal membuat tabel:</strong> " . $koneksi->error . "
          </div>";
}

$koneksi->close();
?>