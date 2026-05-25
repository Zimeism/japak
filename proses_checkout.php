<?php
// proses_checkout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Matikan pelaporan error mentah agar tidak merusak output JSON jika terjadi warning/notice
error_reporting(0);
ini_set('display_errors', 0);

require_once 'admin-panel/config/database.php';
$koneksi = isset($db) ? $db : $conn;
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$user_id       = $_SESSION['user_id'];
$metode_bayar  = $_POST['metode_bayar'] ?? 'cod';
$catatan       = trim($_POST['catatan'] ?? '');

// Ambil data keranjang user
$stmt = $koneksi->prepare("SELECT * FROM keranjang_db WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$keranjang = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($keranjang)) {
    echo json_encode(['status' => 'error', 'message' => 'Keranjang kosong.']);
    exit;
}

// Ambil alamat dari profil
$stmt_user = $koneksi->prepare("
    SELECT p.nama, p.alamat_spesifik,
           prov.nama AS nama_provinsi,
           kab.nama  AS nama_kabupaten,
           kec.nama  AS nama_kecamatan
    FROM pengguna p
    LEFT JOIN wilayah_provinsi prov 
        ON REPLACE(TRIM(prov.id),'.','') = REPLACE(TRIM(p.provinsi_id),'.','')
    LEFT JOIN wilayah_kabupaten kab 
        ON REPLACE(TRIM(kab.id),'.','') = REPLACE(TRIM(p.kabupaten_id),'.','')
    LEFT JOIN wilayah_kecamatan kec 
        ON REPLACE(TRIM(kec.id),'.','') = REPLACE(TRIM(p.kecamatan_id),'.','')
    WHERE p.id = ?
");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();

if (empty($user_data['alamat_spesifik'])) {
    echo json_encode(['status' => 'no_address', 'message' => 'Lengkapi alamat pengiriman di profil terlebih dahulu.']);
    exit;
}

$alamat_lengkap = implode(', ', array_filter([
    $user_data['nama_provinsi'],
    $user_data['nama_kabupaten'],
    $user_data['nama_kecamatan'],
    $user_data['alamat_spesifik']
]));

// Hitung total
$total_harga   = 0;
foreach ($keranjang as $item) {
    $total_harga += floatval($item['harga_produk']) * intval($item['jumlah']);
}
$biaya_aplikasi = 2000;
$total_bayar    = $total_harga + $biaya_aplikasi;

// Generate order ID unik
$order_id = 'RRN-' . strtoupper(substr(md5(uniqid()), 0, 8)) . '-' . date('dmY');

// =====================
// PROSES COD
// =====================
if ($metode_bayar === 'cod') {

    $koneksi->begin_transaction();
    try {
        // Insert ke transaksi
        $ins = $koneksi->prepare("
            INSERT INTO transaksi 
                (order_id, user_id, total_harga, biaya_aplikasi, total_bayar, 
                 metode_bayar, status_bayar, status_pesanan, 
                 nama_penerima, alamat_pengiriman, catatan)
            VALUES (?, ?, ?, ?, ?, 'cod', 'pending', 'dikemas', ?, ?, ?)
        ");
        $ins->bind_param("sidddsss",
            $order_id, $user_id, $total_harga,
            $biaya_aplikasi, $total_bayar,
            $user_data['nama'], $alamat_lengkap, $catatan
        );
        $ins->execute();
        $transaksi_id = $koneksi->insert_id;

        // Insert item-item ke transaksi_item
        foreach ($keranjang as $item) {
            $subtotal = floatval($item['harga_produk']) * intval($item['jumlah']);
            $ins_item = $koneksi->prepare("
                INSERT INTO transaksi_item 
                    (transaksi_id, order_id, produk_id, nama_produk, foto_produk, ukuran, harga_satuan, jumlah, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $ins_item->bind_param("isisssdid",
                $transaksi_id, $order_id,
                $item['id_produk'], $item['nama_produk'], $item['foto_produk'],
                $item['ukuran'], $item['harga_produk'],
                $item['jumlah'], $subtotal
            );
            $ins_item->execute();
        }

        // Kosongkan keranjang di database
        $del = $koneksi->prepare("DELETE FROM keranjang_db WHERE user_id = ?");
        $del->bind_param("i", $user_id);
        $del->execute();

        // Kosongkan session keranjang
        $_SESSION['keranjang'] = [];

        $koneksi->commit();

        echo json_encode([
            'status'   => 'success',
            'metode'   => 'cod',
            'order_id' => $order_id,
            'message'  => 'Pesanan COD berhasil dibuat!'
        ]);

    } catch (Exception $e) {
        $koneksi->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Gagal membuat pesanan: ' . $e->getMessage()]);
    }

// ==========================================
// PROSES MIDTRANS (NATIVE cURL MANUAL)
// ==========================================
} elseif ($metode_bayar === 'midtrans') {

    // 1. Susun item details dari data keranjang
    $item_details = array_map(function($item) {
        return [
            'id'       => $item['id_produk'],
            'price'    => (int) $item['harga_produk'],
            'quantity' => (int) $item['jumlah'],
            'name'     => substr($item['nama_produk'], 0, 50),
        ];
    }, $keranjang);

    // Tambah biaya aplikasi sebagai item details
    $item_details[] = [
        'id'       => 'BIAYA-APP',
        'price'    => 2000,
        'quantity' => 1,
        'name'     => 'Biaya Jasa Aplikasi',
    ];

    // 2. Format struktur payload standar Midtrans API
    $transaction_data = [
        'transaction_details' => [
            'order_id'     => $order_id,
            'gross_amount' => (int) $total_bayar,
        ],
        'customer_details' => [
            'first_name'   => $user_data['nama'] ?? 'Pelanggan Japak',
            'email'        => $_SESSION['user_email'] ?? 'rezimeizafani@gmail.com',
            'shipping_address' => [
                'first_name' => $user_data['nama'] ?? 'Pelanggan Japak',
                'address'    => $alamat_lengkap
            ]
        ],
        'item_details' => $item_details
    ];

    // 3. Autentikasi Kunci Server via Base64 Standard
    $server_key = 'Mid-server-iulweBOSQLgERwUpG45nYDHD';
    $base64_key = base64_encode(trim($server_key) . ':');

    // 4. Request API Token Snap menggunakan Native cURL
    $url = "https://app.sandbox.midtrans.com/snap/v1/transactions";
    $ch  = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transaction_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . $base64_key
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 5. Olah response token dari Midtrans
    $result_data = json_decode($response, true);
    $snapToken   = $result_data['token'] ?? null;

    if ($http_code === 201 && !empty($snapToken)) {
        
        $koneksi->begin_transaction();
        try {
            // Simpan ke database dengan status pending, sekaligus rekam token snap-nya
            $ins = $koneksi->prepare("
                INSERT INTO transaksi 
                    (order_id, user_id, total_harga, biaya_aplikasi, total_bayar,
                     metode_bayar, status_bayar, status_pesanan,
                     nama_penerima, alamat_pengiriman, catatan, midtrans_token)
                VALUES (?, ?, ?, ?, ?, 'midtrans', 'pending', 'dikemas', ?, ?, ?, ?)
            ");
            $ins->bind_param("sidddssss",
                $order_id, $user_id, $total_harga,
                $biaya_aplikasi, $total_bayar,
                $user_data['nama'], $alamat_lengkap, $catatan, $snapToken
            );
            $ins->execute();
            $transaksi_id = $koneksi->insert_id;

            // Simpan item-item ke database transaksi_item
            foreach ($keranjang as $item) {
                $subtotal = floatval($item['harga_produk']) * intval($item['jumlah']);
                $ins_item = $koneksi->prepare("
                    INSERT INTO transaksi_item 
                        (transaksi_id, order_id, produk_id, nama_produk, foto_produk, ukuran, harga_satuan, jumlah, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins_item->bind_param("isisssdid",
                    $transaksi_id, $order_id,
                    $item['id_produk'], $item['nama_produk'], $item['foto_produk'],
                    $item['ukuran'], $item['harga_produk'],
                    $item['jumlah'], $subtotal
                );
                $ins_item->execute();
            }

            // Kosongkan keranjang di database
            $del = $koneksi->prepare("DELETE FROM keranjang_db WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();

            // Kosongkan session keranjang
            $_SESSION['keranjang'] = [];

            $koneksi->commit();

            echo json_encode([
                'status'     => 'success',
                'metode'     => 'midtrans',
                'snap_token' => $snapToken,
                'order_id'   => $order_id,
            ]);

        } catch (Exception $e) {
            $koneksi->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage()]);
        }
        
    } else {
        // Jika request token gagal, tangkap pesan error dari API Midtrans
        $error_msg = $result_data['error_messages'][0] ?? 'Gagal terkoneksi ke Midtrans API.';
        echo json_encode(['status' => 'error', 'message' => 'Midtrans Error: ' . $error_msg]);
    }
}
?>