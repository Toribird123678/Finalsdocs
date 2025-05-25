<?php
session_start();
require_once 'config/db.config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !in_array($_SERVER['REQUEST_URI'], ['/login.php', '/register.php'])) {
    header('Location: login.php');
    exit();
}

// Get current user data if logged in
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Docs Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/5/tinymce.min.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Google Docs Clone</div>
        <div class="nav-links">
            <?php if ($current_user): ?>
                <a href="index.php">Home</a>
                <a href="new_document.php">New Document</a>
                <?php if ($current_user['role'] === 'admin'): ?>
                    <a href="admin.php">Admin Panel</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <?php if ($current_user): ?>
            <div class="documents-list">
                <h2>My Documents</h2>
                <?php
                $stmt = $pdo->prepare("
                    SELECT d.*, u.username as owner_name 
                    FROM documents d 
                    JOIN users u ON d.owner_id = u.id 
                    WHERE d.owner_id = ? 
                    OR d.id IN (SELECT document_id FROM document_permissions WHERE user_id = ?)
                    ORDER BY d.updated_at DESC
                ");
                $stmt->execute([$current_user['id'], $current_user['id']]);
                $documents = $stmt->fetchAll();

                foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <a href="document.php?id=<?php echo $doc['id']; ?>">
                            <h3><?php echo htmlspecialchars($doc['title']); ?></h3>
                            <p>Owner: <?php echo htmlspecialchars($doc['owner_name']); ?></p>
                            <p>Last updated: <?php echo date('M d, Y H:i', strtotime($doc['updated_at'])); ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html> 