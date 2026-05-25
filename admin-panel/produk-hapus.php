<?php
require_once 'config/database.php';

// Ambil parameter id dari URL secara aman
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // 1. Ambil nama file gambar terlebih dahulu sebelum datanya dihapus
    $query_gambar = "SELECT gambar FROM produk WHERE id = ?";
    $stmt_gambar = $db->prepare($query_gambar);
    $stmt_gambar->bind_param("i", $id);
    $stmt_gambar->execute();
    $result = $stmt_gambar->get_result();
    
    if ($result && $result->num_rows > 0) {
        $produk = $result->fetch_assoc();
        $nama_gambar = $produk['gambar'];
        
        // Jika file gambar ada di folder uploads, hapus filenya secara permanen
        if (!empty($nama_gambar) && file_exists('uploads/' . $nama_gambar)) {
            unlink('uploads/' . $nama_gambar);
        }
    }
    $stmt_gambar->close();

    // 2. Hapus data produk dari database berdasarkan ID
    $query_hapus = "DELETE FROM produk WHERE id = ?";
    $stmt_hapus = $db->prepare($query_hapus);
    $stmt_hapus->bind_param("i", $id);
    
    if ($stmt_hapus->execute()) {
        // Jika sukses, kembalikan admin ke produk-list.php dengan status sukses
        header("Location: produk-list.php?status=success_delete");
        exit();
    } else {
        echo "❌ Gagal menghapus data dari database: " . $db->error;
    }
    $stmt_hapus->close();
} else {
    // Jika ID tidak valid, balikkan ke list produk
    header("Location: produk-list.php");
    exit();
}
?>