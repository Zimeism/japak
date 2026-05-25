<?php
// get_wilayah.php
header('Content-Type: application/json');

// Menggunakan __DIR__ agar path database terkunci secara absolut dari root folder
require_once __DIR__ . '/admin-panel/config/database.php'; 

$type = isset($_GET['type']) ? $_GET['type'] : '';

if ($type === 'kabupaten') {
    $provinsi_id = isset($_GET['provinsi_id']) ? $_GET['provinsi_id'] : '';
    
    if (!empty($provinsi_id)) {
        // Ambil data kabupaten berdasarkan id provinsi
        $stmt = $db->prepare("SELECT id, nama FROM `wilayah_kabupaten` WHERE `provinsi_id` = ? ORDER BY nama ASC");
        $stmt->bind_param("s", $provinsi_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode($data);
        exit();
    }
} 

if ($type === 'kecamatan') {
    $kabupaten_id = isset($_GET['kabupaten_id']) ? $_GET['kabupaten_id'] : '';
    
    if (!empty($kabupaten_id)) {
        // Ambil data kecamatan berdasarkan id kabupaten
        $stmt = $db->prepare("SELECT id, nama FROM `wilayah_kecamatan` WHERE `kabupaten_id` = ? ORDER BY nama ASC");
        $stmt->bind_param("s", $kabupaten_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        echo json_encode($data);
        exit();
    }
}

// Jika parameter tidak sesuai atau data tidak ditemukan, kirim array kosong
echo json_encode([]);
?>