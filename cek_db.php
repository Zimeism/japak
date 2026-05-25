<?php
require_once 'admin-panel/config/database.php';
$query = $db->query("SELECT id, nama_produk, gambar FROM produk");
while($row = $query->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Produk: " . $row['nama_produk'] . " | Isi Kolom Gambar: <b>" . $row['gambar'] . "</b><br>";
}
?>