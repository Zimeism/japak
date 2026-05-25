<?php
// 1. WAJIB: Nyalakan session di baris paling pertama sebelum kode lain!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Koneksi database
require_once 'admin-panel/config/database.php';

// Selaraskan variabel koneksi
$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

// Status login aktif
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$user_id      = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$user_role    = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Fungsi format rupiah helper
if (!function_exists('rupiah')) {
    function rupiah($angka) {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}

// =========================================================
// FUNGSI HELPER: MUAT KERANJANG DARI DB KE SESSION
// =========================================================
function load_cart_from_db($koneksi, $user_id) {
    // Hanya muat jika session saat ini benar-benar kosong untuk mencegah bentrok saat checkout
    if (!empty($_SESSION['keranjang'])) {
        return;
    }
    
    $_SESSION['keranjang'] = [];
    $stmt = $koneksi->prepare("SELECT * FROM keranjang_db WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $_SESSION['keranjang'][$row['cart_key']] = [
                'id_produk'    => $row['id_produk'],
                'nama_produk'  => $row['nama_produk'],
                'harga_produk' => $row['harga_produk'],
                'foto_produk'  => $row['foto_produk'],
                'ukuran'       => $row['ukuran'],
                'jumlah'       => $row['jumlah'],
            ];
        }
        $stmt->close();
    }
}

// =========================================================
// SINKRONISASI OTOMATIS
// =========================================================
if ($is_logged_in && $user_id > 0 && $user_role !== 'guest') {
    load_cart_from_db($koneksi, $user_id);
}

// Inisialisasi session keranjang jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// =========================================================
// 3. PROSES TAMBAH KE KERANJANG (AJAX dari detail.php)
// =========================================================
if (isset($_POST['ajax_add'])) {
    header('Content-Type: application/json');
    $id     = isset($_POST['id_produk'])   ? intval($_POST['id_produk'])              : 0;
    $nama   = isset($_POST['nama_produk']) ? htmlspecialchars($_POST['nama_produk']) : 'Produk';
    $harga  = isset($_POST['harga'])       ? floatval($_POST['harga'])                : 0;
    $gambar = isset($_POST['gambar'])      ? htmlspecialchars($_POST['gambar'])       : '';
    $ukuran = isset($_POST['ukuran'])      ? htmlspecialchars($_POST['ukuran'])       : 'M';
    $jumlah = isset($_POST['jumlah'])      ? intval($_POST['jumlah'])                : 1;

    $cart_key = $id . '_' . $ukuran;

    if (isset($_SESSION['keranjang'][$cart_key]) && is_array($_SESSION['keranjang'][$cart_key])) {
        $_SESSION['keranjang'][$cart_key]['jumlah'] += $jumlah;
    } else {
        $_SESSION['keranjang'][$cart_key] = [
            'id_produk'    => $id,
            'nama_produk'  => $nama,
            'harga_produk' => $harga,
            'foto_produk'  => $gambar,
            'ukuran'       => $ukuran,
            'jumlah'       => $jumlah
        ];
    }

    if ($is_logged_in && $user_id > 0 && $user_role !== 'guest') {
        $jumlah_baru = $_SESSION['keranjang'][$cart_key]['jumlah'];

        $stmt = $koneksi->prepare("
            INSERT INTO keranjang_db
                (user_id, cart_key, id_produk, nama_produk, harga_produk, foto_produk, ukuran, jumlah)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE jumlah = ?
        ");
        if ($stmt) {
            $stmt->bind_param("isssdssii",
                $user_id, $cart_key, $id, $nama, $harga, $gambar, $ukuran, $jumlah_baru, $jumlah_baru
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    echo json_encode([
        'status'     => 'success',
        'total_item' => count($_SESSION['keranjang'])
    ]);
    exit();
}

// =========================================================
// 4. HAPUS ITEM DARI KERANJANG
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $key_hapus = isset($_GET['key']) ? $_GET['key'] : '';

    if (isset($_SESSION['keranjang'][$key_hapus])) {
        unset($_SESSION['keranjang'][$key_hapus]);
    }

    if ($is_logged_in && $user_id > 0 && $user_role !== 'guest') {
        $stmt = $koneksi->prepare("DELETE FROM keranjang_db WHERE user_id = ? AND cart_key = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $key_hapus);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: keranjang.php");
    exit();
}

// Ambil data keranjang dari session untuk ditampilkan
$keranjang = isset($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];

// Hitung subtotal dengan validasi tipe data
$subtotal = 0;
foreach ($keranjang as $key => $item) {
    if (is_array($item) && isset($item['harga_produk'], $item['jumlah'])) {
        $subtotal += ($item['harga_produk'] * $item['jumlah']);
    } else {
        unset($_SESSION['keranjang'][$key]);
    }
}

$keranjang     = isset($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];
$biaya_layanan = empty($keranjang) ? 0 : 2000;
$total_akhir   = $subtotal + $biaya_layanan;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Saya | Roemah Raga Nusantara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="Mid-client-N28qQTKy7MspiE8w"></script>
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

    <main class="container mx-auto px-4 md:px-12 py-12 max-w-6xl">
        <div class="flex items-center gap-2 text-xs text-gray-400 uppercase tracking-widest mb-8">
            <a href="index1.php" class="hover:text-red-900">Home</a>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span class="text-red-900 font-bold">Keranjang</span>
        </div>

        <h1 class="serif text-4xl md:text-5xl text-red-900 mb-10 tracking-tight">
            Keranjang Belanja
            <span class="text-sm font-sans text-gray-400 block md:inline md:ml-2 font-normal">
                (<?= count($keranjang); ?> Koleksi Terpilih)
            </span>
        </h1>

        <?php if (empty($keranjang)): ?>
            <div class="bg-white rounded-[40px] p-16 text-center border border-gray-100 shadow-2xl max-w-2xl mx-auto">
                <div class="w-20 h-20 bg-red-50 text-red-900 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="shopping-cart" class="w-10 h-10"></i>
                </div>
                <h3 class="serif text-2xl text-gray-800 mb-2">Keranjang Anda Masih Kosong</h3>
                <p class="text-gray-400 max-w-sm mx-auto text-sm leading-relaxed">
                    Kenakan keindahan budaya Nusantara dalam setiap langkahmu. Mari jelajahi koleksi premium kami.
                </p>
                <a href="index1.php" class="inline-block mt-8 bg-red-900 text-white px-10 py-4 rounded-2xl font-bold hover:scale-105 active:scale-95 transition-all shadow-xl shadow-red-100">
                    Mulai Belanja Koleksi
                </a>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-10 items-start">

                <div class="w-full lg:w-3/5 space-y-6">
                    <?php foreach ($keranjang as $key => $k): ?>
                        <?php if (!is_array($k)) continue; ?>
                        <div class="bg-white rounded-[32px] p-6 flex flex-col sm:flex-row gap-6 items-center shadow-sm border border-gray-100 hover:shadow-md transition-shadow relative group">

                            <a href="keranjang.php?action=delete&key=<?= urlencode($key); ?>"
                               onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')"
                               class="absolute top-4 right-4 text-gray-300 hover:text-red-700 transition sm:opacity-0 group-hover:opacity-100 p-2 bg-gray-50 rounded-full sm:top-6 sm:right-6"
                               title="Hapus Item">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </a>

                            <?php 
                                $foto_raw    = $k['foto_produk'] ?? '';
                                $foto_clean  = basename($foto_raw); 
                                $path_gambar = !empty($foto_clean) && $foto_clean !== 'default.jpg'
                                    ? 'admin-panel/uploads/' . $foto_clean
                                    : 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200';
                            ?>
                            <div class="w-28 h-28 flex-shrink-0 rounded-2xl overflow-hidden bg-gray-50 border border-gray-100">
                                <img src="<?= $path_gambar; ?>" class="w-full h-full object-cover" alt="Foto Produk">
                            </div>

                            <div class="flex-1 text-center sm:text-left pr-6">
                                <h2 class="font-bold text-xl text-gray-800 tracking-tight leading-snug">
                                    <?= htmlspecialchars($k['nama_produk']); ?>
                                </h2>
                                <div class="flex flex-wrap justify-center sm:justify-start gap-4 text-xs text-gray-400 mt-2">
                                    <p>Ukuran: <span class="font-bold text-red-900 bg-red-50/60 px-2.5 py-1 rounded-lg ml-1"><?= htmlspecialchars($k['ukuran']); ?></span></p>
                                    <div class="w-1 h-1 bg-gray-300 rounded-full my-auto"></div>
                                    <p>Jumlah: <span class="font-bold text-gray-700 bg-gray-100 px-2.5 py-1 rounded-lg ml-1"><?= intval($k['jumlah']); ?> Unit</span></p>
                                </div>
                                <p class="text-red-900 font-bold text-xl mt-4">
                                    <?= rupiah($k['harga_produk'] * $k['jumlah']); ?>
                                    <span class="text-xs text-gray-400 font-normal ml-1">(<?= rupiah($k['harga_produk']); ?> / unit)</span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <a href="index1.php" class="inline-flex items-center gap-2 text-red-900 font-bold text-sm hover:underline mt-2">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali Menjelajah Koleksi
                    </a>
                </div>

                <div class="w-full lg:w-2/5 bg-white rounded-[40px] p-8 border border-gray-100 shadow-xl lg:sticky lg:top-28">
                    <h3 class="serif text-2xl text-gray-900 mb-6 pb-4 border-b border-gray-100">Ringkasan Belanja</h3>

                    <div class="space-y-4 text-sm text-gray-500 mb-6">
                        <div class="flex justify-between">
                            <span>Total Harga (<?= count($keranjang); ?> Barang)</span>
                            <span class="font-semibold text-gray-800"><?= rupiah($subtotal); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Biaya Jasa Aplikasi</span>
                            <span class="font-semibold text-gray-800"><?= rupiah($biaya_layanan); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Estimasi Pajak Tradisi</span>
                            <span class="text-emerald-600 font-bold uppercase text-[10px] bg-emerald-50 px-2 py-0.5 rounded-md">Bebas Pajak</span>
                        </div>
                    </div>

                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Metode Pembayaran</p>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div onclick="pilihMetode('midtrans')" id="btn-midtrans"
                            class="metode-btn border-2 border-red-900 bg-red-900 text-white rounded-2xl p-3 text-center cursor-pointer transition-all">
                            <i data-lucide="credit-card" class="w-5 h-5 mx-auto mb-1"></i>
                            <div class="text-xs font-bold">Transfer / QRIS</div>
                            <div class="text-[10px] opacity-70">Bayar Sekarang</div>
                        </div>
                        <div onclick="pilihMetode('cod')" id="btn-cod"
                            class="metode-btn border-2 border-gray-200 text-gray-500 rounded-2xl p-3 text-center cursor-pointer transition-all">
                            <i data-lucide="banknote" class="w-5 h-5 mx-auto mb-1"></i>
                            <div class="text-xs font-bold">COD</div>
                            <div class="text-[10px] opacity-70">Bayar di Tempat</div>
                        </div>
                    </div>

                    <textarea id="catatan-order" placeholder="Catatan untuk penjual (opsional)..."
                            class="w-full text-sm p-3 bg-gray-50 border border-gray-100 rounded-2xl outline-none resize-none mb-4" rows="2"></textarea>

                    <div class="border-t border-dashed border-gray-200 pt-6 mb-6">
                        <div class="flex justify-between items-end">
                            <div>
                                <span class="block text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Total Pembayaran</span>
                                <span class="serif text-2xl md:text-3xl font-bold text-red-900"><?= rupiah($total_akhir); ?></span>
                            </div>
                            <span class="text-xs text-gray-400 italic">Termasuk PPN</span>
                        </div>
                    </div>

                    <button id="btn-lanjut-pembayaran" onclick="prosesCheckout()"
                            class="w-full red-gradient text-white py-4 px-6 rounded-2xl font-bold text-md transition-all shadow-xl shadow-red-200 flex items-center justify-center gap-2 group">
                        <span id="text-tombol">Lanjut ke Pembayaran →</span>
                    </button>

                    <div class="mt-6 flex items-center justify-center gap-2 text-gray-400 text-xs">
                        <i data-lucide="lock" class="w-3.5 h-3.5 text-emerald-600"></i>
                        <span>Pembayaran Terenkripsi & Aman</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        lucide.createIcons();

        let metodeTerpilih = 'midtrans';

        fn_pilihMetode = function(metode) {
            metodeTerpilih = metode;
            document.querySelectorAll('.metode-btn').forEach(el => {
                el.classList.remove('border-red-900', 'bg-red-900', 'text-white');
                el.classList.add('border-gray-200', 'text-gray-500');
            });
            const el = document.getElementById('btn-' + metode);
            el.classList.remove('border-gray-200', 'text-gray-500');
            el.classList.add('border-red-900', 'bg-red-900', 'text-white');
        }
        window.pilihMetode = fn_pimodal;

        async function prosesCheckout() {
            const btn     = document.getElementById('btn-lanjut-pembayaran');
            const btnText = document.getElementById('text-tombol');
            btn.disabled  = true;
            btnText.textContent = 'Memproses...';

            const catatan = document.getElementById('catatan-order').value;

            try {
                const res = await fetch('proses_checkout.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `metode_bayar=${metodeTerpilih}&catatan=${encodeURIComponent(catatan)}`
                });
                
                // Perbaikan pembacaan response teks agar kebal dari whitespace/karakter siluman PHP
                const rawText = await res.text();
                const data = JSON.parse(rawText.trim());

                if (data.status === 'no_address') {
                    alert('⚠️ ' + data.message);
                    window.location.href = 'profile.php';
                    return;
                }
                if (data.status === 'error') {
                    alert('❌ ' + data.message);
                    btn.disabled = false;
                    btnText.textContent = 'Lanjut ke Pembayaran →';
                    return;
                }
                if (data.metode === 'cod') {
                    window.location.href = 'pesanan.php?order_id=' + data.order_id + '&status=success_cod';
                    return;
                }
                if (data.metode === 'midtrans') {
                    window.snap.pay(data.snap_token, {
                        onSuccess: function(result) {
                            fetch('update_status_bayar.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                body: `order_id=${data.order_id}&status=paid`
                            }).then(() => {
                                window.location.href = 'pesanan.php?order_id=' + data.order_id + '&status=success';
                            });
                        },
                        onPending: function() {
                            window.location.href = 'pesanan.php?tab=dikemas';
                        },
                        onError: function() {
                            alert('Pembayaran gagal. Silakan coba lagi.');
                            btn.disabled = false;
                            btnText.textContent = 'Lanjut ke Pembayaran →';
                        },
                        onClose: function() {
                            btn.disabled = false;
                            btnText.textContent = 'Lanjut ke Pembayaran →';
                        }
                    });
                }
            } catch(err) {
                alert('Terjadi kesalahan koneksi atau format response hancur.');
                btn.disabled = false;
                btnText.textContent = 'Lanjut ke Pembayaran →';
            }
        }
    </script>
</body>
</html>