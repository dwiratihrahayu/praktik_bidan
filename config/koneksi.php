<?php
// Koneksi Database dengan dukungan Environment Variable (Cloud/Vercel) & Fallback Localhost (XAMPP)
$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : "";
$db   = getenv('DB_NAME') ?: "praktik_bidan";
$port = (int)(getenv('DB_PORT') ?: 3306);

$koneksi = mysqli_connect($host, $user, $pass, $db, $port);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>
