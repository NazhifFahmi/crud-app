<?php
include 'db_connect.php';

$id = $_GET['id'] ?? null;
$message = '';
$produk = null;

if (!$id) {
    header("Location: index.php");
    exit;
}

// Ambil data produk yang akan diedit
try {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $produk = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produk) {
        die("Produk tidak ditemukan.");
    }
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = $_POST['nama_produk'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];

    if (!empty($nama_produk) && !empty($harga)) {
        try {
            $sql = "UPDATE produk SET nama_produk = :nama_produk, deskripsi = :deskripsi, harga = :harga WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nama_produk' => $nama_produk,
                ':deskripsi' => $deskripsi,
                ':harga' => $harga,
                ':id' => $id
            ]);
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } else {
        $message = "Nama produk dan harga wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Produk</title>
     <style>
        body { font-family: sans-serif; margin: 20px; }
        form { width: 50%; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 5px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; }
        input[type="text"], input[type="number"], textarea { width: calc(100% - 22px); padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        textarea { resize: vertical; height: 80px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .back-link { display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <h1>Edit Produk: <?php echo htmlspecialchars($produk['nama_produk']); ?></h1>
    <?php if (!empty($message)): ?>
        <p class="message <?php echo (strpos($message, "Error") !== false || strpos($message, "wajib") !== false) ? 'error' : ''; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?php echo $id; ?>">
        <label for="nama_produk">Nama Produk:</label>
        <input type="text" id="nama_produk" name="nama_produk" value="<?php echo htmlspecialchars($produk['nama_produk']); ?>" required>

        <label for="deskripsi">Deskripsi:</label>
        <textarea id="deskripsi" name="deskripsi"><?php echo htmlspecialchars($produk['deskripsi']); ?></textarea>

        <label for="harga">Harga:</label>
        <input type="number" id="harga" name="harga" step="0.01" value="<?php echo htmlspecialchars($produk['harga']); ?>" required>

        <input type="submit" value="Update Produk">
        <a href="index.php" class="back-link">Kembali ke Daftar Produk</a>
    </form>
</body>
</html>