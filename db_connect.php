<?php
$host = 'db'; // Nama service MySQL di docker-compose
$db_name = 'db_produk';
$username = 'user_produk';
$password = 'password_produk'; // Ganti dengan password yang kuat
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Koneksi berhasil!"; // Hapus atau komentari setelah tes
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}
?>