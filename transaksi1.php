<?php
include 'config.php';

$keranjang = isset($_SESSION['keranjang'])
? $_SESSION['keranjang']
: [];

$total = 0;

if(isset($_POST['checkout'])){

    $_SESSION['success'] = true;

    header("Location: transaksi1.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Transaksi | Roemah Raga</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>

body{
font-family:'Plus Jakarta Sans',sans-serif;
background:#fafafa;
}

.serif{
font-family:'Playfair Display',serif;
}

</style>

</head>

<body>

<nav class="sticky top-0 z-50 bg-white border-b border-gray-100 px-6 md:px-12 py-4 flex justify-between items-center">

<a href="index1.php"
class="serif text-3xl text-red-900 font-bold">

Roemah Raga

</a>

<a href="keranjang.php"
class="text-red-900 font-bold">

Kembali

</a>

</nav>

<div class="container mx-auto px-4 md:px-12 py-10">

<h1 class="serif text-5xl text-red-900 mb-10">

Transaksi

</h1>

<?php if(isset($_SESSION['success'])): ?>

<div class="bg-green-100 border border-green-300 text-green-700 p-6 rounded-3xl mb-10">

<h2 class="font-bold text-2xl mb-2">
Checkout Berhasil 🎉
</h2>

<p>
Pesanan sedang diproses.
</p>

</div>

<?php
unset($_SESSION['success']);
unset($_SESSION['keranjang']);
?>

<?php endif; ?>

<?php if(empty($keranjang)): ?>

<div class="bg-white rounded-[32px] p-10 shadow text-center">

<p class="text-gray-400 text-lg">
Keranjang masih kosong
</p>

<a href="index1.php"
class="inline-block mt-6 bg-red-900 text-white px-8 py-4 rounded-2xl font-bold">

Belanja Sekarang

</a>

</div>

<?php else: ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

<!-- PRODUK -->
<div class="lg:col-span-2 space-y-6">

<?php foreach($keranjang as $k): ?>

<?php
$subtotal = $k['harga_produk'] * $k['jumlah'];
$total += $subtotal;
?>

<div class="bg-white rounded-[32px] p-6 shadow flex gap-6 items-center">

<img
src="img/<?= $k['foto_produk']; ?>"
class="w-32 h-32 object-cover rounded-2xl">

<div class="flex-1">

<h2 class="font-bold text-2xl text-gray-800">
<?= $k['nama_produk']; ?>
</h2>

<p class="text-gray-400 mt-2">
Ukuran: <?= $k['ukuran']; ?>
</p>

<p class="text-gray-400">
Jumlah: <?= $k['jumlah']; ?>
</p>

<p class="text-red-900 font-bold text-2xl mt-4">
<?= rupiah($subtotal); ?>
</p>

</div>

</div>

<?php endforeach; ?>

</div>

<!-- FORM -->
<div class="bg-red-900 text-white rounded-[40px] p-8 h-fit sticky top-28">

<form method="POST" class="space-y-6">

<div>

<label class="font-bold text-sm uppercase tracking-widest">
Nama Lengkap
</label>

<input
type="text"
required
class="w-full mt-2 p-4 rounded-2xl bg-white/10 border border-white/20 outline-none">

</div>

<div>

<label class="font-bold text-sm uppercase tracking-widest">
Alamat
</label>

<textarea
required
rows="4"
class="w-full mt-2 p-4 rounded-2xl bg-white/10 border border-white/20 outline-none"></textarea>

</div>

<div>

<label class="font-bold text-sm uppercase tracking-widest">
Metode Pembayaran
</label>

<select
class="w-full mt-2 p-4 rounded-2xl bg-red-800 border border-white/20 outline-none">

<option>Transfer Bank</option>
<option>E-Wallet</option>
<option>COD</option>

</select>

</div>

<div class="border-t border-white/20 pt-6">

<div class="flex justify-between items-center mb-6">

<span class="opacity-70">
Total Pembayaran
</span>

<h2 class="text-3xl font-bold">
<?= rupiah($total); ?>
</h2>

</div>

<button
type="submit"
name="checkout"
class="w-full bg-white text-red-900 py-4 rounded-2xl font-bold text-lg hover:scale-105 transition-all">

Checkout Sekarang

</button>

</div>

</form>

</div>

</div>

<?php endif; ?>

</div>

</body>
</html>