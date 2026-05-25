<?php
// cek_tabel.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Hubungkan ke database menggunakan konfigurasi proyekmu
require_once 'config/database.php';

// Selaraskan variabel koneksi (menyesuaikan database.php milikmu)
$koneksi = isset($conn) ? $conn : (isset($db) ? $db : null);

if (!$koneksi) {
    die("❌ Gagal memuat koneksi database. Periksa variabel \$conn atau \$db di config/database.php.");
}

// 2. Eksekusi query untuk mengambil semua nama tabel
$query = "SHOW TABLES";
$result = $koneksi->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Table Explorer | JAPAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen p-6 md:p-12" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <div class="max-w-3xl mx-auto bg-gray-800 rounded-[32px] p-6 md:p-8 border border-gray-700 shadow-2xl">
        
        <div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-700">
            <div class="p-3.5 bg-amber-950 text-amber-400 border border-amber-800/50 rounded-2xl shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white">JAPAK Table Explorer</h1>
                <p class="text-xs text-gray-400">Membaca struktur tabel aktif dari cluster database kamu</p>
            </div>
        </div>

        <div class="space-y-3.5">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Struktur Tabel yang Tersedia:</p>
            
            <?php 
            if ($result && $result->num_rows > 0) {
                $no = 1;
                while ($row = $result->fetch_array()) {
                    $nama_tabel = $row[0];
                    
                    // Query tambahan opsional untuk menghitung jumlah baris/data di tabel ini
                    $count_query = "SELECT COUNT(*) FROM " . $nama_tabel;
                    $count_result = $koneksi->query($count_query);
                    $jumlah_row = 0;
                    if ($count_result) {
                        $count_row = $count_result->fetch_array();
                        $jumlah_row = $count_row[0];
                    }

                    echo '<div class="flex items-center justify-between p-4.5 bg-gray-850 border border-gray-700/70 rounded-2xl hover:bg-gray-700/40 transition-all group">';
                    echo '  <div class="flex items-center gap-3.5">';
                    echo '      <span class="text-xs text-gray-500 font-mono font-bold group-hover:text-amber-400 transition-colors">[' . str_pad($no++, 2, "0", STR_PAD_LEFT) . ']</span>';
                    echo '      <span class="font-mono text-sm font-semibold text-gray-200 tracking-wide">' . htmlspecialchars($nama_tabel) . '</span>';
                    echo '  </div>';
                    
                    // Tampilkan badge jumlah data di dalam tabel
                    echo '  <div class="flex items-center gap-2">';
                    echo '      <span class="text-[11px] font-mono text-gray-400 bg-gray-800 border border-gray-700 px-3 py-1 rounded-xl font-medium">' . $jumlah_row . ' data</span>';
                    echo '      <span class="text-[10px] bg-amber-950 text-amber-400 border border-amber-900/50 px-2.5 py-1 rounded-lg font-bold uppercase tracking-wider">Active</span>';
                    echo '  </div>';
                    echo '</div>';
                }
            } elseif ($result && $result->num_rows === 0) {
                echo '<div class="p-6 bg-amber-950/30 border border-amber-900/50 text-amber-400 rounded-2xl text-sm text-center">⚠️ Database terkoneksi, namun belum ada tabel sama sekali (kosong). Silakan lakukan import file .sql kamu.</div>';
            } else {
                echo '<div class="p-4 bg-red-950/50 border border-red-900/50 text-red-400 rounded-2xl text-sm">❌ Gagal memuat data tabel: ' . $koneksi->error . '</div>';
            }
            ?>
        </div>

        <div class="mt-8 pt-5 border-t border-gray-700 flex flex-col sm:flex-row justify-between items-center gap-3 text-xs text-gray-500">
            <div>Total Terdeteksi: <span class="font-bold text-white"><?= $result ? $result->num_rows : 0; ?> Tabel</span></div>
            <div class="text-emerald-500 font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span> Query Executed Successfully
            </div>
        </div>
    </div>

</body>
</html>