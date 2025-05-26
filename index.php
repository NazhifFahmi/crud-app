<?php
include 'db_connect.php';

// Ambil semua produk
$stmt = $pdo->query("SELECT id, nama_produk, deskripsi, harga FROM produk ORDER BY id DESC");
$produks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Produk</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { text-decoration: none; color: #007bff; margin-right: 10px;}
        .action-links a { margin-right: 5px; }
        .btn-add { display: inline-block; padding: 10px 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px;}
    </style>
</head>
<body>
    <h1>Daftar Produk</h1>
    <a href="create.php" class="btn-add">Tambah Produk Baru</a>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Produk</th>
                <th>Deskripsi</th>
                <th>Harga</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($produks)): ?>
                <tr>
                    <td colspan="5">Tidak ada produk.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($produks as $produk): ?>
                <tr>
                    <td><?php echo htmlspecialchars($produk['id']); ?></td>
                    <td><?php echo htmlspecialchars($produk['nama_produk']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($produk['deskripsi'])); ?></td>
                    <td>Rp <?php echo number_format($produk['harga'], 2, ',', '.'); ?></td>
                    <td class="action-links">
                        <a href="edit.php?id=<?php echo $produk['id']; ?>">Edit</a>
                        <a href="delete.php?id=<?php echo $produk['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>