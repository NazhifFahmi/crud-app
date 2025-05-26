<?php
require_once 'config/database.php';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['create'])) {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone']]);
        $message = "User created successfully!";
    }
    
    if (isset($_POST['update'])) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['id']]);
        $message = "User updated successfully!";
    }
    
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $message = "User deleted successfully!";
    }
}

// Get edit user data
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch();
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRUD Application - PHP MySQL Docker</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">CRUD Application</h1>
        <p class="text-center text-muted">PHP + MySQL + Docker on Google Compute Engine</p>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <!-- Create/Update Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h3><?= $editUser ? 'Edit User' : 'Add New User' ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editUser): ?>
                        <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required 
                               value="<?= $editUser['name'] ?? '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required 
                               value="<?= $editUser['email'] ?? '' ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" 
                               value="<?= $editUser['phone'] ?? '' ?>">
                    </div>
                    
                    <button type="submit" name="<?= $editUser ? 'update' : 'create' ?>" 
                            class="btn btn-<?= $editUser ? 'warning' : 'primary' ?>">
                        <?= $editUser ? 'Update User' : 'Create User' ?>
                    </button>
                    
                    <?php if ($editUser): ?>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h3>Users List</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <a href="?edit=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="http://<?= $_SERVER['HTTP_HOST'] ?>:8081" target="_blank" class="btn btn-info">
                Open phpMyAdmin
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>