<?php
session_start();
require_once 'config/db.config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !isAdmin($pdo, $_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle user suspension
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = $_POST['user_id'];
    $suspended = isset($_POST['suspended']) ? 1 : 0;
    suspendUser($pdo, $userId, $suspended);
}

// Get all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Get all documents
$stmt = $pdo->query("
    SELECT d.*, u.username as owner_name
    FROM documents d
    JOIN users u ON d.owner_id = u.id
    ORDER BY d.updated_at DESC
");
$documents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Google Docs Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        <div class="admin-panel">
            <h2>Admin Panel</h2>
            
            <div class="admin-section">
                <h3>Users</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                <td>
                                    <?php echo $user['is_suspended'] ? 'Suspended' : 'Active'; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form method="POST" class="suspend-form">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <label>
                                            <input type="checkbox" name="suspended" 
                                                <?php echo $user['is_suspended'] ? 'checked' : ''; ?>
                                                onchange="this.form.submit()">
                                            Suspend
                                        </label>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="admin-section">
                <h3>Documents</h3>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Owner</th>
                            <th>Created</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td><?php echo htmlspecialchars($doc['owner_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($doc['created_at'])); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($doc['updated_at'])); ?></td>
                                <td>
                                    <a href="document.php?id=<?php echo $doc['id']; ?>" class="button">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here
    </script>
</body>
</html> 