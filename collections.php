<?php
session_start();
require_once 'admin-panel/config/database.php'; 

$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

if (!isset($_SESSION['user_id'])) {
    header("Location: index1.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Mengambil data produk gabungan dari tabel produk asli berdasar koleksi user
$query = "SELECT p.* FROM produk p 
          JOIN collections c ON p.id = c.product_id 
          WHERE c.user_id = ? ORDER BY c.created_at DESC";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Koleksi Saya | Roemah Raga Nusantara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfc; }
        .serif { font-family: 'Playfair Display', serif; }
        .red-gradient { background: linear-gradient(135deg, #991b1b 0%, #450a0a 100%); }
        .nav-link:after { content: ''; display: block; width: 0; height: 2px; background: #991b1b; transition: width .3s; }
        .nav-link:hover:after { width: 100%; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

<?php require_once 'navbar.php'; ?>

    <main class="container mx-auto px-6 py-12 max-w-7xl">
        <div class="border-b border-gray-200 pb-5 mb-8">
            <h1 class="text-3xl font-serif text-amber-900 font-bold">Koleksi Favorit Saya</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar produk raga pilihan yang Anda simpan</p>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php while($product = $result->fetch_assoc()): ?>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden p-4 relative group hover:shadow-md transition-all">
                        
                        <div class="w-full h-64 bg-gray-50 rounded-lg overflow-hidden flex items-center justify-center mb-4">
                            <img src="admin-panel/uploads/<?= htmlspecialchars($product['gambar'] ?? ($product['image'] ?? 'default.jpg')); ?>" 
                                 alt="<?= htmlspecialchars($product['nama_produk'] ?? $product['name']); ?>" 
                                 class="max-h-full max-w-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>

                        <h3 class="text-base font-semibold text-gray-800 line-clamp-1"><?= htmlspecialchars($product['nama_produk'] ?? $product['name']); ?></h3>
                        <p class="text-red-700 font-bold mt-1">Rp <?= number_format($product['harga'] ?? $product['price'], 0, ',', '.'); ?></p>
                        
                        <div class="grid grid-cols-5 gap-2 mt-4">
                            <a href="detail.php?id=<?= $product['id']; ?>" 
                               class="col-span-4 text-center bg-red-950 hover:bg-red-900 text-white font-medium py-2 px-3 rounded-lg text-sm transition-colors">
                                Lihat Detail
                            </a>
                            <a href="remove_from_collection.php?id=<?= $product['id']; ?>" 
                               class="col-span-1 border border-red-200 text-red-600 hover:bg-red-50 flex items-center justify-center rounded-lg transition-colors" 
                               onclick="return confirm('Hapus dari koleksi?')">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-16 bg-white rounded-xl border border-gray-100 p-8 shadow-sm">
                <div class="text-gray-300 text-6xl mb-4">
                    <i class="fa-solid fa-heart-crack"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-700">Belum Ada Koleksi</h3>
                <p class="text-gray-400 text-sm mt-1 mb-6">Klik tombol hati pada produk untuk menyimpannya di sini.</p>
                <a href="index1.php" class="bg-red-950 hover:bg-red-900 text-white px-6 py-2.5 rounded-lg font-medium text-sm inline-block transition-colors">
                    Kembali Belanja
                </a>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>