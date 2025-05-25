<?php
session_start();
require_once 'config/db.config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($title)) {
        $error = 'Please enter a document title';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO documents (title, content, owner_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$title, $content, $_SESSION['user_id']]);
            $documentId = $pdo->lastInsertId();

            // Log the activity
            logActivity($pdo, $documentId, $_SESSION['user_id'], 'created the document');

            header("Location: document.php?id=$documentId");
            exit();
        } catch (PDOException $e) {
            $error = 'Failed to create document. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Document - Google Docs Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">Google Docs Clone</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="new-document-form">
            <h2>Create New Document</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Document Title:</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="content">Initial Content:</label>
                    <textarea id="content" name="content" rows="10"></textarea>
                </div>
                <button type="submit">Create Document</button>
            </form>
        </div>
    </div>
</body>
</html> 