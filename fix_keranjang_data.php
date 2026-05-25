<?php
// fix_keranjang_data.php — jalankan SEKALI lalu HAPUS
session_start();
require_once 'admin-panel/config/database.php';
$koneksi = isset($db) ? $db : $conn;

echo "<div style='font-family:sans-serif;padding:20px;'>";
echo "<h2>🔧 Fix Data Keranjang</h2>";

// Hapus semua data keranjang yang nama_produknya "0" atau kosong
$del = $koneksi->query("DELETE FROM keranjang_db WHERE nama_produk = '0' OR nama_produk = '' OR nama_produk IS NULL");
echo "<p style='color:green'>✅ Hapus data corrupt: " . $koneksi->affected_rows . " baris dihapus</p>";

// Sync ulang nama dan gambar dari tabel produk
$sync = $koneksi->query("
    UPDATE keranjang_db k
    JOIN produk p ON k.id_produk = p.id
    SET k.nama_produk = p.nama_produk,
        k.foto_produk = p.gambar,
        k.harga_produk = p.harga
");
echo "<p style='color:green'>✅ Sync nama & gambar: " . $koneksi->affected_rows . " baris diperbarui</p>";

// Tampilkan hasil
$check = $koneksi->query("SELECT k.*, p.nama_produk as nama_asli FROM keranjang_db k LEFT JOIN produk p ON k.id_produk = p.id");
echo "<h3>📋 Data keranjang sekarang:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-size:13px;'>";
echo "<tr style='background:#eee'><th>ID</th><th>user_id</th><th>id_produk</th><th>nama_produk</th><th>foto_produk</th><th>harga</th><th>jumlah</th></tr>";
while($r = $check->fetch_assoc()) {
    echo "<tr>
        <td>{$r['id']}</td>
        <td>{$r['user_id']}</td>
        <td>{$r['id_produk']}</td>
        <td><b>{$r['nama_produk']}</b></td>
        <td>{$r['foto_produk']}</td>
        <td>{$r['harga_produk']}</td>
        <td>{$r['jumlah']}</td>
    </tr>";
}
echo "</table>";
echo "<p style='color:#888;font-size:12px;margin-top:20px'>⚠️ Hapus file ini setelah selesai!</p>";
echo "</div>";
?>