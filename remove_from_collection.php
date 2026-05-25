<?php
session_start();
require_once 'admin-panel/config/database.php'; 

$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

if (isset($_SESSION['user_id']) && isset($_GET['id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_GET['id']);

    $query = "DELETE FROM collections WHERE user_id = ? AND product_id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
}

header("Location: collections.php");
exit;
?>