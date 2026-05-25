<?php
require_once 'config/database.php';

//die("FILE INI YANG DIEDIT");

// Ambil data produk dan nama kategorinya
$query = "SELECT produk.*, kategori.nama_kategori 
          FROM produk 
          LEFT JOIN kategori ON produk.id_kat = kategori.id_kategori 
          ORDER BY produk.id DESC";

$result = $db->query($query);

include 'includes/header.php';
?>

<?php if (isset($_GET['status']) && $_GET['status'] == 'success_delete'): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        🗑️ <b>Sukses!</b> Produk berhasil dihapus dari sistem.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Daftar Produk</h5>
        <a href="produk-tambah.php" class="btn btn-sm btn-light">Tambah Produk Baru</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<tr>';
                            echo '<td>' . $row['id'] . '</td>';
                            echo '<td class="fw-bold">' . htmlspecialchars($row['nama_produk']) . '</td>';
                            echo '<td><span class="badge bg-secondary">' . htmlspecialchars($row['nama_kategori'] ?? 'Tanpa Kategori') . '</span></td>';
                            echo '<td>Rp ' . number_format($row['harga'], 0, ',', '.') . '</td>';
                            echo '<td>' . $row['stok'] . ' unit</td>';
                            
                            // Tombol Hapus menuju produk-hapus.php
                            echo '<td class="text-center">';
                            echo '<a href="produk-hapus.php?id=' . $row['id'] . '" class="btn btn-sm btn-danger px-3 fw-bold" onclick="return confirm(\'Apakah kamu yakin ingin menghapus produk ' . htmlspecialchars($row['nama_produk']) . ' ini?\')">';
                            echo '🗑️ Hapus';
                            echo '</a>';
                            echo '</td>';
                            
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada data produk.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>