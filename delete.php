<?php
include 'db_connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    $sql = "DELETE FROM produk WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    header("Location: index.php");
    exit;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>