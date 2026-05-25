<?php
$host     = 'db-japak-nusantara-rezimeizafani-da4d.h.aivencloud.com';
$port     = 20147;
$user     = 'avnadmin';
$password = 'AVNS_vLGuGHJtZ4ES58bQ0_D';
$dbname   = 'defaultdb';

// Inisialisasi MySQLi
$db = mysqli_init();

if (!$db) {
    die("MySQLi initialization failed");
}

// Konfigurasi SSL menggunakan CA Certificate yang diunduh dari Aiven
$db->ssl_set(NULL, NULL, __DIR__ . '/../certs/ca.pem', NULL, NULL);

// Melakukan koneksi secara real-time dengan enkripsi SSL
$db->real_connect($host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if ($db->connect_error) {
    die("Koneksi Database Gagal: " . $db->connect_error);
}
?>