<?php 
require_once 'config/database.php';

$message = '';

// --- LOGIKA PROSES TAMBAH KATEGORI (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kategori = htmlspecialchars(trim($_POST['nama_kategori']));

    if (!empty($nama_kategori)) {
        $stmt = $db->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
        $stmt->bind_param("s", $nama_kategori);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            Kategori berhasil ditambahkan!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            Gagal menambahkan kategori.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        }
        $stmt->close();
    }
}

// --- LOGIKA PROSES HAPUS KATEGORI (GET) ---
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    if ($delete_id > 0) {
        $stmt = $db->prepare("DELETE FROM kategori WHERE id_kategori = ?");
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                            Kategori berhasil dihapus!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                            Gagal menghapus kategori atau data sedang digunakan oleh produk.
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>";
        }
        $stmt->close();
    }
}

// Ambil data kategori terbaru untuk ditampilkan di tabel
$kategori_query = $db->query("SELECT * FROM kategori ORDER BY id_kategori DESC");

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Tambah Kategori</h5>
            </div>
            <div class="card-body">
                <?= $message; ?>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label">Nama Kategori</label>
                        <input type="text" name="nama_kategori" class="form-control" required placeholder="Contoh: Elektronik">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Kategori</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Daftar Kategori</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3" style="width: 80px;">No</th>
                                <th>Nama Kategori</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($kategori_query && $kategori_query->num_rows > 0): 
                                $no = 1;
                                while ($row = $kategori_query->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="px-3"><?= $no++; ?></td>
                                    <td><strong><?= htmlspecialchars($row['nama_kategori']); ?></strong></td>
                                    <td class="text-center">
                                        <a href="?delete_id=<?= $row['id_kategori']; ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kategori \'<?= addslashes($row['nama_kategori']); ?>\'? Tindakan ini tidak dapat dibatalkan.');">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Belum ada data kategori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>