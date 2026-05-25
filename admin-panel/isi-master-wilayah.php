<?php
// Menggunakan konfigurasi database SSL Aiven yang sudah ada
require_once 'config/database.php';

set_time_limit(0); // Mencegah timeout karena kita akan melakukan multi-request API
ini_set('memory_limit', '512M'); 

echo "<h3 style='font-family: sans-serif; color: #0288d1;'>⚡ Memulai Sinkronisasi Data Wilayah JAPAK (JSON API CDN Method)...</h3>";
echo "<p style='font-family: sans-serif; color: #555;'>⏳ Menghubungkan ke API emsifa dan menyiapkan database cloud Aiven...</p>";
flush();

// 1. Matikan pengecekan Foreign Key & Autocommit agar super cepat ke database cloud
$db->query("SET FOREIGN_KEY_CHECKS = 0;");
$db->query("SET AUTOCOMMIT = 0;");
$db->query("START TRANSACTION;");

// 2. Kosongkan database JAPAK agar tidak duplikat data
$db->query("TRUNCATE TABLE `wilayah_kecamatan`;");
$db->query("TRUNCATE TABLE `wilayah_kabupaten`;");
$db->query("TRUNCATE TABLE `wilayah_provinsi`;");

$prov_count = 0;
$kab_count = 0;
$kec_count = 0;

// Fungsi helper untuk ambil data dari API URL dan mengubahnya menjadi Array PHP
function fetch_json($url) {
    $context = stream_context_create([
        "http" => ["header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"]
    ]);
    $json = file_get_contents($url, false, $context);
    return $json ? json_decode($json, true) : false;
}

// 3. AMBIL DATA PROVINSI
$url_provinsi = "https://emsifa.github.io/api-wilayah-indonesia/api/provinces.json";
$provinsis = fetch_json($url_provinsi);

if ($provinsis === false) {
    $db->query("ROLLBACK;");
    die("<p style='color: red; font-family: sans-serif;'>❌ Gagal mengambil data provinsi dari API Master. Sinkronisasi dibatalkan.</p>");
}

foreach ($provinsis as $prov) {
    $prov_id   = $db->real_escape_string($prov['id']);
    $prov_nama = $db->real_escape_string($prov['name']);
    
    $sql_prov = "INSERT INTO `wilayah_provinsi` (`id`, `nama`) VALUES ('$prov_id', '$prov_nama')";
    if ($db->query($sql_prov)) {
        $prov_count++;
    }

    // 4. AMBIL DATA KABUPATEN (Berdasarkan ID Provinsi saat ini)
    $url_kabupaten = "https://emsifa.github.io/api-wilayah-indonesia/api/regencies/{$prov_id}.json";
    $kabupatens = fetch_json($url_kabupaten);
    
    if ($kabupatens) {
        foreach ($kabupatens as $kab) {
            $kab_id   = $db->real_escape_string($kab['id']);
            $kab_nama = $db->real_escape_string($kab['name']);
            
            $sql_kab = "INSERT INTO `wilayah_kabupaten` (`id`, `provinsi_id`, `nama`) VALUES ('$kab_id', '$prov_id', '$kab_nama')";
            if ($db->query($sql_kab)) {
                $kab_count++;
            }

            // 5. AMBIL DATA KECAMATAN (Berdasarkan ID Kabupaten saat ini)
            $url_kecamatan = "https://emsifa.github.io/api-wilayah-indonesia/api/districts/{$kab_id}.json";
            $kecamatans = fetch_json($url_kecamatan);
            
            if ($kecamatans) {
                foreach ($kecamatans as $kec) {
                    $kec_id   = $db->real_escape_string($kec['id']);
                    $kec_nama = $db->real_escape_string($kec['name']);
                    
                    $sql_kec = "INSERT INTO `wilayah_kecamatan` (`id`, `kabupaten_id`, `nama`) VALUES ('$kec_id', '$kab_id', '$kec_nama')";
                    if ($db->query($sql_kec)) {
                        $kec_count++;
                    }
                }
            }
        }
    }
    
    // Tampilkan log progress di browser per provinsi agar tidak dikira nge-hang
    echo "<span style='font-family: sans-serif; font-size: 13px; color: #4caf50;'>✔️ Provinsi ID $prov_id ($prov_nama) beserta sub-wilayahnya selesai diproses.</span><br>";
    flush();
}

// 6. Finalisasi Transaksi Massal
if (($prov_count + $kab_count + $kec_count) > 0) {
    $db->query("COMMIT;");
    
    echo "<div style='font-family: sans-serif; margin-top: 20px; padding: 15px; background: #f5f5f5; border-left: 5px solid #0288d1;'>";
    echo "<h3>📊 Rincian Data Wilayah JAPAK Sukses Ter-inject:</h3>";
    echo "🔹 Provinsi Berhasil: <b>$prov_count</b> baris.<br>";
    echo "🔹 Kabupaten Berhasil: <b>$kab_count</b> baris.<br>";
    echo "🔹 Kecamatan Berhasil: <b>$kec_count</b> baris.<br>";
    echo "</div>";

    echo "<div style='font-family: sans-serif; padding: 20px; background: #e8f5e9; color: #2e7d32; border-radius: 15px; margin-top: 20px; border: 1px solid #a5d6a7;'>";
    echo "🏆 <b>Sukses Sempurna, Zi!</b> Seluruh jenjang administrasi wilayah berhasil ditanam ke database Aiven. Dropdown di <b>profile.php</b> dijamin langsung aktif meluncur!";
    echo "</div>";
} else {
    $db->query("ROLLBACK;");
    echo "<div style='font-family: sans-serif; padding: 20px; background: #ffebee; color: #c62828; border-radius: 15px; margin-top: 20px; border: 1px solid #ffcdd2;'>";
    echo "❌ <b>Gagal Total!</b> Tidak ada data yang berhasil masuk ke database cloud.";
    echo "</div>";
}

// Kembalikan konfigurasi database ke kondisi semula
$db->query("SET FOREIGN_KEY_CHECKS = 1;");
$db->query("SET AUTOCOMMIT = 1;");
?>