<?php
session_start();
// 1. Mengintegrasikan koneksi database dari admin-panel Anda
require_once 'admin-panel/config/database.php';

// 2. Mengambil parameter ID dari URL secara aman
$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 3. Query untuk mengambil data produk utama berdasarkan ID, digabung dengan nama kategorinya
$query_utama = "SELECT produk.*, kategori.nama_kategori 
                FROM produk 
                LEFT JOIN kategori ON produk.id_kat = kategori.id_kategori 
                WHERE produk.id = $id_produk";
$result_utama = $db->query($query_utama);

// Jika ID tidak ditemukan atau tidak valid, lempar kembali ke index
if (!$result_utama || $result_utama->num_rows === 0) {
    header("Location: index1.php");
    exit();
}

// Menyimpan data produk utama ke dalam variabel $product
$product = $result_utama->fetch_assoc();

// 4. Query untuk produk serupa (4 produk acak selain produk utama yang sedang dilihat)
$query_serupa = "SELECT * FROM produk WHERE id != $id_produk ORDER BY RAND() LIMIT 4";
$result_serupa = $db->query($query_serupa);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['nama_produk']) ?> | Roemah Raga Nusantara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght=0,700;1,700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfc; }
        .serif { font-family: 'Playfair Display', serif; }
        .red-gradient { background: linear-gradient(135deg, #991b1b 0%, #450a0a 100%); }
        .nav-link:after { content: ''; display: block; width: 0; height: 2px; background: #991b1b; transition: width .3s; }
        .nav-link:hover:after { width: 100%; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <input type="hidden" id="prod_id" value="<?= $product['id']; ?>">
    <input type="hidden" id="prod_nama" value="<?= htmlspecialchars($product['nama_produk']); ?>">
    <input type="hidden" id="prod_harga" value="<?= $product['harga']; ?>">
    <input type="hidden" id="prod_foto" value="<?= htmlspecialchars($product['gambar']); ?>">
    <input type="hidden" id="prod_stok" value="<?= intval($product['stok']); ?>">

    <nav class="sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 px-4 md:px-12 py-4 flex justify-between items-center">
        <div class="flex items-center gap-8">
            <a href="index1.php" class="serif text-2xl font-bold text-red-900 tracking-tighter">Roemah Raga</a>
            <div class="hidden md:flex gap-6 text-sm font-semibold text-gray-500 uppercase tracking-widest">
                <a href="index1.php" class="nav-link">Home</a>
                <a href="#" class="nav-link text-red-900 border-b-2 border-red-900">Shop</a>
                <a href="#" class="nav-link">Collections</a>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <a href="keranjang.php" class="relative cursor-pointer block hover:scale-105 transition-transform">
                <i data-lucide="shopping-bag" class="w-6 h-6 text-gray-600 hover:text-red-900 transition-colors"></i>
                <span id="badge-keranjang" class="absolute -top-1 -right-1 bg-red-700 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-bold">
                    <?= isset($_SESSION['keranjang']) ? count($_SESSION['keranjang']) : 0; ?>
                </span>
            </a>
            <button class="bg-red-900 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg shadow-red-200">Akun Saya</button>
        </div>
    </nav>

    <main class="container mx-auto px-4 md:px-12 py-10">
        <div class="flex items-center gap-2 text-xs text-gray-400 uppercase tracking-widest mb-8">
            <a href="index1.php" class="hover:text-red-900">Home</a>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span class="text-red-900 font-bold">Detail Produk</span>
        </div>

        <div class="flex flex-col md:flex-row gap-12 items-start">
            <div class="w-full md:w-1/2">
                <div class="bg-white p-4 rounded-[40px] shadow-2xl border border-gray-100 relative overflow-hidden group">
                    <?php 
                    $gambar_utama = !empty($product['gambar']) ? 'admin-panel/uploads/' . $product['gambar'] : 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=800';
                    ?>
                    <img src="<?= $gambar_utama ?>" class="w-full h-[600px] object-cover rounded-[32px] transition-transform duration-700 group-hover:scale-105" alt="Product Image">
                    <div class="absolute top-8 left-8">
                        <span class="bg-red-900 text-white px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest">Premium</span>
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/2 md:sticky md:top-28">
                <div class="flex flex-col">
                    <span class="text-red-900 font-bold text-sm uppercase tracking-[0.3em] mb-2">
                        <?= htmlspecialchars($product['nama_kategori'] ?? 'Premium Series') ?>
                    </span>
                    <h1 class="serif text-5xl md:text-6xl text-gray-900 leading-tight mb-4">
                        <?= htmlspecialchars($product['nama_produk']) ?>
                    </h1>
                    
                    <div class="flex items-center gap-4 mb-8">
                        <p class="text-3xl font-bold text-red-900 italic">Rp <?= number_format($product['harga'], 0, ',', '.') ?></p>
                        <div class="h-1 w-12 bg-gray-200 rounded-full"></div>
                        <span class="text-gray-400 text-sm">Stok: <?= intval($product['stok']) ?> unit</span>
                    </div>

                    <p class="text-gray-500 leading-relaxed text-lg mb-10 pb-10 border-b border-gray-100">
                        <?= !empty($product['deskripsi']) ? htmlspecialchars($product['deskripsi']) : "Koleksi warisan Nusantara premium pilihan yang dirancang eksklusif untuk memberikan kesan anggun, berwibawa, dan berkelas bagi penggunanya." ?>
                    </p>

                    <div class="mb-10">
                        <h4 class="font-bold text-sm uppercase tracking-widest text-gray-400 mb-4">Pilih Ukuran</h4>
                        <div class="flex gap-4">
                            <?php $sizes = ['M', 'L', 'XL']; foreach($sizes as $index => $s): ?>
                            <button onclick="selectSize(this, '<?= $s ?>')" class="size-btn w-14 h-14 flex items-center justify-center border-2 rounded-2xl font-bold transition-all <?= $index === 0 ? 'bg-red-900 text-white border-red-900' : 'border-gray-100 text-gray-900 hover:border-red-900 hover:text-red-900' ?>">
                                <?= $s ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-6 items-center">
                        <div class="flex items-center bg-gray-100 rounded-2xl p-2 w-full sm:w-auto">
                            <button onclick="changeQty(-1)" class="w-12 h-12 flex items-center justify-center hover:text-red-900 font-bold">-</button>
                            <input type="text" id="qty" value="1" class="bg-transparent w-12 text-center font-bold outline-none" readonly>
                            <button onclick="changeQty(1)" class="w-12 h-12 flex items-center justify-center hover:text-red-900 font-bold">+</button>
                        </div>
                        
                        <button onclick="tambahKeKeranjang()" class="flex-1 w-full red-gradient text-white py-4 px-12 rounded-2xl font-bold text-lg hover:scale-105 active:scale-98 transition-all shadow-xl shadow-red-200">
                            Tambah ke Keranjang
                        </button>

                        <button class="p-4 border-2 border-gray-100 rounded-2xl hover:bg-red-50 hover:border-red-900 transition-all group">
                            <i data-lucide="heart" class="w-6 h-6 text-gray-400 group-hover:text-red-900"></i>
                        </button>
                    </div>

                    <div class="mt-12 p-6 bg-red-50 rounded-3xl flex items-center gap-4 border border-red-100">
                        <div class="bg-red-900 p-3 rounded-2xl text-white">
                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h5 class="font-bold text-sm text-red-900 uppercase tracking-tighter">Kualitas Nusantara Terjamin</h5>
                            <p class="text-xs text-red-700/70">Produk ini merupakan karya pengrajin lokal terpilih.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="mt-24">
            <h3 class="serif text-3xl mb-8">Lengkapi Gayamu</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php 
                if ($result_serupa && $result_serupa->num_rows > 0):
                    while ($serupa = $result_serupa->fetch_assoc()):
                        $gambar_serupa = !empty($serupa['gambar']) ? 'admin-panel/uploads/' . $serupa['gambar'] : 'https://images.unsplash.com/photo-1617627143750-d86bc21e42bb?w=400';
                ?>
                <a href="detail.php?id=<?= $serupa['id'] ?>" class="group block">
                    <div class="group bg-white rounded-[32px] overflow-hidden border border-gray-100 p-2 hover:shadow-xl transition-all">
                        <div class="h-60 bg-gray-100 rounded-[24px] mb-4 overflow-hidden">
                            <img src="<?= $gambar_serupa ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        </div>
                        <div class="px-3 pb-3">
                            <h4 class="font-bold text-sm truncate"><?= htmlspecialchars($serupa['nama_produk']) ?></h4>
                            <p class="text-red-900 font-bold text-sm">Rp <?= number_format($serupa['harga'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                </a>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="col-span-full text-center text-gray-400 py-6 italic">
                        Belum ada koleksi pendukung lainnya.
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div id="toast-notif" class="fixed bottom-10 right-10 z-[100] transform translate-y-20 opacity-0 pointer-events-none transition-all duration-500 ease-out flex items-center gap-4 bg-white border border-gray-100 shadow-2xl p-5 rounded-3xl max-w-sm">
        <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-inner">
            <i data-lucide="check-circle-2" class="w-6 h-6"></i>
        </div>
        <div class="flex-1">
            <h5 class="font-bold text-gray-900 text-sm">Berhasil Masuk Keranjang</h5>
            <p class="text-xs text-gray-400 mt-0.5" id="toast-msg">Produk siap dipesan.</p>
        </div>
        <a href="keranjang.php" class="text-xs font-bold text-red-900 bg-red-50 px-3 py-2 rounded-xl hover:bg-red-100 transition shadow-sm">
            Lihat
        </a>
    </div>

    <footer class="bg-white border-t border-gray-100 py-12 mt-20">
        <div class="container mx-auto px-12 text-center text-gray-300 text-[10px] uppercase tracking-widest">
            &copy; <?= date('Y'); ?> Roemah Raga Nusantara.
        </div>
    </footer>

    <script>
        lucide.createIcons();

        // Menyimpan status default ukuran terpiih (Default: M)
        let selectedSize = 'M';

        function selectSize(btn, size) {
            selectedSize = size;
            document.querySelectorAll('.size-btn').forEach(b => {
                b.classList.remove('bg-red-900', 'text-white', 'border-red-900');
                b.classList.add('border-gray-100', 'text-gray-900');
            });
            btn.classList.remove('border-gray-100', 'text-gray-900');
            btn.classList.add('bg-red-900', 'text-white', 'border-red-900');
        }

        function changeQty(amount) {
            let qty = document.getElementById('qty');
            let stokMax = parseInt(document.getElementById('prod_stok').value);
            let val = parseInt(qty.value);
            let newVal = val + amount;
            if (newVal < 1) newVal = 1;
            if (newVal > stokMax) {
                alert('Stok tersedia hanya ' + stokMax + ' unit.');
                newVal = stokMax;
            }
            qty.value = newVal;
        }
        // FUNGSI UTAMA AJAX ADD TO CART
        function tambahKeKeranjang() {

            // ✅ TAMBAHAN: Validasi stok sebelum kirim
            const stokMax = parseInt(document.getElementById('prod_stok').value);
            const jumlah  = parseInt(document.getElementById('qty').value);

            if (jumlah > stokMax) {
                alert('Jumlah melebihi stok tersedia (' + stokMax + ' unit).');
                return; // hentikan, tidak jadi kirim ke server
            }

            const formData = new FormData();
            formData.append('ajax_add', '1');
            formData.append('id_produk', document.getElementById('prod_id').value);
            formData.append('nama_produk', document.getElementById('prod_nama').value);
            formData.append('harga', document.getElementById('prod_harga').value);
            formData.append('gambar', document.getElementById('prod_foto').value);
            formData.append('ukuran', selectedSize);
            formData.append('jumlah', document.getElementById('qty').value);

            fetch('keranjang.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === 'success') {
                    // Update jumlah angka di badge icon atas secara langsung
                    document.getElementById('badge-keranjang').innerText = data.total_item;
                    
                    // Set isi teks info toast
                    document.getElementById('toast-msg').innerText = `${document.getElementById('prod_nama').value} (${selectedSize}) sebanyak ${document.getElementById('qty').value} unit.`;
                    
                    // Jalankan Animasi Toast Muncul
                    const toast = document.getElementById('toast-notif');
                    toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
                    toast.classList.add('translate-y-0', 'opacity-100');

                    setTimeout(() => {
                        toast.classList.remove('translate-y-0', 'opacity-100');
                        toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
                    }, 4000);
                }
            })
            .catch(err => console.error('Gagal menambahkan data:', err));
        }
    </script>
</body>
</html>