<?php
session_start();
require_once 'admin-panel/config/database.php'; 

$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'login_required', 'message' => 'Silakan login terlebih dahulu untuk menambahkan ke koleksi!']);
    exit;
}

if (isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);

    // Periksa status data di tabel collections
    $check_query = "SELECT id FROM collections WHERE user_id = ? AND product_id = ?";
    $stmt = $koneksi->prepare($check_query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Jika sudah disukai, hapus dari list (Toggle Off)
        $delete_query = "DELETE FROM collections WHERE user_id = ? AND product_id = ?";
        $del_stmt = $koneksi->prepare($delete_query);
        $del_stmt->bind_param("ii", $user_id, $product_id);
        if ($del_stmt->execute()) {
            echo json_encode(['status' => 'removed', 'message' => 'Produk dihapus dari koleksi.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui koleksi.']);
        }
    } else {
        // Jika belum disukai, tambahkan ke list (Toggle On)
        $insert_query = "INSERT INTO collections (user_id, product_id) VALUES (?, ?)";
        $ins_stmt = $koneksi->prepare($insert_query);
        $ins_stmt->bind_param("ii", $user_id, $product_id);
        if ($ins_stmt->execute()) {
            echo json_encode(['status' => 'added', 'message' => 'Produk berhasil ditambahkan ke koleksi!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan ke koleksi.']);
        }
    }
} else {
    echo json_encode(['status' => 'invalid', 'message' => 'Aksi tidak valid.']);
}
?>