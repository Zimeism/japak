<?php
// japak/proses_pembayaran.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Matikan pelaporan error mentah agar tidak mengganggu output JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'admin-panel/config/database.php';
header('Content-Type: application/json');

// 1. AMBIL DATA KERANJANG
$keranjang = isset($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];

if (empty($keranjang)) {
    echo json_encode(['error' => 'Isi keranjang belanja Anda kosong.']);
    exit();
}

// 2. HITUNG SUB-TOTAL DAN DETAIL BARANG
$subtotal = 0;
$item_details = [];

foreach ($keranjang as $key => $item) {
    if (is_array($item) && isset($item['harga_produk']) && isset($item['jumlah'])) {
        $harga_item = (int)$item['harga_produk'];
        $qty_item   = (int)$item['jumlah'];
        $subtotal  += ($harga_item * $qty_item);
        
        $item_details[] = [
            'id'       => 'PROD-' . $item['id_produk'],
            'price'    => $harga_item,
            'quantity' => $qty_item,
            'name'     => substr($item['nama_produk'], 0, 50)
        ];
    }
}

// Tambahkan biaya layanan aplikasi (Rp 2.000)
$biaya_layanan = 2000;
$gross_amount = $subtotal + $biaya_layanan;

$item_details[] = [
    'id'       => 'BIAYA-LAYANAN',
    'price'    => $biaya_layanan,
    'quantity' => 1,
    'name'     => 'Biaya Jasa Aplikasi'
];

// Generate Order ID unik JAPAK
$order_id = 'JAPAK-' . time() . '-' . rand(10, 99);

// 3. PARAMETER PAYLOAD MIDTRANS
$transaction_data = [
    'transaction_details' => [
        'order_id'     => $order_id,
        'gross_amount' => $gross_amount,
    ],
    'item_details' => $item_details,
    'customer_details' => [
        'first_name' => $_SESSION['user_nama'] ?? 'Pelanggan Japak',
        'email'      => $_SESSION['user_email'] ?? 'rezimeizafani@gmail.com',
    ]
];

// 4. PROSES ENKRIPSI SERVER KEY KE BASE64 (Kunci Perbaikan!)
$server_key = 'Mid-server-iulweBOSQLgERwUpG45nYDHD';
$clean_key  = trim($server_key);
$base64_key = base64_encode($clean_key . ':');

// 5. TEMBAK API MIDTRANS MENGGUNAKAN CURL
$url = "https://app.sandbox.midtrans.com/snap/v1/transactions";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_data));

// Menggunakan otentikasi header Authorization Bearer/Basic Base64 secara manual
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Basic ' . $base64_key
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// 6. EVALUASI HASIL RESPONS
if ($http_code === 201) {
    $result = json_decode($response, true);
    
    // Blok penyimpanan ke database transaksi (Aman dari crash)
    try {
        if (isset($db) && $db instanceof mysqli) {
            $status_awal = 'pending';
            $user_email = $_SESSION['user_email'] ?? 'rezimeizafani@gmail.com';
            $stmt = $db->prepare("INSERT INTO transaksi (order_id, email, total_harga, status_pembayaran) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssis", $order_id, $user_email, $gross_amount, $status_awal);
                $stmt->execute();
            }
        }
    } catch (Exception $db_error) {
        // Abaikan error DB agar token tetap terkirim ke frontend
    }

    // Kirim token sukses ke Javascript frontend
    echo json_encode(['token' => $result['token']]);
} else {
    // Tampilkan pesan error jika otentikasi gagal
    echo json_encode(['error' => 'Kegagalan Autentikasi: Midtrans API Error. HTTP status code: ' . $http_code . ' API response: ' . $response]);
}