<?php
// 1. Nyalakan session jika navbar membutuhkan hitungan jumlah item keranjang
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Koreksi jalur jika membutuhkan koneksi database (opsional untuk halaman statis)
// require_once 'admin-panel/config/database.php';

$keranjang = isset($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | Roemah Raga Nusantara</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght=0,700;1,700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfc; }
        .serif { font-family: 'Playfair Display', serif; }
        .red-gradient { background: linear-gradient(135deg, #991b1b 0%, #450a0a 100%); }
        .nav-link:after { content: ''; display: block; width: 0; height: 2px; background: #991b1b; transition: width .3s; }
        .nav-link:hover:after { width: 100%; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">

    <?php require_once 'navbar.php'; ?>

    <main class="container mx-auto px-4 md:px-12 py-12 max-w-4xl flex-grow">
        <div class="flex items-center gap-2 text-xs text-gray-400 uppercase tracking-widest mb-8">
            <a href="index1.php" class="hover:text-red-900">Home</a>
            <i data-lucide="chevron-right" class="w-3 h-3"></i>
            <span class="text-red-900 font-bold">About Us</span>
        </div>

        <div class="bg-white rounded-[40px] p-8 md:p-16 border border-gray-100 shadow-xl relative overflow-hidden">
            
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-red-50 rounded-full blur-2xl opacity-60"></div>
            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-amber-50 rounded-full blur-2xl opacity-60"></div>

            <div class="text-center mb-12 relative z-10">
                <div class="w-16 h-16 red-gradient text-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg shadow-red-100">
                    <i data-lucide="sparkles" class="w-8 h-8"></i>
                </div>
                <h1 class="serif text-4xl md:text-5xl text-red-900 tracking-tight mb-2">Roemah Raga Nusantara</h1>
                <p class="text-xs md:text-sm text-gray-400 uppercase tracking-widest font-semibold">Nadi Budaya dalam Balutan Busana Modern</p>
            </div>

            <div class="space-y-6 text-gray-600 leading-relaxed text-justify relative z-10 text-md">
    <p>
        <strong class="text-red-900 font-semibold">Roemah Raga</strong> hadir sebagai jembatan kontemporer yang mempertemukan keagungan tradisi warisan Nusantara dengan representasi gaya hidup modern yang dinamis. Kami percaya bahwa pakaian adat bukanlah sekadar peninggalan masa lalu yang kaku, melainkan sebuah mahakarya hidup yang terus relevan melintasi zaman.
    </p>
    <p>
        Kami mengkurasi dan menghadirkan ragam koleksi pakaian premium—mulai dari beskap eksklusif, kebaya, hingga busana adat pilihan—yang dirancang dengan detail presisi tinggi, material berkualitas, serta kenyamanan optimal. Setiap produk diciptakan khusus untuk merayakan identitas, kehormatan, dan kebanggaan budaya dalam setiap momentum berharga di hidup Anda.
    </p>
    <p>
        Melalui platform digital resmi ini, kami berkomitmen untuk memberikan pengalaman eksplorasi busana tradisional yang <span class="italic text-gray-800">seamless</span>, aman, dan tepercaya. Setiap helai kain yang Anda pilih di <span class="serif text-red-900 font-bold font-medium">Roemah Raga</span> bukan sekadar transaksi sandang biasa, melainkan sebuah bentuk kontribusi nyata dalam melestarikan karya budaya leluhur agar tetap kokoh dan abadi.
    </p>

    <div class="mt-10 pt-8 border-t border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-6 text-left">
        <div class="flex items-start gap-3.5 group">
            <div class="p-3 bg-red-50 text-red-900 rounded-2xl group-hover:scale-105 transition-transform">
                <i data-lucide="phone" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Hubungi Kami</h4>
                <a href="https://wa.me/6285326695545" target="_blank" class="text-gray-800 font-bold hover:text-red-900 transition-colors">
                    085326695545
                </a>
                <p class="text-xs text-gray-400 mt-0.5">Layanan Pelanggan & Kemitraan</p>
            </div>
        </div>

        <div class="flex items-start gap-3.5 group">
            <div class="p-3 bg-red-50 text-red-900 rounded-2xl group-hover:scale-105 transition-transform">
                <i data-lucide="map-pin" class="w-5 h-5"></i>
            </div>
            <div>
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">Butik & Workshop</h4>
                <p class="text-gray-800 font-semibold text-sm leading-semibold">
                    Jl. Situnggul No. 17, RT 21/RW 05
                </p>
                <p class="text-xs text-gray-400 mt-0.5 leading-relaxed">
                    Desa Pesarean, Kec. Adiwerna, Kab. Tegal<br>
                    Jawa Tengah, Indonesia — 52194
                </p>
            </div>
        </div>
    </div>
</div>

            <div class="border-t border-dashed border-gray-200 my-10 relative z-10"></div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center relative z-10">
                <div class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100">
                    <i data-lucide="award" class="w-6 h-6 text-red-900 mx-auto mb-2"></i>
                    <h4 class="font-bold text-gray-800 text-sm mb-1">Kualitas Premium</h4>
                    <p class="text-xs text-gray-400">Bahan pilihan berstandar tinggi</p>
                </div>
                <div class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100">
                    <i data-lucide="shield-check" class="w-6 h-6 text-red-900"></i>
                    <h4 class="font-bold text-gray-800 text-sm mb-1">Transaksi Aman</h4>
                    <p class="text-xs text-gray-400">Keamanan enkripsi data terjamin</p>
                </div>
                <div class="p-4 bg-gray-50/50 rounded-2xl border border-gray-100">
                    <i data-lucide="truck" class="w-6 h-6 text-red-900 mx-auto mb-2"></i>
                    <h4 class="font-bold text-gray-800 text-sm mb-1">Pengiriman Cepat</h4>
                    <p class="text-xs text-gray-400">Layanan logistik ke seluruh Nusantara</p>
                </div>
            </div>

            <div class="flex justify-center mt-12 relative z-10">
                <a href="index1.php" class="red-gradient text-white px-10 py-4 rounded-2xl font-bold hover:scale-105 active:scale-95 transition-all shadow-xl shadow-red-100 flex items-center gap-2 group">
                    <span>Kembali Jelajahi Koleksi</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>

        </div>
    </main>

    <footer class="border-t border-gray-100 bg-white py-6 text-center text-xs text-gray-400 mt-12">
        <p>&copy; <?= date('Y'); ?> Roemah Raga Nusantara. Hak Cipta Dilindungi.</p>
    </footer>

    <script>
        // Inisialisasi ikon Lucide agar muncul di halaman
        lucide.createIcons();
    </script>
</body>
</html>