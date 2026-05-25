<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
$koneksi = isset($db) ? $db : $conn;

// ==========================================
// 1. QUERY UNTUK DATA CHART (BULANAN) - FIXED CASE SENSITIVE
// ==========================================
$query_chart = "
    SELECT 
        DATE_FORMAT(created_at, '%M %Y') AS bulan,
        SUM(total_bayar) AS total_pendapatan
    FROM transaksi
    WHERE LOWER(TRIM(status_pesanan)) IN ('dikemas', 'pending', 'proses', 'dikirim', 'selesai', 'success')
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY created_at ASC
    LIMIT 6
";
$result_chart = $koneksi->query($query_chart);

$labels_bulan = [];
$data_pendapatan = [];

if ($result_chart) {
    while ($row_chart = $result_chart->fetch_assoc()) {
        $labels_bulan[] = $row_chart['bulan'];
        $data_pendapatan[] = (int)$row_chart['total_pendapatan'];
    }
}

// ==========================================
// 2. QUERY UNTUK TABEL RIWAYAT TRANSAKSI MASUK
// ==========================================
$query = "SELECT t.*, ti.nama_produk, ti.jumlah 
          FROM transaksi t
          LEFT JOIN transaksi_item ti ON t.id = ti.transaksi_id
          ORDER BY t.created_at DESC 
          LIMIT 20";
$result = $koneksi->query($query);

include 'includes/header.php';
?>

<h2 class="mb-4 fw-bold text-secondary">Dashboard Ringkasan Penjualan</h2>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary me-2"></i>Grafik Tren Pendapatan</h5>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 320px; width: 100%;">
            <canvas id="omsetChart"></canvas>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Riwayat Transaksi Masuk</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-borderless mb-0">
                <thead class="table-light border-bottom">
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Nama Produk</th>
                        <th>Jumlah</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="align-middle">
                                <td><span class="text-monospace fw-bold text-secondary"><?= htmlspecialchars($row['order_id']); ?></span></td>
                                <td><?= date('d M Y H:i', strtotime($row['created_at'])); ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['nama_produk'] ?? 'Produk Jasa/Sewa'); ?></td>
                                <td><?= intval($row['jumlah'] ?? 1); ?> pcs</td>
                                <td class="text-success fw-bold">Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $status_clean = strtolower(trim($row['status_pesanan']));
                                    if ($status_clean === 'selesai' || $status_clean === 'success'): 
                                    ?>
                                        <span class="badge bg-success">Selesai</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark text-capitalize"><?= htmlspecialchars($row['status_pesanan']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi masuk.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Oper data array dari PHP ke bentuk JSON JavaScript
const dataLabels = <?= json_encode($labels_bulan); ?>;
const dataOmset = <?= json_encode($data_pendapatan); ?>;

const ctx = document.getElementById('omsetChart').getContext('2d');
const omsetChart = new Chart(ctx, {
    type: 'line', 
    data: {
        labels: dataLabels,
        datasets: [{
            label: 'Omset Penjualan (Rp)',
            data: dataOmset,
            backgroundColor: 'rgba(13, 110, 253, 0.08)', 
            borderColor: 'rgba(13, 110, 253, 1)', 
            borderWidth: 3,
            pointBackgroundColor: 'rgba(13, 110, 253, 1)',
            pointRadius: 5,
            pointHoverRadius: 7,
            tension: 0.3, 
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        if (value >= 1000000) {
                            return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                        } else if (value >= 1000) {
                            return 'Rp ' + (value / 1000).toFixed(0) + ' Rb';
                        }
                        return 'Rp ' + value;
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let val = context.raw;
                        return ' Total Omset: Rp ' + val.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>