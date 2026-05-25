<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Menghubungkan ke database sesuai contoh script tambah produk kamu
require_once 'config/database.php';

// ==================================================
// PROSES UPDATE STATUS MANUAL (JIKA TOMBOL DIKLIK)
// ==================================================
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $transaksi_id = isset($_POST['transaksi_id']) ? intval($_POST['transaksi_id']) : 0;
    $status_baru = isset($_POST['status_baru']) ? $_POST['status_baru'] : '';

    // Validasi status untuk menghindari manipulasi request ilegal
    $allowed_status = ['dikemas', 'dikirim', 'selesai'];
    
    if ($transaksi_id <= 0 || !in_array(strtolower($status_baru), $allowed_status)) {
        $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Gagal:</strong> Data ID transaksi atau status tidak valid!
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>';
    } else {
        // Disamakan format kapitalisasinya dengan pesanan.php milik client ('Dikemas', 'Dikirim', 'Selesai')
        $status_final = ucfirst(strtolower($status_baru)); 
        
        $stmt_update = $db->prepare("UPDATE transaksi SET status_pesanan = ? WHERE id = ?");
        $stmt_update->bind_param("si", $status_final, $transaksi_id);
        
        if ($stmt_update->execute()) {
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            🎉 Status Transaksi <strong>#'.$transaksi_id.'</strong> berhasil diperbarui menjadi <strong>'.$status_final.'</strong>!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        } else {
            $message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Gagal:</strong> Tidak dapat memperbarui status di database. MySQL: ' . $db->error . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>';
        }
        $stmt_update->close();
    }
}

// ==================================================
// QUERY DATA PAKET AKTIF (Menampilkan Antrean Pengiriman)
// ==================================================
$query_paket = "
    SELECT t.id, t.order_id, t.created_at, t.status_pesanan, t.total_bayar,
           ti.nama_produk, ti.jumlah, ti.ukuran
    FROM transaksi t
    LEFT JOIN transaksi_item ti ON t.id = ti.transaksi_id
    WHERE LOWER(TRIM(t.status_pesanan)) IN ('dikemas', 'pending', 'proses', 'dikirim')
    ORDER BY t.created_at ASC
";
$result_paket = $db->query($query_paket);

// Memanggil template header sesuai contoh file kamu
include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="row mb-3 mt-2">
    <div class="col-12">
        <h2 class="fw-bold text-dark">📦 Manager Paket & Pengiriman</h2>
        <p class="text-muted">Konfirmasi status pengemasan dan keberangkatan paket pesanan secara manual untuk diteruskan ke client.</p>
    </div>
</div>

<?php if (!empty($message)) echo $message; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 m-0 text-white"><i class="fas fa-box-open me-2"></i>Antrean Validasi Paket</h5>
        <span class="badge bg-primary fs-6"><?= $result_paket ? $result_paket->num_rows : 0; ?> Paket Aktif</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">ID Transaksi / Waktu</th>
                        <th>Detail Item</th>
                        <th>Total Bayar</th>
                        <th class="text-center">Status Saat Ini</th>
                        <th class="text-center pe-3" style="width: 250px;">Aksi Cepat Admin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_paket && $result_paket->num_rows > 0): ?>
                        <?php while($paket = $result_paket->fetch_assoc()): 
                            $status_current = strtolower(trim($paket['status_pesanan']));
                        ?>
                            <tr>
                                <td class="ps-3">
                                    <span class="font-monospace fw-bold d-block text-primary"><?= htmlspecialchars($paket['order_id']); ?></span>
                                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($paket['created_at'])); ?></small>
                                </td>
                                
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($paket['nama_produk'] ?? 'Custom Order / Jasa'); ?></div>
                                    <small class="text-secondary">Ukuran: <?= htmlspecialchars($paket['ukuran'] ?? '-'); ?> | Qty: <?= intval($paket['jumlah'] ?? 1); ?> pcs</small>
                                </td>
                                
                                <td class="fw-bold text-success">
                                    Rp <?= number_format($paket['total_bayar'], 0, ',', '.'); ?>
                                </td>
                                
                                <td class="text-center">
                                    <?php if ($status_current === 'dikirim'): ?>
                                        <span class="badge bg-info text-white px-3 py-2 text-capitalize"><i class="fas fa-truck me-1"></i> Dikirim</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark px-3 py-2 text-capitalize"><i class="fas fa-box me-1"></i> <?= htmlspecialchars($paket['status_pesanan']); ?></span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-center pe-3">
                                    <div class="d-inline-flex gap-2 justify-content-center">
                                        <form method="POST" action="">
                                            <input type="hidden" name="transaksi_id" value="<?= $paket['id']; ?>">
                                            
                                            <?php if ($status_current === 'dikemas' || $status_current === 'pending' || $status_current === 'proses'): ?>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-primary fw-bold">
                                                    <i class="fas fa-shipping-fast me-1"></i> Kirim Paket
                                                </button>
                                                <input type="hidden" name="status_baru" value="dikirim">
                                            <?php endif; ?>

                                            <?php if ($status_current === 'dikirim'): ?>
                                                <button type="submit" name="update_status" class="btn btn-sm btn-success fw-bold">
                                                    <i class="fas fa-check-circle me-1"></i> Set Selesai
                                                </button>
                                                <input type="hidden" name="status_baru" value="selesai">
                                            <?php endif; ?>
                                        </form>
                                        
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                Ubah Ke
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <form method="POST" action=""><input type="hidden" name="transaksi_id" value="<?= $paket['id']; ?>"><input type="hidden" name="status_baru" value="dikemas"><button type="submit" name="update_status" class="dropdown-item">📦 Dikemas</button></form>
                                                </li>
                                                <li>
                                                    <form method="POST" action=""><input type="hidden" name="transaksi_id" value="<?= $paket['id']; ?>"><input type="hidden" name="status_baru" value="dikirim"><button type="submit" name="update_status" class="dropdown-item">🚚 Dikirim</button></form>
                                                </li>
                                                <li>
                                                    <form method="POST" action=""><input type="hidden" name="transaksi_id" value="<?= $paket['id']; ?>"><input type="hidden" name="status_baru" value="selesai"><button type="submit" name="update_status" class="dropdown-item">✅ Selesai</button></form>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="fas fa-boxes fa-3x d-block mb-3 text-secondary opacity-50"></i>
                                Tidak ada antrean paket pengiriman aktif saat ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
// Memanggil template footer sesuai contoh file kamu
include 'includes/footer.php'; 
?>