<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'admin-panel/config/database.php';
$koneksi = isset($db) ? $db : $conn;

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: index1.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$order_id = $_GET['order_id'] ?? '';
$status   = $_GET['status'] ?? '';

// 1. Ambil data gabungan transaksi dan item
$query = "
    SELECT t.*, 
           ti.nama_produk, ti.foto_produk, ti.ukuran, ti.jumlah
    FROM transaksi t
    LEFT JOIN transaksi_item ti ON t.id = ti.transaksi_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_raw = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$semua_pesanan = [];
$items_map = [];

foreach ($result_raw as $row) {
    $t_id = intval($row['id']);
    
    if (!isset($semua_pesanan[$t_id])) {
        $semua_pesanan[$t_id] = [
            'id' => $row['id'],
            'order_id' => $row['order_id'],
            'user_id' => $row['user_id'],
            'total_bayar' => $row['total_bayar'],
            'status_pesanan' => $row['status_pesanan'],
            'created_at' => $row['created_at'],
            'jumlah_item' => 0 
        ];
    }
    
    if (!empty($row['nama_produk'])) {
        $items_map[$t_id][] = [
            'nama_produk' => $row['nama_produk'],
            'foto_produk' => $row['foto_produk'],
            'ukuran' => $row['ukuran'],
            'jumlah' => $row['jumlah']
        ];
        $semua_pesanan[$t_id]['jumlah_item'] += 1;
    }
}

$semua_pesanan = array_values($semua_pesanan);

// 2. Filter data berdasarkan status pesanan
$dikemas = array_filter($semua_pesanan, function($p) {
    if (!isset($p['status_pesanan'])) return false;
    $status_clean = strtolower(trim($p['status_pesanan']));
    return $status_clean === 'dikemas' || $status_clean === 'pending' || $status_clean === 'proses';
});

$dikirim = array_filter($semua_pesanan, function($p) {
    if (!isset($p['status_pesanan'])) return false;
    $status_clean = strtolower(trim($p['status_pesanan']));
    return $status_clean === 'dikirim' || $status_clean === 'dalam pengiriman';
});

$selesai = array_filter($semua_pesanan, function($p) {
    if (!isset($p['status_pesanan'])) return false;
    $status_clean = strtolower(trim($p['status_pesanan']));
    return $status_clean === 'selesai' || $status_clean === 'success';
});

$tab = $_GET['tab'] ?? '';
$initial_tab = 0;
if ($tab === 'dikirim') $initial_tab = 1;
elseif ($tab === 'selesai') $initial_tab = 2;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya | Roemah Raga Nusantara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght=700&family=Plus+Jakarta+Sans:wght=300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8f8f8; margin: 0; }
        .serif { font-family: 'Playfair Display', serif; }
        .tab-active { color: #991b1b; border-bottom: 3px solid #991b1b; }
        
        /* FIX LAYOUT SLIDER & WIDTH */
        .slider-container { width: 100%; overflow: hidden; position: relative; }
        .slider { display: flex; width: 300%; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .page { width: 33.33333%; flex-shrink: 0; padding: 20px; min-height: 60vh; box-sizing: border-box; }
    </style>
</head>
<body class="bg-gray-50">

<div class="bg-gray-800 text-white text-xs p-1 text-center opacity-50">Log ID User: <?= $user_id ?></div>

<div class="flex items-center px-5 py-4 bg-white border-b border-gray-100 sticky top-0 z-50">
    <a href="profile.php" class="text-red-900 mr-4 text-lg"><i class="fa-solid fa-arrow-left"></i></a>
    <span class="serif text-lg font-bold text-gray-800">Pesanan Saya</span>
</div>

<?php if (strpos($status, 'success') !== false || $status === 'sukses'): ?>
<div class="mx-5 mt-4 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-sm text-emerald-800">
    <i class="fa-solid fa-circle-check mr-2"></i>
    <b>Pesanan Berhasil Diproses!</b> Sistem berhasil memverifikasi faktur belanja Anda.
    <?php if ($order_id): ?><span class="text-xs opacity-70 block mt-1">Order ID: <?= htmlspecialchars($order_id) ?></span><?php endif; ?>
</div>
<?php endif; ?>

<div class="flex bg-white border-b border-gray-100 sticky top-[57px] z-40">
    <?php foreach (['Dikemas' => count($dikemas), 'Dikirim' => count($dikirim), 'Selesai' => count($selesai)] as $label => $count): ?>
    <div class="flex-1 text-center py-4 cursor-pointer text-xs font-bold text-gray-400 uppercase tracking-widest transition tab-item"
         onclick="goTab(<?= ['Dikemas'=>0,'Dikirim'=>1,'Selesai'=>2][$label] ?>)">
        <?= $label ?>
        <?php if ($count > 0): ?>
            <span class="ml-1 bg-red-900 text-white text-[9px] w-4 h-4 rounded-full inline-flex items-center justify-center"><?= $count ?></span>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="slider-container">
    <div class="slider" id="slider">

        <div class="page">
            <?php if (empty($dikemas)): ?>
                <div class="text-center py-20">
                    <i class="fa-solid fa-box-open text-5xl mb-4 block text-gray-300"></i>
                    <p class="text-sm font-semibold text-gray-400">Belum ada pesanan dikemas</p>
                </div>
            <?php else: foreach ($dikemas as $p):
                $daftar_item = $items_map[intval($p['id'])] ?? [];
                $item = $daftar_item[0] ?? null; 
                $foto = (!empty($item['foto_produk'])) ? 'admin-panel/uploads/' . basename($item['foto_produk']) : 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200';
                $extra = $p['jumlah_item'] > 1 ? '+'.($p['jumlah_item']-1).' produk lainnya' : '';
            ?>
            <div class="bg-white rounded-[20px] p-5 mb-4 shadow-sm border border-gray-100 max-w-xl mx-auto text-left">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Roemah Raga Official</span>
                    <span class="text-orange-600 font-bold text-[11px]">Sedang Dikemas</span>
                </div>
                <div class="flex gap-4 items-center">
                    <img src="<?= htmlspecialchars($foto) ?>" class="w-16 h-16 rounded-xl object-cover border border-gray-100" alt="produk" onerror="this.src='https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200'">
                    <div class="flex-1">
                        <div class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($item['nama_produk'] ?? 'Produk Raga') ?></div>
                        <div class="text-xs text-gray-400 mt-0.5">Ukuran: <?= htmlspecialchars($item['ukuran'] ?? '-') ?> · <?= intval($item['jumlah'] ?? 1) ?> Unit</div>
                        <?php if ($extra): ?><div class="text-xs text-red-900 font-medium mt-0.5"><?= $extra ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="border-t border-gray-100 mt-4 pt-3 flex justify-between items-center">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Total Belanja</div>
                        <div class="font-bold text-red-900">Rp <?= number_format($p['total_bayar'], 0, ',', '.') ?></div>
                    </div>
                    <div class="flex gap-2">
                        <a href="index1.php" class="text-xs border border-gray-200 text-gray-500 px-3 py-2 rounded-xl font-bold hover:bg-gray-50 transition">Beli Lagi</a>
                        <a href="detail_pesanan.php?order_id=<?= htmlspecialchars($p['order_id']) ?>" class="text-xs bg-red-900 text-white px-3 py-2 rounded-xl font-bold hover:bg-red-800 transition">Rincian</a>
                    </div>
                </div>
                <div class="text-[10px] text-gray-300 mt-2">
                    ID Transaksi: <?= htmlspecialchars($p['order_id']) ?> · <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="page">
            <?php if (empty($dikirim)): ?>
                <div class="text-center py-20">
                    <i class="fa-solid fa-truck-fast text-5xl mb-4 block text-gray-300"></i>
                    <p class="text-sm font-semibold text-gray-400">Belum ada pesanan dalam pengiriman</p>
                </div>
            <?php else: foreach ($dikirim as $p):
                $daftar_item = $items_map[intval($p['id'])] ?? [];
                $item = $daftar_item[0] ?? null;
                $foto = (!empty($item['foto_produk'])) ? 'admin-panel/uploads/' . basename($item['foto_produk']) : 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200';
                $extra = $p['jumlah_item'] > 1 ? '+'.($p['jumlah_item']-1).' produk lainnya' : '';
            ?>
            <div class="bg-white rounded-[20px] p-5 mb-4 shadow-sm border border-gray-100 max-w-xl mx-auto text-left">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Roemah Raga Official</span>
                    <span class="text-blue-600 font-bold text-[11px]">Dalam Pengiriman</span>
                </div>
                <div class="flex gap-4 items-center">
                    <img src="<?= htmlspecialchars($foto) ?>" class="w-16 h-16 rounded-xl object-cover border border-gray-100" alt="produk" onerror="this.src='https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200'">
                    <div class="flex-1">
                        <div class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($item['nama_produk'] ?? 'Produk Raga') ?></div>
                        <div class="text-xs text-gray-400 mt-0.5">Ukuran: <?= htmlspecialchars($item['ukuran'] ?? '-') ?> · <?= intval($item['jumlah'] ?? 1) ?> Unit</div>
                        <?php if ($extra): ?><div class="text-xs text-red-900 font-medium mt-0.5"><?= $extra ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="border-t border-gray-100 mt-4 pt-3 flex justify-between items-center">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Total Belanja</div>
                        <div class="font-bold text-red-900">Rp <?= number_format($p['total_bayar'], 0, ',', '.') ?></div>
                    </div>
                    <div class="flex gap-2">
                        <a href="index1.php" class="text-xs border border-gray-200 text-gray-500 px-3 py-2 rounded-xl font-bold hover:bg-gray-50 transition">Beli Lagi</a>
                        <a href="detail_pesanan.php?order_id=<?= htmlspecialchars($p['order_id']) ?>" class="text-xs bg-red-900 text-white px-3 py-2 rounded-xl font-bold hover:bg-red-800 transition">Rincian</a>
                    </div>
                </div>
                <div class="text-[10px] text-gray-300 mt-2">
                    ID Transaksi: <?= htmlspecialchars($p['order_id']) ?> · <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="page">
            <?php if (empty($selesai)): ?>
                <div class="text-center py-20">
                    <i class="fa-solid fa-circle-check text-5xl mb-4 block text-gray-300"></i>
                    <p class="text-sm font-semibold text-gray-400">Belum ada pesanan selesai</p>
                </div>
            <?php else: foreach ($selesai as $p):
                $daftar_item = $items_map[intval($p['id'])] ?? [];
                $item = $daftar_item[0] ?? null;
                $foto = (!empty($item['foto_produk'])) ? 'admin-panel/uploads/' . basename($item['foto_produk']) : 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200';
                $extra = $p['jumlah_item'] > 1 ? '+'.($p['jumlah_item']-1).' produk lainnya' : '';
            ?>
            <div class="bg-white rounded-[20px] p-5 mb-4 shadow-sm border border-gray-100 max-w-xl mx-auto text-left">
                <div class="flex justify-between items-center mb-3">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Roemah Raga Official</span>
                    <span class="text-emerald-600 font-bold text-[11px]">Selesai</span>
                </div>
                <div class="flex gap-4 items-center">
                    <img src="<?= htmlspecialchars($foto) ?>" class="w-16 h-16 rounded-xl object-cover border border-gray-100" alt="produk" onerror="this.src='https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=200'">
                    <div class="flex-1">
                        <div class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($item['nama_produk'] ?? 'Produk Raga') ?></div>
                        <div class="text-xs text-gray-400 mt-0.5">Ukuran: <?= htmlspecialchars($item['ukuran'] ?? '-') ?> · <?= intval($item['jumlah'] ?? 1) ?> Unit</div>
                        <?php if ($extra): ?><div class="text-xs text-red-900 font-medium mt-0.5"><?= $extra ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="border-t border-gray-100 mt-4 pt-3 flex justify-between items-center">
                    <div>
                        <div class="text-xs text-gray-400 mb-1">Total Belanja</div>
                        <div class="font-bold text-red-900">Rp <?= number_format($p['total_bayar'], 0, ',', '.') ?></div>
                    </div>
                    <div class="flex gap-2">
                        <a href="index1.php" class="text-xs border border-gray-200 text-gray-500 px-3 py-2 rounded-xl font-bold hover:bg-gray-50 transition">Beli Lagi</a>
                        <a href="detail_pesanan.php?order_id=<?= htmlspecialchars($p['order_id']) ?>" class="text-xs bg-red-900 text-white px-3 py-2 rounded-xl font-bold hover:bg-red-800 transition">Rincian</a>
                    </div>
                </div>
                <div class="text-[10px] text-gray-300 mt-2">
                    ID Transaksi: <?= htmlspecialchars($p['order_id']) ?> · <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<script>
let current = <?= $initial_tab ?>;
const tabs = document.querySelectorAll('.tab-item');

function goTab(index) {
    current = index;
    // Menggunakan pergeseran koordinat persentase yang disesuaikan dengan 3 kolom slider
    document.getElementById('slider').style.transform = 'translateX(-' + (index * 33.33333) + '%)';
    tabs.forEach((t, i) => {
        t.classList.toggle('tab-active', i === index);
        t.classList.toggle('text-gray-400', i !== index);
    });
}

// Fitur Geser Touchscreen Mobile
let startX = 0;
const slider = document.getElementById('slider');
slider.addEventListener('touchstart', e => startX = e.changedTouches[0].screenX);
slider.addEventListener('touchend', e => {
    const diff = e.changedTouches[0].screenX - startX;
    if (diff < -50 && current < 2) goTab(current + 1);
    if (diff > 50 && current > 0) goTab(current - 1);
});

// Jalankan index tab default saat halaman dimuat
goTab(current);
</script>

</body>
</html>