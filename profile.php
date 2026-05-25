<?php
session_start();
require_once 'admin-panel/config/database.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: index1.php");
    exit();
}

$user_email = $_SESSION['user_email'];

// Ambil pesan dari session jika ada (akibat redirect)
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";

// Hapus sisa session message agar tidak muncul terus-menerus saat di-refresh manual
unset($_SESSION['success_msg']);
unset($_SESSION['error_msg']);

// PROSES SIMPAN ALAMAT
if (isset($_POST['save_alamat'])) {
    $provinsi = trim($_POST['provinsi'] ?? '');
    $kabupaten = trim($_POST['kabupaten'] ?? '');
    $kecamatan = trim($_POST['kecamatan'] ?? '');
    $alamat_spesifik = trim($_POST['alamat_spesifik'] ?? '');

    if (empty($provinsi) || empty($kabupaten) || empty($kecamatan) || empty($alamat_spesifik)) {
        $_SESSION['error_msg'] = "Semua tingkatan lokasi dan alamat detail wajib diisi!";
    } else {
        $update = $db->prepare("UPDATE pengguna SET provinsi_id = ?, kabupaten_id = ?, kecamatan_id = ?, alamat_spesifik = ? WHERE email = ?");
        
        if ($update) {
            $update->bind_param("sssss", $provinsi, $kabupaten, $kecamatan, $alamat_spesifik, $user_email);
            
            if ($update->execute()) {
                // ✅ TAMBAHAN: Simpan nama wilayah ke session agar langsung tampil
                $q = $db->prepare("
                    SELECT 
                        prov.nama AS nama_provinsi,
                        kab.nama  AS nama_kabupaten,
                        kec.nama  AS nama_kecamatan
                    FROM wilayah_provinsi prov
                    LEFT JOIN wilayah_kabupaten kab 
                        ON REPLACE(TRIM(kab.id), '.', '') = REPLACE(TRIM(?), '.', '')
                    LEFT JOIN wilayah_kecamatan kec 
                        ON REPLACE(TRIM(kec.id), '.', '') = REPLACE(TRIM(?), '.', '')
                    WHERE REPLACE(TRIM(prov.id), '.', '') = REPLACE(TRIM(?), '.', '')
                    LIMIT 1
                ");
                $q->bind_param("sss", $kabupaten, $kecamatan, $provinsi);
                $q->execute();
                $wilayah = $q->get_result()->fetch_assoc();

                $_SESSION['alamat_cache'] = [
                    'provinsi_id'    => $provinsi,
                    'kabupaten_id'   => $kabupaten,
                    'kecamatan_id'   => $kecamatan,
                    'alamat_spesifik'=> $alamat_spesifik,
                    'nama_provinsi'  => $wilayah['nama_provinsi']  ?? '',
                    'nama_kabupaten' => $wilayah['nama_kabupaten'] ?? '',
                    'nama_kecamatan' => $wilayah['nama_kecamatan'] ?? '',
                ];

                $_SESSION['success_msg'] = "Alamat pengiriman raga Anda berhasil diperbarui!";
            } else {
                $_SESSION['error_msg'] = "Gagal mengeksekusi penyimpanan data ke cloud database: " . $update->error;
            }
            $update->close();
        } else {
            $_SESSION['error_msg'] = "Gagal menyiapkan struktur query: " . $db->error;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// FETCH DATA USER
$stmt = $db->prepare("
    SELECT nama, foto, provinsi_id, kabupaten_id, kecamatan_id, alamat_spesifik
    FROM pengguna
    WHERE email = ?
");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    $user_data = [
        'nama' => '', 'foto' => '', 'provinsi_id' => '', 'kabupaten_id' => '',
        'kecamatan_id' => '', 'alamat_spesifik' => '',
        'nama_provinsi' => '', 'nama_kabupaten' => '', 'nama_kecamatan' => ''
    ];
} else {
    // ✅ Query nama wilayah TERPISAH — lebih reliable dari JOIN kompleks
    $user_data['nama_provinsi'] = '';
    $user_data['nama_kabupaten'] = '';
    $user_data['nama_kecamatan'] = '';

    if (!empty($user_data['provinsi_id'])) {
        $q = $db->prepare("SELECT nama FROM wilayah_provinsi WHERE REPLACE(TRIM(id),'.','') = REPLACE(TRIM(?),'.','') LIMIT 1");
        $q->bind_param("s", $user_data['provinsi_id']);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        $user_data['nama_provinsi'] = $r['nama'] ?? '';
    }

    if (!empty($user_data['kabupaten_id'])) {
        $q = $db->prepare("SELECT nama FROM wilayah_kabupaten WHERE REPLACE(TRIM(id),'.','') = REPLACE(TRIM(?),'.','') LIMIT 1");
        $q->bind_param("s", $user_data['kabupaten_id']);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        $user_data['nama_kabupaten'] = $r['nama'] ?? '';
    }

    if (!empty($user_data['kecamatan_id'])) {
        $q = $db->prepare("SELECT nama FROM wilayah_kecamatan WHERE REPLACE(TRIM(id),'.','') = REPLACE(TRIM(?),'.','') LIMIT 1");
        $q->bind_param("s", $user_data['kecamatan_id']);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        $user_data['nama_kecamatan'] = $r['nama'] ?? '';
    }
}

// ✅ TAMBAHAN: Override dengan session cache jika ada (hasil redirect pasca simpan)
if (!empty($_SESSION['alamat_cache'])) {
    $cache = $_SESSION['alamat_cache'];
    // Gabungkan ke $user_data — nama wilayah dari cache diprioritaskan
    $user_data = array_merge($user_data, $cache);
    unset($_SESSION['alamat_cache']); // Hapus setelah dipakai sekali
}

$display_name = !empty($user_data['nama']) ? $user_data['nama'] : explode('@', $user_email)[0];
$display_foto = 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&s=200';

$provinsi_res = $db->query("SELECT id, nama FROM wilayah_provinsi ORDER BY nama ASC");

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index1.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya | Roemah Raga Nusantara</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #fcfcfc; 
            margin: 0; 
        }
        .serif { font-family: 'Playfair Display', serif; }
        .red-gradient { background: linear-gradient(135deg, #991b1b 0%, #450a0a 100%); }
        
        .header {
            background: linear-gradient(135deg, #991b1b 0%, #450a0a 100%);
            color: white; 
            padding: 40px 20px 50px;
            border-radius: 0 0 40px 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .top-container { 
            display: flex;
            align-items: flex-start;
            gap: 24px;
            max-width: 800px; 
            margin: 0 auto;
            position: relative;
        }

        .profile-avatar-area { 
            position: relative; 
            flex-shrink: 0;
            cursor: pointer;
        }
        .profile-avatar-area img { 
            width: 95px; 
            height: 95px; 
            border-radius: 50%; 
            border: 3px solid rgba(255,255,255,0.3);
            object-fit: cover;
            animation: pulse-gold 2s infinite;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        @keyframes pulse-gold {
            0% { box-shadow: 0 0 0 0 rgba(255,255,255,0.4); }
            70% { box-shadow: 0 0 0 12px rgba(255,255,255,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,255,255,0); }
        }

        .profile-text-area {
            flex-grow: 1;
            padding-top: 2px;
            padding-right: 45px;
        }

        .address-summary {
            margin-top: 12px;
            background: rgba(0, 0, 0, 0.15);
            padding: 10px 14px;
            border-radius: 12px;
            max-width: 550px;
            border-left: 3px solid #facc15;
        }

        .card {
            background: white; 
            margin: -25px 20px 20px;
            padding: 25px; 
            border-radius: 30px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .orders { display: flex; justify-content: space-around; margin-top: 20px; }
        .orders div { text-align: center; cursor: pointer; transition: 0.3s; }
        .orders div:hover { transform: translateY(-5px); }
        
        .orders i { 
            font-size: 24px; 
            margin-bottom: 8px;
            width: 55px;
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
            background: #fff5f5;
            color: #991b1b;
        }

        .orders div:nth-child(2) i { background: #f0f7ff; color: #2196f3; }
        .orders div:nth-child(3) i { background: #fffdf0; color: #facc15; }

        .modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
            display: flex; justify-content: center; align-items: center; z-index: 100;
        }
        .modal-content {
            background: white; padding: 40px; border-radius: 40px;
            width: 90%; max-width: 400px; text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.2);
        }
        .hidden { display: none; }

        input[type="text"], select, textarea {
            width: 100%; padding: 14px; margin: 8px 0 15px 0;
            border-radius: 15px; border: 1px solid #eee; outline: none;
            background: #f9f9f9; font-size: 14px;
        }
        select:disabled { opacity: 0.6; background: #f1f1f1; cursor: not-allowed; }

        .btn-save { background: #991b1b; color: white; padding: 12px 25px; border-radius: 12px; font-weight: bold; border: none; cursor: pointer; width: 100%; }
        .btn-cancel { color: #999; margin-top: 15px; display: block; font-size: 14px; cursor: pointer; }

        .back-link {
            color: white; font-size: 14px; text-decoration: none;
            display: flex; align-items: center; gap: 5px; opacity: 0.8;
            margin-bottom: 25px; transition: 0.3s;
        }
        .back-link:hover { opacity: 1; transform: translateX(-5px); }
    </style>
</head>

<body>

<div class="header">
    <div class="max-w-[800px] mx-auto">
        <a href="index1.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Toko
        </a>
    </div>
    
    <div class="top-container">
        <div class="profile-avatar-area" style="cursor:default;">
            <img src="<?= $display_foto ?>" alt="Avatar" style="
                width: 95px; height: 95px; border-radius: 50%;
                border: 3px solid rgba(255,255,255,0.4);
                object-fit: cover;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                animation: pulse-gold 2s infinite;
                background: #ddd;
            ">
        </div>

        <div class="profile-text-area">
            <b id="namaText" class="text-2xl block font-bold tracking-wide"><?= htmlspecialchars($display_name) ?></b>
            <span class="inline-block bg-white/20 text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-md mt-1 mb-1">
                Premium Member
            </span>
            <div class="text-[11px] opacity-50 font-medium"><?= htmlspecialchars($user_email) ?></div>
            
            <div class="address-summary">
                <div class="text-[10px] font-bold text-yellow-400 uppercase tracking-wider mb-1">
                    <i class="fa-solid fa-map-marker-alt mr-1"></i> Alamat Tujuan Utama
                </div>
                <div class="text-xs text-gray-200 line-clamp-2 leading-relaxed">
                    <?php 
                        // LOGIKA AMAN: Selama alamat_spesifik ada isinya, tampilkan data!
                        if (!empty($user_data['alamat_spesifik'])) {
                            $prov = !empty($user_data['nama_provinsi']) ? $user_data['nama_provinsi'] : "Provinsi ID: " . $user_data['provinsi_id'];
                            $kab  = !empty($user_data['nama_kabupaten']) ? $user_data['nama_kabupaten'] : "Kabupaten ID: " . $user_data['kabupaten_id'];
                            $kec  = !empty($user_data['nama_kecamatan']) ? $user_data['nama_kecamatan'] : "Kecamatan ID: " . $user_data['kecamatan_id'];
                            $det  = $user_data['alamat_spesifik'];
                            
                            echo htmlspecialchars("$prov, $kab, $kec — Detail: $det");
                        } else {
                            echo "<span class='italic opacity-60'>Belum dikonfigurasi. Atur melalui menu 'Alamat Saya' di bawah.</span>";
                        }
                    ?>
                </div>
            </div>
        </div>

        <div class="absolute right-0 top-1">
            <a href="?logout=1" class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center hover:bg-white/10 transition" title="Keluar">
                <i class="fa-solid fa-power-off text-sm"></i>
            </a>
        </div>
    </div>
</div>

<div class="card">
    <h3 class="serif text-xl text-gray-800">Status Pesanan</h3>
    <div class="orders">
        <div onclick="window.location.href='pesanan.php?tab=dikemas'">
            <i class="fa-solid fa-box"></i>
            <span class="text-[11px] font-bold text-gray-500 uppercase">Dikemas</span>
        </div>
        <div onclick="window.location.href='pesanan.php?tab=dikirim'">
            <i class="fa-solid fa-truck"></i>
            <span class="text-[11px] font-bold text-gray-500 uppercase">Dikirim</span>
        </div>
        <div onclick="window.location.href='pesanan.php?tab=penilaian'">
            <i class="fa-solid fa-star"></i>
            <span class="text-[11px] font-bold text-gray-500 uppercase">Penilaian</span>
        </div>
    </div>
</div>

<div class="max-w-[800px] mx-auto px-5">
    <?php if(!empty($success_msg)): ?>
        <div class="p-4 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-2xl text-sm mb-4">
            <i class="fa-solid fa-circle-check mr-2"></i> <?= $success_msg ?>
        </div>
    <?php endif; ?>
    <?php if(!empty($error_msg)): ?>
        <div class="p-4 bg-amber-50 text-amber-800 border border-amber-200 rounded-2xl text-sm mb-4">
            <i class="fa-solid fa-triangle-exclamation mr-2"></i> <?= $error_msg ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h3 class="serif text-xl text-gray-800 mb-4">Pengaturan Akun</h3>
    <div class="space-y-4">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl cursor-pointer hover:bg-gray-100 transition" onclick="toggleAlamatSection()">
            <div class="flex items-center gap-4">
                <i class="fa-solid fa-location-dot text-red-900 w-5 text-center"></i>
                <span class="text-sm font-semibold text-gray-700">Alamat Saya</span>
            </div>
            <i class="fa-solid fa-chevron-right text-gray-300 text-xs"></i>
        </div>
    </div>
</div>

<div id="alamatSection" class="card hidden">
    <h3 class="serif text-xl text-gray-800 mb-2">Lokasi Pengiriman</h3>
    <p class="text-xs text-gray-400 mb-6">Sesuaikan data administrasi wilayah tujuan paket Anda.</p>
    
    <form method="POST" class="space-y-1">
        <div>
            <label class="text-xs font-bold text-gray-400 uppercase ml-1">Provinsi</label>
            <select name="provinsi" id="provinsi" onchange="fetchKabupaten()">
                <option value="">-- Pilih Provinsi --</option>
                <?php if($provinsi_res): ?>
                    <?php while($row = $provinsi_res->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>" <?= (isset($user_data['provinsi_id']) && trim($user_data['provinsi_id']) == trim($row['id'])) ? 'selected' : '' ?>><?= $row['nama'] ?></option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div>
            <label class="text-xs font-bold text-gray-400 uppercase ml-1">Kota / Kabupaten</label>
            <select name="kabupaten" id="kabupaten" onchange="fetchKecamatan()" disabled>
                <option value="">-- Pilih Kota/Kabupaten --</option>
            </select>
        </div>

        <div>
            <label class="text-xs font-bold text-gray-400 uppercase ml-1">Kecamatan</label>
            <select name="kecamatan" id="kecamatan" disabled>
                <option value="">-- Pilih Kecamatan --</option>
            </select>
        </div>

        <div>
            <label class="text-xs font-bold text-gray-400 uppercase ml-1">Alamat Lengkap (Nama Jalan, RT/RW, Rumah)</label>
            <textarea name="alamat_spesifik" rows="3" placeholder="Contoh: Jl. Raya Adiwerna No.21, RT 02/RW 01" class="w-full p-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none text-sm focus:ring-2 focus:ring-red-900 mt-2"><?= htmlspecialchars($user_data['alamat_spesifik'] ?? '') ?></textarea>
        </div>

        <div class="pt-2">
            <button type="submit" name="save_alamat" class="btn-save">Simpan Perubahan Alamat</button>
        </div>
    </form>
</div>

<div class="text-center py-10 opacity-30">
    <p class="text-[10px] uppercase tracking-[0.3em]">&copy; Roemah Raga Nusantara</p>
</div>

<script>

    function toggleAlamatSection() {
        const section = document.getElementById("alamatSection");
        section.classList.toggle("hidden");
        if(!section.classList.contains("hidden")) {
            section.scrollIntoView({ behavior: 'smooth' });
        }
    }

    const oldKab = "<?= !empty($user_data['kabupaten_id']) ? trim($user_data['kabupaten_id']) : '' ?>";
    const oldKec = "<?= !empty($user_data['kecamatan_id']) ? trim($user_data['kecamatan_id']) : '' ?>";

    async function fetchKabupaten() {
        const provId = document.getElementById('provinsi').value;
        const kabSelect = document.getElementById('kabupaten');
        const kecSelect = document.getElementById('kecamatan');

        kabSelect.innerHTML = '<option value="">-- Pilih Kota/Kabupaten --</option>';
        kecSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        kabSelect.disabled = true;
        kecSelect.disabled = true;

        if (!provId) return;

        try {
            const res = await fetch(`get_wilayah.php?type=kabupaten&provinsi_id=${provId}`);
            const data = await res.json();

            if (!data || data.length === 0) return;

            kabSelect.disabled = false;
            
            data.forEach(kab => {
                const kabIdStr = String(kab.id).trim();
                const isSelected = (kabIdStr === oldKab) ? 'selected' : '';
                kabSelect.innerHTML += `<option value="${kab.id}" ${isSelected}>${kab.nama}</option>`;
            });

            if (oldKab) {
                kabSelect.value = oldKab;
            }
            
            if (kabSelect.value !== "") {
                await fetchKecamatan();
            }
        } catch (error) {
            console.error("Gagal memuat data kabupaten:", error);
        }
    }

    async function fetchKecamatan() {
        const kabId = document.getElementById('kabupaten').value;
        const kecSelect = document.getElementById('kecamatan');

        kecSelect.innerHTML = '<option value="">-- Pilih Kecamatan --</option>';
        kecSelect.disabled = true;

        if (!kabId) return;

        try {
            const res = await fetch(`get_wilayah.php?type=kecamatan&kabupaten_id=${kabId}`);
            const data = await res.json();

            if (!data || data.length === 0) return;

            kecSelect.disabled = false;
            
            data.forEach(kec => {
                const kecIdStr = String(kec.id).trim();
                const isSelected = (kecIdStr === oldKec) ? 'selected' : '';
                kecSelect.innerHTML += `<option value="${kec.id}" ${isSelected}>${kec.nama}</option>`;
            });

            if (oldKec) {
                kecSelect.value = oldKec;
            }
        } catch (error) {
            console.error("Gagal memuat data kecamatan:", error);
        }
    }

    window.onload = async function() {
        const provSelect = document.getElementById('provinsi');
        if(provSelect && provSelect.value !== "") {
            await fetchKabupaten();
        }
    }
</script>

</body>
</html>