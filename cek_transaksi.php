<?php
// cek_transaksi.php — jalankan SEKALI lalu HAPUS
require_once 'admin-panel/config/database.php';
$koneksi = isset($db) ? $db : $conn;

echo "<div style='font-family:sans-serif;padding:20px;'>";
echo "<h2>🔍 Cek Data Transaksi</h2>";

// Cek semua transaksi + item
$result = $koneksi->query("
    SELECT t.id, t.order_id, t.user_id, t.status_pesanan, 
           ti.nama_produk, ti.foto_produk, ti.ukuran, ti.jumlah
    FROM transaksi t
    LEFT JOIN transaksi_item ti ON t.id = ti.transaksi_id
    ORDER BY t.created_at DESC
    LIMIT 20
");

echo "<h3>📋 Data Transaksi + Item:</h3>";
echo "<table border='1' cellpadding='8' style='border-collapse:collapse;font-size:13px'>";
echo "<tr style='background:#eee'>
    <th>t.id</th><th>order_id</th><th>user_id</th>
    <th>status_pesanan</th><th>nama_produk</th>
    <th>foto_produk</th><th>ukuran</th><th>jumlah</th>
</tr>";

$count = 0;
while ($r = $result->fetch_assoc()) {
    $count++;
    $bg = $r['nama_produk'] ? '' : "style='background:#ffe0e0'";
    echo "<tr $bg>
        <td>{$r['id']}</td>
        <td>{$r['order_id']}</td>
        <td>{$r['user_id']}</td>
        <td>{$r['status_pesanan']}</td>
        <td>" . ($r['nama_produk'] ?? '<b style="color:red">NULL ❌</b>') . "</td>
        <td>" . ($r['foto_produk'] ?? '-') . "</td>
        <td>" . ($r['ukuran'] ?? '-') . "</td>
        <td>" . ($r['jumlah'] ?? '-') . "</td>
    </tr>";
}

if ($count === 0) echo "<tr><td colspan='8' style='color:red;text-align:center'>Tidak ada data sama sekali</td></tr>";
echo "</table>";

// Cek total row per tabel
$t1 = $koneksi->query("SELECT COUNT(*) as total FROM transaksi")->fetch_assoc();
$t2 = $koneksi->query("SELECT COUNT(*) as total FROM transaksi_item")->fetch_assoc();
echo "<p>📊 Total baris <b>transaksi</b>: {$t1['total']} | <b>transaksi_item</b>: {$t2['total']}</p>";

echo "<p style='color:#888;font-size:12px'>⚠️ Hapus file ini setelah selesai!</p>";
echo "</div>";
?>