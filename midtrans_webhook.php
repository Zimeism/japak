<?php
// japak/midtrans_webhook.php

// 1. Ambil koneksi database dan konfigurasi Midtrans
require_once 'admin-panel/config/database.php';
require_once 'config-midtrans.php';

header('Content-Type: application/json');

try {
    // 2. Tangkap data notifikasi dari server Midtrans
    $notif = new \Midtrans\Notification();

    $transaction  = $notif->transaction_status;
    $order_id     = $notif->order_id;
    $status_code  = $notif->status_code;
    $gross_amount = $notif->gross_amount;

    // 3. VALIDASI KEAMANAN (SHA512 Signature Verification)
    // Mencocokkan Signature Key dari Midtrans dengan Server Key lokal Anda
    $server_key = \Midtrans\Config::$serverKey;
    $local_signature = hash("sha512", $order_id . $status_code . $gross_amount . $server_key);

    if ($local_signature !== $notif->signature_key) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Validasi Signature Palsu! Akses Ditolak.']);
        exit();
    }

    // 4. PEMETAAN STATUS TRANSAKSI MIDTRANS
    $status_final = 'pending';

    if ($transaction == 'settlement' || $transaction == 'capture') {
        $status_final = 'sukses';
    } else if (in_array($transaction, ['deny', 'expire', 'cancel'])) {
        $status_final = 'gagal';
    }

    // 5. UPDATE STATUS KE DATABASE LOKAL JAPAK
    // Ganti nama tabel 'transaksi' dan kolomnya jika Anda menggunakan nama struktur tabel yang berbeda
    $query_check = $db->prepare("SELECT order_id FROM transaksi WHERE order_id = ?");
    $query_check->bind_param("s", $order_id);
    $query_check->execute();
    $result_check = $query_check->get_result();

    if ($result_check->num_rows > 0) {
        // Jika data transaksi ditemukan, lakukan update status pembayaran
        $stmt = $db->prepare("UPDATE transaksi SET status_pembayaran = ? WHERE order_id = ?");
        $stmt->bind_param("ss", $status_final, $order_id);
        $stmt->execute();
        
        echo json_encode(['status' => 'success', 'message' => 'Status database berhasil diperbarui menjadi ' . $status_final]);
    } else {
        // Log jika tabel belum ada atau order_id tidak terdaftar di DB (Sangat berguna saat tahap simulasi/testing)
        echo json_encode(['status' => 'mock_success', 'message' => 'Signature valid, tetapi order_id tidak ditemukan di database.']);
    }

    http_response_code(200);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}