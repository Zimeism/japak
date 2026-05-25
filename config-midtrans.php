<?php
// japak/config-midtrans.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan pustaka Composer termuat dengan benar
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// Set Access Keys murni tanpa ada spasi atau karakter tersembunyi
\Midtrans\Config::$serverKey = 'Mid-server-iulweBOSQLgERwUpG45nYDHD';
\Midtrans\Config::$clientKey = 'Mid-client-N28qQTKy7MspiE8w';

// Pengaturan Environment (Wajib FALSE untuk Akun Sandbox)
\Midtrans\Config::$isProduction = false; 
\Midtrans\Config::$isSanitized  = true;  
\Midtrans\Config::$is3ds        = true;