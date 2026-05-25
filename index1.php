<?php
session_start();
// Mengintegrasikan koneksi database
require_once 'admin-panel/config/database.php';

// Menyelaraskan variabel koneksi agar tidak terjadi error undifined variable
$koneksi = isset($conn) ? $conn : (isset($db) ? $db : $koneksi);

// Inisialisasi struktur keranjang belanja jika belum ada
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

// =========================================================
// FUNGSI HELPER: MUAT KERANJANG DARI DB KE SESSION
// (Disamakan dengan keranjang.php agar sinkron)
// =========================================================
function load_cart_from_db($koneksi, $user_id) {
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

// Ambil status login dasar untuk pengecekan awal
$is_logged_in = isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true;
$user_id      = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$user_role    = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// SINKRONISASI OTOMATIS: Jika user sudah login, muat data keranjang dari database ke session halaman utama
if ($is_logged_in && $user_id > 0 && $user_role !== 'guest') {
    load_cart_from_db($koneksi, $user_id);
}

// --- LOGIKA MENAMBAH KE KERANJANG VIA AJAX (BACKEND) ---
if (isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    // Pastikan user tidak dalam mode guest atau belum login
    if (!isset($_SESSION['is_logged_in']) || $_SESSION['user_role'] === 'guest') {
        echo json_encode(['status' => 'error', 'message' => 'Silahkan login terlebih dahulu.']);
        exit();
    }
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $ukuran_default = 'M'; // Default ukuran karena tombol di index1.php langsung menambah ke keranjang
    $cart_key = $product_id . '_' . $ukuran_default;
    $user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    
    if ($product_id > 0 && $user_id > 0) {
        // PERBAIKAN: Menambahkan kolom 'gambar' ke dalam query SELECT
        $check_stmt = $koneksi->prepare("SELECT id, nama_produk, harga, gambar, stok FROM produk WHERE id = ?");
        if ($check_stmt) {
            $check_stmt->bind_param("i", $product_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();
            
            if ($res->num_rows > 0) {
                $prod_data = $res->fetch_assoc();

                $stok_tersedia = intval($prod_data['stok']);
                $jumlah_di_keranjang = isset($_SESSION['keranjang'][$cart_key]) 
                    ? intval($_SESSION['keranjang'][$cart_key]['jumlah']) 
                    : 0;

                if ($jumlah_di_keranjang >= $stok_tersedia) {
                    echo json_encode([
                        'status'  => 'error', 
                        'message' => 'Stok produk ini hanya ' . $stok_tersedia . ' unit dan sudah habis di keranjang Anda.'
                    ]);
                    exit();
                }

                $nama   = $prod_data['nama_produk'];
                $harga  = floatval($prod_data['harga']);
                
                // MENGAMBIL NAMA FILE GAMBAR: Menggunakan basename() agar folder path tidak dobel
                $gambar_raw = isset($prod_data['gambar']) ? $prod_data['gambar'] : '';
                $gambar = !empty($gambar_raw) ? basename($gambar_raw) : 'default.jpg';

                // Update data di dalam Session
                if (isset($_SESSION['keranjang'][$cart_key]) && is_array($_SESSION['keranjang'][$cart_key])) {
                    $_SESSION['keranjang'][$cart_key]['jumlah'] += 1;
                } else {
                    $_SESSION['keranjang'][$cart_key] = [
                        'id_produk'    => $product_id,
                        'nama_produk'  => $nama,
                        'harga_produk' => $harga,
                        'foto_produk'  => $gambar,
                        'ukuran'       => $ukuran_default,
                        'jumlah'       => 1
                    ];
                }

                // SIMPAN/UPDATE KE DATABASE (keranjang_db)
                $jumlah_baru = $_SESSION['keranjang'][$cart_key]['jumlah'];
                $stmt_db = $koneksi->prepare("
                    INSERT INTO keranjang_db 
                        (user_id, cart_key, id_produk, nama_produk, harga_produk, foto_produk, ukuran, jumlah)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE jumlah = ?
                ");
                if ($stmt_db) {
                    $stmt_db->bind_param("isssdssii",
                        $user_id,
                        $cart_key,
                        $product_id,
                        $nama,
                        $harga,
                        $gambar, // Data gambar sekarang sudah aman terisi nama file aslinya
                        $ukuran_default,
                        $jumlah_baru,
                        $jumlah_baru
                    );
                    $stmt_db->execute();
                    $stmt_db->close();
                }

                // Hitung total item akumulatif untuk respon badge cart di navbar
                $total_items = 0;
                foreach ($_SESSION['keranjang'] as $item) {
                    if (is_array($item)) {
                        $total_items += $item['jumlah'];
                    }
                }

                echo json_encode(['status' => 'success', 'cart_count' => $total_items]);
                exit();
            }
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan produk.']);
    exit();
}

// --- LOGIKA LOGIN MANUAL (FORM) & REDIREKSI ADMIN ---
if (isset($_POST['login_submit'])) {
    $email_or_username = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($email_or_username) && !empty($password)) {
        if ($email_or_username === 'admin' && $password === 'admin16') {
            $_SESSION['is_logged_in'] = true;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_id'] = 1;
            $_SESSION['user_email'] = 'admin@roemahraga.com';
            header("Location: admin-panel/index.php");
            exit();
        } else {
            $_SESSION['is_logged_in'] = true;
            $_SESSION['user_role'] = 'client';
            $_SESSION['user_id'] = 2; // Catatan: Id ini idealnya ditarik dinamis dari tabel 'pengguna' sesuai user yang login
            $_SESSION['user_email'] = $email_or_username;
            
            // Muat keranjang langsung setelah login berhasil
            load_cart_from_db($koneksi, $_SESSION['user_id']);
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// --- LOGIKA REGISTER/SIGN UP MANUAL ---
if (isset($_POST['register_submit'])) {
    $email = trim($_POST['reg_email']);
    $password = $_POST['reg_password'];
    
    if (!empty($email) && !empty($password)) {
        $_SESSION['is_logged_in'] = true;
        $_SESSION['user_role'] = 'client';
        $_SESSION['user_id'] = 3;
        $_SESSION['user_email'] = $email;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// --- LOGIKA LOGIN VIA GOOGLE ---
if (isset($_POST['google_login_active'])) {
    header('Content-Type: application/json');
    
    $google_email = trim($_POST['google_email']);
    
    if (empty($google_email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email Google tidak valid.']);
        exit();
    }

    // Cek apakah email sudah ada di database
    $cek = $koneksi->prepare("SELECT id, nama FROM pengguna WHERE email = ?");
    $cek->bind_param("s", $google_email);
    $cek->execute();
    $res = $cek->get_result();

    if ($res->num_rows > 0) {
        // Email sudah ada → langsung login
        $row = $res->fetch_assoc();
        $user_id_google = $row['id'];
        $user_nama      = $row['nama'];
    } else {
        // Email belum ada → auto register
        $nama_default = explode('@', $google_email)[0];
        $password_dummy = md5(uniqid()); // password acak, tidak bisa dipakai login manual
        
        $ins = $koneksi->prepare("INSERT INTO pengguna (email, password, nama) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $google_email, $password_dummy, $nama_default);
        
        if ($ins->execute()) {
            $user_id_google = $koneksi->insert_id;
            $user_nama      = $nama_default;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan akun Google.']);
            exit();
        }
    }

    // Set session dengan ID asli dari database
    $_SESSION['is_logged_in'] = true;
    $_SESSION['user_role']    = 'client';
    $_SESSION['user_id']      = $user_id_google;
    $_SESSION['user_email']   = $google_email;
    $_SESSION['user_nama']    = $user_nama;

    load_cart_from_db($koneksi, $user_id_google);

    echo json_encode(['status' => 'success']);
    exit();
}

// --- LOGIKA MASUK SEBAGAI GUEST (TAMU) ---
if (isset($_GET['action']) && $_GET['action'] === 'guest_mode') {
    $_SESSION['is_logged_in'] = true;
    $_SESSION['user_role'] = 'guest';
    $_SESSION['user_email'] = 'Guest Account';
    $_SESSION['keranjang'] = []; // Bersihkan session keranjang jika masuk mode guest
    header("Location: " . $_SERVER['PHP_SELF'] . "#koleksi-utama");
    exit();
}

// --- LOGIKA LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Sinkronisasi Variabel Global untuk keperluan tampilan HTML di bawah
$kategori_terpilih = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
$search_keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roemah Raga Nusantara | Premium Heritage</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #fcfcfc; scroll-behavior: smooth; }
        .serif { font-family: 'Playfair Display', serif; }
        .red-gradient { background: linear-gradient(135deg, #991b1b 0%, #450a0a 100%); }
        .glass { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1); }
        .nav-link:after { content: ''; display: block; width: 0; height: 2px; background: #991b1b; transition: width .3s; }
        .nav-link:hover:after { width: 100%; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">

    <div id="loginModal" class="fixed inset-0 z-[100] hidden bg-black/60 backdrop-blur-sm items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-[40px] overflow-hidden shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
            <button onclick="toggleModal()" class="absolute top-6 right-6 text-gray-400 hover:text-red-900 transition z-10">
                <i data-lucide="x"></i>
            </button>
            
            <div id="loginBox" class="p-10">
                <div class="text-center mb-6">
                    <h2 class="serif text-3xl font-bold text-red-900 mb-2">Selamat Datang</h2>
                    <p class="text-gray-400 text-sm">Masuk ke akun Anda atau gunakan akun Google.</p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1 ml-2">Email / Username</label>
                        <input type="text" name="email" required placeholder="user@example.com atau admin" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-red-900 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1 ml-2">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-red-900 transition">
                    </div>
                    <button type="submit" name="login_submit" class="w-full py-3.5 red-gradient text-white rounded-2xl font-bold shadow-lg shadow-red-200 hover:shadow-red-300 transition-all active:scale-95">
                        Masuk Sekarang
                    </button>
                </form>

                <div class="relative my-6 text-center">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                    <span class="relative bg-white px-4 text-xs text-gray-400 uppercase font-semibold">Atau alternatif</span>
                </div>

                <div class="flex justify-center mb-4 min-h-[44px]">
                    <div id="g_id_onload"
                         data-client_id="201923757943-sh0rvd30g4vtam7qtdca7vg3ei3joevu.apps.googleusercontent.com"
                         data-context="signin"
                         data-ux_mode="popup"
                         data-callback="handleCredentialResponse"
                         data-auto_select="false">
                    </div>
                    <div class="g_id_signin w-full flex justify-center" 
                         data-type="standard" 
                         data-shape="pill" 
                         data-theme="outline" 
                         data-text="signin_with" 
                         data-size="large" 
                         data-width="360"
                         data-logo_alignment="left">
                    </div>
                </div>

                <a href="?action=guest_mode" class="block text-center w-full py-3.5 border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-2xl text-xs font-bold transition mb-4">
                    Masuk Sebagai Guest (Lihat Saja)
                </a>

                <p class="text-center text-xs text-gray-400">Belum punya akun? <button onclick="switchAuthMode('register')" class="text-red-900 font-bold underline outline-none">Daftar</button></p>
            </div>

            <div id="registerBox" class="p-10 hidden">
                <div class="text-center mb-6">
                    <h2 class="serif text-3xl font-bold text-red-900 mb-2">Buat Akun Baru</h2>
                    <p class="text-gray-400 text-sm">Lengkapi form di bawah untuk bergabung bersama kami.</p>
                </div>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1 ml-2">Alamat Email</label>
                        <input type="email" name="reg_email" required placeholder="nama@email.com" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-red-900 transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1 ml-2">Password Baru</label>
                        <input type="password" name="reg_password" required placeholder="••••••••" class="w-full px-5 py-3.5 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-red-900 transition">
                    </div>
                    <button type="submit" name="register_submit" class="w-full py-3.5 bg-gray-900 text-white rounded-2xl font-bold shadow-lg transition-all active:scale-95">
                        Mendaftar
                    </button>
                </form>

                <p class="mt-6 text-center text-xs text-gray-400">Sudah memiliki akun? <button onclick="switchAuthMode('login')" class="text-red-900 font-bold underline outline-none">Login</button></p>
            </div>
        </div>
    </div>

    <?php
    $kategori_query = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori ASC");

    // Pembuatan Query Dinamis
    // PERBAIKAN: Kolom 'deskripsi' tidak ada di tabel produk, hanya cari berdasarkan nama_produk
    if ($kategori_terpilih > 0) {
        if (!empty($search_keyword)) {
            $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_kat = ? AND nama_produk LIKE ? ORDER BY id DESC");
            if ($stmt) {
                $search_param = "%" . $search_keyword . "%";
                $stmt->bind_param("is", $kategori_terpilih, $search_param);
                $stmt->execute();
                $produk_query = $stmt->get_result();
            } else {
                die("Kesalahan Query Database: " . $koneksi->error);
            }
        } else {
            $stmt = $koneksi->prepare("SELECT * FROM produk WHERE id_kat = ? ORDER BY id DESC");
            if ($stmt) {
                $stmt->bind_param("i", $kategori_terpilih);
                $stmt->execute();
                $produk_query = $stmt->get_result();
            } else {
                die("Kesalahan Query Database: " . $koneksi->error);
            }
        }
    } else {
        if (!empty($search_keyword)) {
            $stmt = $koneksi->prepare("SELECT * FROM produk WHERE nama_produk LIKE ? ORDER BY id DESC");
            if ($stmt) {
                $search_param = "%" . $search_keyword . "%";
                $stmt->bind_param("s", $search_param);
                $stmt->execute();
                $produk_query = $stmt->get_result();
            } else {
                die("Kesalahan Query Database: " . $koneksi->error);
            }
        } else {
            $produk_query = $koneksi->query("SELECT * FROM produk ORDER BY id DESC");
        }
    }
    ?>

    <nav class="hidden md:flex sticky top-0 z-50 bg-white/80 backdrop-blur-md border-b border-gray-100 px-12 py-4 justify-between items-center">
        <div class="flex items-center gap-8">
            <h1 class="serif text-2xl font-bold text-red-900 tracking-tighter">Roemah Raga</h1>
            <div class="flex gap-6 text-sm font-semibold text-gray-500 uppercase tracking-widest items-center">
                <a href="index1.php" class="nav-link text-red-900">Home</a>
                <a href="keranjang.php" class="nav-link">Keranjang</a>
                
                <a href="collections.php" class="nav-link flex items-center gap-1.5 group/col text-gray-500 hover:text-red-600 transition-colors">
                    Collections <i data-lucide="heart" class="w-4 h-4 text-red-600 fill-red-100 group-hover/col:fill-red-600 transition-all duration-300"></i>
                </a>
                
                <a href="about.php" class="nav-link">About</a>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <form action="index1.php#koleksi-utama" method="GET" class="relative group">
                <?php if ($kategori_terpilih > 0): ?>
                    <input type="hidden" name="kategori" value="<?= $kategori_terpilih ?>">
                <?php endif; ?>
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search_keyword) ?>" placeholder="Cari Koleksi..." class="pl-10 pr-4 py-2 bg-gray-100 rounded-full text-sm w-64 focus:ring-2 focus:ring-red-900 outline-none transition-all">
            </form>

            <a href="keranjang.php" class="relative cursor-pointer block hover:scale-105 transition-transform">
                <i data-lucide="shopping-bag" class="w-6 h-6 text-gray-600 hover:text-red-900 transition-colors"></i>
                <span id="cartBadgeCount" class="absolute -top-1 -right-1 bg-red-700 text-white text-[10px] w-4 h-4 flex items-center justify-center rounded-full font-bold">
                    <?php echo isset($_SESSION['keranjang']) ? array_sum($_SESSION['keranjang']) : 0; ?>
                </span>
            </a>
            
            <?php if($is_logged_in): ?>
                <div class="flex items-center gap-3">
                    <a href="profile.php" class="flex items-center gap-2 bg-red-50 border border-red-100 pr-4 pl-1.5 py-1.5 rounded-full shadow-sm hover:bg-red-100 hover:border-red-200 transition-all duration-300 cursor-pointer">
                        <div class="w-7 h-7 rounded-full bg-red-900 text-white font-bold text-xs flex items-center justify-center uppercase">
                            <?= substr($_SESSION['user_email'], 0, 1); ?>
                        </div>
                        <span class="text-xs font-bold text-red-900 max-w-[100px] truncate">
                            <?= $user_role === 'guest' ? 'Mode Guest' : $_SESSION['user_email']; ?>
                        </span>
                    </a>
                    <a href="?logout=true" class="text-xs font-bold text-gray-400 hover:text-red-600 transition underline">Logout</a>
                </div>
            <?php else: ?>
                <button onclick="toggleModal()" class="bg-red-900 text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-red-800 transition shadow-lg shadow-red-200">Masuk</button>
            <?php endif; ?>
        </div>
    </nav>

    <header class="container mx-auto px-4 md:px-12 py-8">
        <div class="red-gradient rounded-[40px] p-12 text-white relative overflow-hidden flex flex-col md:flex-row items-center justify-between shadow-2xl">
            <div class="absolute top-0 right-0 w-1/2 h-full opacity-10 pointer-events-none">
                <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg" class="scale-150 transform">
                    <path fill="#FFFFFF" d="M44.7,-76.4C58.2,-69.2,70.1,-58.5,78.2,-45.3C86.3,-32.1,90.6,-16,88.9,-0.9C87.3,14.1,79.7,28.2,70.3,40.1C60.8,51.9,49.5,61.5,36.5,69.5C23.5,77.5,8.8,83.9,-5.8,83.9C-20.4,83.9,-34.9,77.5,-47.3,68.9C-59.7,60.2,-69.9,49.4,-76.8,36.7C-83.6,24.1,-87.1,9.6,-85.7,-4.3C-84.3,-18.2,-78,-31.6,-68.8,-42.9C-59.6,-54.2,-47.5,-63.3,-34.7,-70.9C-21.9,-78.6,-8.4,-84.7,4.3,-84.7C17.1,-84.7,31.2,-83.6,44.7,-76.4Z" transform="translate(100 100)" />
                </svg>
            </div>
            <div class="md:w-3/5 z-10">
                <span class="inline-block glass px-4 py-1.5 rounded-full text-[11px] font-bold tracking-[0.3em] uppercase mb-6">Premium Shade</span>
                <h2 class="serif text-4xl md:text-6xl italic leading-tight mb-4">Roemah Raga Nusantara</h2>
                <div class="h-1 w-24 bg-white/30 mb-6 rounded-full"></div>
                <p class="text-lg opacity-80 max-w-xl leading-relaxed mb-8">Kenakan keindahan budaya Nusantara dalam setiap langkahmu. Tampil elegan dengan sentuhan tradisi yang dipadukan gaya modern.</p>
                <div class="flex gap-4">
                    <a href="#koleksi-utama" class="bg-white text-red-950 px-8 py-4 rounded-2xl font-bold hover:scale-105 transition-all shadow-xl block text-center">Lihat Koleksi</a>
                </div>
            </div>
            <div class="hidden md:block md:w-1/3 z-10">
                <div class="glass p-4 rounded-[32px] rotate-3 hover:rotate-0 transition-all duration-500">
                    <img src="https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&q=80&w=600" class="rounded-[24px] shadow-2xl" alt="Featured">
                </div>
            </div>
        </div>
    </header>

    <section id="koleksi-utama" class="container mx-auto px-12 py-10 bg-white rounded-[50px] shadow-sm mb-12">
        <div class="text-center mb-10">
            <h3 class="serif text-2xl text-gray-800">Kategori Pilihan</h3>
        </div>

        <div class="flex flex-wrap justify-center gap-4">
            <a href="index1.php?<?= !empty($search_keyword) ? 'search='.urlencode($search_keyword) : '' ?>#koleksi-utama" class="px-10 py-4 rounded-2xl font-bold text-sm transition-all shadow-sm <?= ($kategori_terpilih === 0) ? 'bg-red-900 text-white' : 'bg-white text-gray-500 border border-gray-100 hover:border-red-900'; ?>">
                Semua Raga
            </a>

            <?php 
            if ($kategori_query && $kategori_query->num_rows > 0) {
                while ($kat = $kategori_query->fetch_assoc()) {
                    $id_kat = isset($kat['id_kategori']) ? intval($kat['id_kategori']) : 0;
                    $isActive = ($kategori_terpilih === $id_kat) ? true : false;
                    $buttonClass = $isActive 
                        ? 'bg-red-900 text-white shadow-md' 
                        : 'bg-white text-gray-500 border border-gray-100 hover:border-red-900';

                    $url_kat = "index1.php?kategori=" . $id_kat;
                    if (!empty($search_keyword)) {
                        $url_kat .= "&search=" . urlencode($search_keyword);
                    }

                    echo '<a href="' . $url_kat . '#koleksi-utama" class="px-10 py-4 rounded-2xl font-bold text-sm transition-all shadow-sm ' . $buttonClass . '">';
                    echo htmlspecialchars($kat['nama_kategori']);
                    echo '</a>';
                }
            }
            ?>
        </div>
        <br>
        <div class="flex justify-center gap-12 md:gap-24">
            <div class="group flex flex-col items-center cursor-pointer">
                <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center group-hover:bg-red-900 group-hover:text-white transition-all shadow-inner">
                    <i data-lucide="sparkles" class="w-10 h-10"></i>
                </div>
                <span class="mt-4 font-bold text-xs uppercase tracking-widest text-gray-600">Dress</span>
            </div>
        </div>
    </section>

    <main class="container mx-auto px-12 pb-24">
        <div class="flex justify-between items-end mb-8">
            <div>
                <h3 class="serif text-3xl text-gray-900">
                    <?php 
                    if (!empty($search_keyword)) {
                        echo "Hasil Pencarian: \"" . htmlspecialchars($search_keyword) . "\"";
                    } elseif ($kategori_terpilih > 0) {
                        echo "Koleksi Hasil Filter";
                    } else {
                        echo "Koleksi Terlaris";
                    }
                    ?>
                </h3>
                <p class="text-gray-400 italic">"Merayakan Budaya, Memaknai Gaya"</p>
            </div>
            <?php if (!empty($search_keyword)): ?>
                <a href="index1.php#koleksi-utama" class="text-xs font-bold text-red-900 hover:underline flex items-center gap-1">
                    <i data-lucide="rotate-ccw" class="w-3 h-3"></i> Bersihkan Pencarian
                </a>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php 
            if ($produk_query && $produk_query->num_rows > 0): 
                while ($p = $produk_query->fetch_assoc()): 
                    $gambar_path = !empty($p['gambar']) ? 'admin-panel/uploads/' . $p['gambar'] : 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=400';
                    $prod_id = $p['id'];
                    
                    // Cek apakah produk ini sudah masuk daftar koleksi pengguna atau belum
                    $is_favorited = false;
                    if ($is_logged_in && $user_role !== 'guest') {
                        $fav_check = $koneksi->query("SELECT id FROM collections WHERE user_id = $user_id AND product_id = $prod_id");
                        if ($fav_check && $fav_check->num_rows > 0) {
                            $is_favorited = true;
                        }
                    }
            ?>

            <div class="group block">
                <div class="group bg-white rounded-[32px] overflow-hidden border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300">
                    <div class="relative h-72 overflow-hidden bg-gray-100 cursor-pointer" onclick="window.location.href='detail.php?id=<?= $p['id'] ?>'">
                        <img src="<?= $gambar_path ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" alt="Product">
                        
                        <div class="absolute top-4 right-4 flex flex-col gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0">
                            <button class="btn-fav-heart bg-white p-3 rounded-full shadow-lg hover:bg-red-900 hover:text-white transition text-gray-400" 
                                    data-id="<?= $prod_id ?>" 
                                    onclick="event.stopPropagation();">
                                <i data-lucide="heart" class="w-4 h-4 <?= $is_favorited ? 'text-red-600 fill-red-600' : '' ?>"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <h4 class="font-bold text-gray-800 text-lg mb-1 cursor-pointer" onclick="window.location.href='detail.php?id=<?= $p['id'] ?>'"><?= htmlspecialchars($p['nama_produk']) ?></h4>
                        <p class="text-red-900 font-bold text-xl">Rp <?= number_format($p['harga'], 0, ',', '.') ?></p>
                        
                        <button onclick="handleProtectedAction('Menambah ke Keranjang', <?= $p['id'] ?>)" class="w-full mt-4 py-3 border border-gray-100 rounded-xl text-sm font-bold text-gray-400 group-hover:bg-red-900 group-hover:text-white transition-all">
                            Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
            
            <?php 
                endwhile; 
            else:
            ?>
                <div class="col-span-full text-center py-12 text-gray-400">
                    <i data-lucide="package-search" class="w-12 h-12 mx-auto mb-3 opacity-50"></i>
                    Belum ada koleksi produk yang cocok dengan pencarian Anda.
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-white border-t border-gray-100 py-12">
        <div class="container mx-auto px-12 text-center text-gray-400">
            <p class="text-[10px] uppercase tracking-widest">&copy; <?= date('Y'); ?> Roemah Raga Nusantara. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        // Inisialisasi ikon Lucide
        lucide.createIcons();

        // Mengambil status session dari PHP backend
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        const userRole = "<?php echo $user_role; ?>";

        // Fungsi kontrol buka/tutup Modal Login Form
        function toggleModal() {
            const modal = document.getElementById('loginModal');
            if (modal) {
                if(modal.classList.contains('hidden')) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                } else {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            }
        }

        // Fungsi switch tab antara form Login dan Register di dalam modal
        function switchAuthMode(mode) {
            const loginBox = document.getElementById('loginBox');
            const registerBox = document.getElementById('registerBox');
            if (loginBox && registerBox) {
                if (mode === 'register') {
                    loginBox.classList.add('hidden');
                    registerBox.classList.remove('hidden');
                } else {
                    registerBox.classList.add('hidden');
                    loginBox.classList.remove('hidden');
                }
            }
        }

        // =========================================================
        // SINKRONISASI AJAX: TAMBAH KE KERANJANG BELANJA
        // =========================================================
        function handleProtectedAction(actionName, productId = null) {
            if (!isLoggedIn) {
                toggleModal();
            } else if (userRole === 'guest') {
                alert("Akses Ditolak! Anda masuk sebagai Guest. Silahkan login dengan akun resmi/Google terlebih dahulu untuk menambah produk ke keranjang belanja.");
                toggleModal();
            } else {
                if (productId) {
                    const formData = new FormData();
                    // Mengirimkan parameter 'action' agar ditangkap dengan benar oleh backend PHP
                    formData.append('action', 'add_to_cart');
                    formData.append('product_id', productId);

                    fetch('index1.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Respon Jaringan Bermasalah');
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            alert("Berhasil dimasukkan ke keranjang belanja!");
                            
                            // Sinkronisasi badge total item pada Navbar di index1.php maupun keranjang.php
                            const badge = document.getElementById('cartBadgeCount') || document.querySelector('nav a relative span.absolute') || document.querySelector('nav span.absolute');
                            if (badge) {
                                badge.innerText = data.cart_count;
                            }
                        } else {
                            alert(data.message || "Gagal menambahkan produk.");
                        }
                    })
                    .catch(err => {
                        console.error("Cart Request Error:", err);
                        alert("Terjadi masalah jaringan saat mencoba menyimpan produk.");
                    });
                }
            }
        }

        // =========================================================
        // LOGIKA INTEGRASI EVENT CLICK TOMBOL FAVORIT (WISHLIST)
        // =========================================================
        document.querySelectorAll('.btn-fav-heart').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!isLoggedIn) {
                    toggleModal();
                    return;
                }
                if (userRole === 'guest') {
                    alert("Akses Ditolak! Mode Guest tidak dapat menyimpan koleksi favorit.");
                    toggleModal();
                    return;
                }

                const productId = this.getAttribute('data-id');
                const icon = this.querySelector('svg');

                if (productId) {
                    const formData = new FormData();
                    formData.append('product_id', productId);

                    fetch('add_to_collection.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Respon server bermasalah');
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'added') {
                            alert(data.message);
                            if (icon) icon.classList.add('text-red-600', 'fill-red-600');
                        } else if (data.status === 'removed') {
                            alert(data.message);
                            if (icon) icon.classList.remove('text-red-600', 'fill-red-600');
                        } else if (data.status === 'login_required') {
                            alert(data.message);
                            toggleModal();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Wishlist Integration Error:', error);
                        alert("Terjadi kesalahan koneksi saat memproses favorit.");
                    });
                }
            });
        });

        // =========================================================
        // GOOGLE SIGN-IN API HELPER METHOD
        // =========================================================
        function parseJwt(token) {
            try {
                var base64Url = token.split('.')[1];
                var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
                    return '%' + ('0' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
                return JSON.parse(jsonPayload);
            } catch (error) {
                console.error("Gagal mendecode JWT Token:", error);
                return null;
            }
        }

        // Handler Callback untuk merespon Google API Login
        function handleCredentialResponse(response) {
            const responsePayload = parseJwt(response.credential);
            if(responsePayload && responsePayload.email) {
                const userEmail = responsePayload.email;

                const formData = new FormData();
                formData.append('google_login_active', 'true');
                formData.append('google_email', userEmail);

                fetch('index1.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => {
                    if (!res.ok) throw new Error('Respon Jaringan Bermasalah');
                    return res.json();
                })
                .then(data => {
                    if(data.status === 'success') {
                        // Reload halaman agar session baru terbaca dan data keranjang disinkronkan dari DB
                        window.location.reload();
                    } else {
                        alert("Gagal sinkronisasi login Google dengan sistem backend.");
                    }
                })
                .catch(err => {
                    console.error("Google Auth Integration Error:", err);
                    alert("Terjadi kesalahan sistem saat memproses autentikasi Google.");
                });
            } else {
                alert("Gagal membaca profile data dari Google Account.");
            }
        }
    </script>
</body>
</html>