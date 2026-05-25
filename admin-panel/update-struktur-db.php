<?php 
// Menggunakan konfigurasi database SSL Aiven yang sudah ada
require_once 'config/database.php'; 

echo "<h3 style='font-family: sans-serif; color: #0288d1;'>🔄 Memulai Migrasi Struktur Database Wilayah Sekelas Enterprise...</h3>";

// Pastikan koneksi $db aman
if (!$db) {
    die("<p style='color: red; font-family: sans-serif;'>❌ Gagal terhubung ke database. Periksa file config/database.php Anda.</p>");
}

// Matikan pengecekan foreign key sementara agar proses migrasi lancar
$db->query("SET FOREIGN_KEY_CHECKS = 0;");

// --- LANGKAH 1: PENGECEKAN & PENAMBAHAN KOLOM TABEL PENGGUNA ---
// Ambil struktur kolom yang saat ini ada di tabel pengguna
$check_columns = $db->query("SHOW COLUMNS FROM pengguna");

$existing_columns = [];

if ($check_columns === false) {
    echo "<p style='color: orange; font-family: sans-serif;'>⚠️ Tabel <b>pengguna</b> tidak ditemukan atau gagal diakses: " . $db->error . "</p>";
    echo "<p style='color: #0288d1; font-family: sans-serif;'>🛠️ Mencoba membuat tabel <b>pengguna</b> dasar terlebih dahulu...</p>";
    
    // Membuat tabel pengguna otomatis jika belum ada agar migrasi tidak macet
    $create_user_table = "CREATE TABLE IF NOT EXISTS pengguna (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        nama VARCHAR(255) NULL,
        foto VARCHAR(255) NULL,
        password VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($db->query($create_user_table)) {
        echo "<p style='color: green; font-family: sans-serif;'>✔️ Tabel <b>pengguna</b> baru berhasil dibuat.</p>";
    } else {
        die("<p style='color: red; font-family: sans-serif;'>❌ Gagal membuat tabel pengguna dasar: " . $db->error . "</p>");
    }
} else {
    while ($row = $check_columns->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
}

// Daftar kolom baru yang wajib diinjeksikan beserta tipe datanya
$columns_to_add = [
    'provinsi_id' => "CHAR(2) NULL",
    'kabupaten_id' => "CHAR(4) NULL",
    'kecamatan_id' => "CHAR(7) NULL",
    'alamat_spesifik' => "TEXT NULL"
];

foreach ($columns_to_add as $column_name => $data_type) {
    if (!in_array($column_name, $existing_columns)) {
        $alter_sql = "ALTER TABLE pengguna ADD COLUMN $column_name $data_type";
        if ($db->query($alter_sql)) {
            echo "<p style='color: green; font-family: sans-serif;'>✔️ Kolom <b>$column_name</b> berhasil ditambahkan ke tabel pengguna.</p>";
        } else {
            echo "<p style='color: red; font-family: sans-serif;'>❌ Gagal menambahkan kolom $column_name: " . $db->error . "</p>";
        }
    } else {
        echo "<p style='color: #7f8c8d; font-family: sans-serif;'>ℹ️ Kolom <b>$column_name</b> sudah ada di tabel pengguna, melewati proses.</p>";
    }
}


// --- LANGKAH 2: PEMBUATAN TABEL MASTER WILAYAH ---
$table_queries = [
    // Membuat Tabel Master Provinsi
    "provinsi" => "CREATE TABLE IF NOT EXISTS wilayah_provinsi (
        id CHAR(2) PRIMARY KEY,
        nama VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Membuat Tabel Master Kota / Kabupaten
    "kabupaten" => "CREATE TABLE IF NOT EXISTS wilayah_kabupaten (
        id CHAR(4) PRIMARY KEY,
        provinsi_id CHAR(2),
        nama VARCHAR(255) NOT NULL,
        FOREIGN KEY (provinsi_id) REFERENCES wilayah_provinsi(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    // Membuat Tabel Master Kecamatan
    "kecamatan" => "CREATE TABLE IF NOT EXISTS wilayah_kecamatan (
        id CHAR(7) PRIMARY KEY,
        kabupaten_id CHAR(4),
        nama VARCHAR(255) NOT NULL,
        FOREIGN KEY (kabupaten_id) REFERENCES wilayah_kabupaten(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

$success = true;
foreach ($table_queries as $name => $sql) {
    if (!$db->query($sql)) {
        echo "<p style='color: red; font-family: sans-serif;'>❌ Gagal membuat tabel master $name: " . $db->error . "</p>";
        $success = false;
        break;
    } else {
        echo "<p style='color: green; font-family: sans-serif;'>✔️ Tabel master <b>wilayah_$name</b> siap digunakan.</p>";
    }
}

// Hidupkan kembali pengecekan foreign key demi integritas relasi data
$db->query("SET FOREIGN_KEY_CHECKS = 1;");

if ($success) {
    echo "<div style='font-family: sans-serif; padding: 20px; background: #e8f5e9; border-radius: 15px; color: #2e7d32; border: 1px solid #a5d6a7; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>";
    echo "<h3 style='margin-top:0;'>🎉 BOOM! Struktur Database Alamat JAPAK Sukses Terintegrasi!</h3>";
    echo "<p>Sistem deteksi kolom berhasil melewati proteksi MySQL dan seluruh tabel master siap menampung data pengiriman raga.</p>";
    echo "</div>";
}
?>