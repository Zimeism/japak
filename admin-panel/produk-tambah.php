<?php 
require_once 'config/database.php';

$message = '';

// Ambil data kategori untuk dipasang di dropdown form
$kategori_res = $db->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = htmlspecialchars(trim($_POST['nama_produk']));
    $id_kategori = isset($_POST['id_kategori']) ? intval($_POST['id_kategori']) : 0;
    $harga       = floatval($_POST['harga']);
    $stok        = intval($_POST['stok']);
    
    if (empty($nama_produk)) {
        $message = "<div class='alert alert-danger'>Nama produk tidak boleh kosong!</div>";
    } elseif ($id_kategori <= 0) {
        $message = "<div class='alert alert-danger'>Silakan pilih kategori terlebih dahulu. Jika kosong, isi data kategori di menu Tambah Kategori.</div>";
    } else {
        
        $gambar_name = ''; 
        
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp    = $_FILES['gambar']['tmp_name'];
            $orig_name   = $_FILES['gambar']['name'];
            $file_size   = $_FILES['gambar']['size'];
            
            $file_ext    = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($file_ext, $allowed_ext)) {
                if ($file_size <= 2 * 1024 * 1024) { 
                    $gambar_name = uniqid('prod_', true) . '.' . $file_ext;
                    $upload_dir  = 'uploads/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    if (!move_uploaded_file($file_tmp, $upload_dir . $gambar_name)) {
                        $message = "<div class='alert alert-danger'>Gagal memindahkan file gambar ke folder uploads.</div>";
                        $gambar_name = ''; 
                    }
                } else {
                    $message = "<div class='alert alert-danger'>Ukuran gambar terlalu besar! Maksimal 2MB.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>Format gambar salah! Hanya boleh JPG, JPEG, PNG, atau WEBP.</div>";
            }
        }

        if (empty($message)) {
            $query = "INSERT INTO produk (nama_produk, id_kat, harga, stok, gambar) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if ($stmt === false) {
                $message = "<div class='alert alert-danger'>Struktur query tidak sesuai dengan database. MySQL Error: " . $db->error . "</div>";
            } else {
                $stmt->bind_param("siids", $nama_produk, $id_kategori, $harga, $stok, $gambar_name);
                
                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>🎉 Produk <b>$nama_produk</b> berhasil diterbitkan!</div>";
                } else {
                    if (!empty($gambar_name) && file_exists('uploads/' . $gambar_name)) {
                        unlink('uploads/' . $gambar_name);
                    }
                    $message = "<div class='alert alert-danger'>Gagal menyimpan ke database Aiven: " . $stmt->error . "</div>";
                }
                $stmt->close();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Tambah Produk Baru</h5>
            </div>
            <div class="card-body">
                
                <?php if (!empty($message)) echo $message; ?>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php 
                            if ($kategori_res && $kategori_res->num_rows > 0) {
                                while($kat = $kategori_res->fetch_assoc()) {
                                    // Menggunakan id_kategori sesuai struktur tabel kategori yang baru
                                    echo '<option value="' . $kat['id_kategori'] . '">' . $kat['nama_kategori'] . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Harga (Rp)</label>
                            <input type="number" name="harga" class="form-control" required min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stok" class="form-control" required min="0">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Foto Produk</label>
                        <input type="file" name="gambar" class="form-control" accept="image/*" required>
                        <small class="text-muted">Format didukung: JPG, JPEG, PNG, WEBP. Maks 2MB.</small>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Publish Produk</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>