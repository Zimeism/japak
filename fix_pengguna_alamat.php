<?php
// File: fix_pengguna_alamat.php
// Jalankan SEKALI via browser: localhost/japak/fix_pengguna_alamat.php
// Setelah berhasil, HAPUS file ini dari server.

require_once 'admin-panel/config/database.php';

$koneksi = isset($conn) ? $conn : (isset($db) ? $db : null);

if (!$koneksi) {
    die("<p style='color:red'>❌ Koneksi database gagal.</p>");
}

$queries = [
    "Tambah kolom provinsi_id" => "ALTER TABLE `pengguna` ADD COLUMN IF NOT EXISTS `provinsi_id` VARCHAR(20) DEFAULT NULL",
    "Tambah kolom kabupaten_id" => "ALTER TABLE `pengguna` ADD COLUMN IF NOT EXISTS `kabupaten_id` VARCHAR(20) DEFAULT NULL",
    "Tambah kolom kecamatan_id" => "ALTER TABLE `pengguna` ADD COLUMN IF NOT EXISTS `kecamatan_id` VARCHAR(20) DEFAULT NULL",
    "Tambah kolom alamat_spesifik" => "ALTER TABLE `pengguna` ADD COLUMN IF NOT EXISTS `alamat_spesifik` TEXT DEFAULT NULL",
    "Tambah kolom foto" => "ALTER TABLE `pengguna` ADD COLUMN IF NOT EXISTS `foto` VARCHAR(255) DEFAULT NULL",
    "Tambah kolom nama" => "ALTER TABLE `pengguna` ADD COLUMN IF NOT EXISTS `nama` VARCHAR(100) DEFAULT NULL",
];

echo "<div style='font-family:sans-serif; padding:20px;'>";
echo "<h2>🔧 Fix Tabel Pengguna — Aiven Database</h2>";

foreach ($queries as $label => $sql) {
    if ($koneksi->query($sql)) {
        echo "<p style='color:green'>✅ $label — berhasil</p>";
    } else {
        echo "<p style='color:orange'>⚠️ $label — " . $koneksi->error . "</p>";
    }
}

// Cek struktur tabel pengguna sekarang
echo "<hr><h3>📋 Struktur tabel pengguna saat ini:</h3>";
$result = $koneksi->query("SHOW COLUMNS FROM `pengguna`");
if ($result) {
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse; font-size:13px;'>";
    echo "<tr style='background:#eee'><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td><b>{$row['Field']}</b></td>
                <td>{$row['Type']}</td>
                <td>{$row['Null']}</td>
                <td>{$row['Default']}</td>
              </tr>";
    }
    echo "</table>";
}

// Cek jumlah data pengguna
$count = $koneksi->query("SELECT COUNT(*) AS total FROM `pengguna`");
$total = $count->fetch_assoc()['total'];
echo "<hr><p style='font-size:16px'>👥 Total data di tabel pengguna: <b>$total</b></p>";

if ($total == 0) {
    echo "<p style='color:red; font-weight:bold'>⚠️ Tabel pengguna KOSONG di database Aiven! 
          Data pengguna perlu di-migrate dari database lokal.</p>";
}

echo "<p style='color:#888; font-size:12px; margin-top:30px'>Silakan hapus file ini setelah selesai.</p>";
echo "</div>";
?>