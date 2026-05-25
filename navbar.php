<?php
// navbar.php — include di semua halaman
// Pastikan session & koneksi sudah di-start sebelum include file ini

$_nav_keranjang = isset($_SESSION['keranjang']) ? $_SESSION['keranjang'] : [];
$_nav_cart_count = 0;
foreach ($_nav_keranjang as $item) {
    if (is_array($item) && isset($item['jumlah'])) {
        $_nav_cart_count += $item['jumlah'];
    }
}
$_nav_is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$_nav_user_email   = $_SESSION['user_email'] ?? '';
$_nav_user_role    = $_SESSION['user_role'] ?? '';
$_nav_current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="hidden md:flex sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 px-12 py-4 justify-between items-center">
    <div class="flex items-center gap-8">
        <a href="index1.php" class="serif text-2xl font-bold text-red-900 tracking-tighter" style="font-family:'Playfair Display',serif;">Roemah Raga</a>
        <div class="flex gap-6 text-sm font-semibold text-gray-500 uppercase tracking-widest items-center">
            <a href="index1.php"      class="nav-link <?= $_nav_current_page === 'index1.php'      ? 'text-red-900' : '' ?>">Home</a>
            <a href="keranjang.php"   class="nav-link <?= $_nav_current_page === 'keranjang.php'   ? 'text-red-900' : '' ?>">Keranjang</a>
            <a href="collections.php" class="nav-link flex items-center gap-1.5 group/col <?= $_nav_current_page === 'collections.php' ? 'text-red-900' : 'text-gray-500' ?> hover:text-red-600 transition-colors">
                Collections <i data-lucide="heart" class="w-4 h-4 text-red-600 fill-red-100 group-hover/col:fill-red-600 transition-all duration-300"></i>
            </a>
            <a href="about.php"       class="nav-link <?= $_nav_current_page === 'about.php'       ? 'text-red-900' : '' ?>">About</a>
        </div>
    </div>
    <div class="flex items-center gap-6">

        <!-- Ikon Keranjang -->
        <a href="keranjang.php" class="relative cursor-pointer block hover:scale-105 transition-transform">
            <i data-lucide="shopping-bag" class="w-6 h-6 text-gray-600 hover:text-red-900 transition-colors"></i>
            <span class="absolute -top-1 -right-1 bg-red-700 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-bold">
                <?= $_nav_cart_count ?>
            </span>
        </a>

        <!-- Status Login -->
        <?php if ($_nav_is_logged_in): ?>
            <div class="flex items-center gap-3">
                <a href="profile.php" class="flex items-center gap-2 bg-red-50 border border-red-100 pr-4 pl-1.5 py-1.5 rounded-full shadow-sm hover:bg-red-100 hover:border-red-200 transition-all duration-300 cursor-pointer">
                    <div class="w-7 h-7 rounded-full bg-red-900 text-white font-bold text-xs flex items-center justify-center uppercase">
                        <?= substr($_nav_user_email, 0, 1) ?>
                    </div>
                    <span class="text-xs font-bold text-red-900 max-w-[100px] truncate">
                        <?= $_nav_user_role === 'guest' ? 'Mode Guest' : htmlspecialchars($_nav_user_email) ?>
                    </span>
                </a>
                <a href="index1.php?logout=true" class="text-xs font-bold text-gray-400 hover:text-red-600 transition underline">Logout</a>
            </div>
        <?php else: ?>
            <a href="index1.php" class="bg-red-900 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-red-800 transition shadow-lg shadow-red-200">Masuk</a>
        <?php endif; ?>
    </div>
</nav>