<?php
require_once 'config/db.config.php';

// Admin user details
$email = 'admin@example.com';
$new_password = 'admin123';

// Hash the new password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update admin password
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password = ? 
        WHERE email = ? AND role = 'admin'
    ");
    $stmt->execute([$hashed_password, $email]);
    
    if ($stmt->rowCount() > 0) {
        echo "Admin password has been reset successfully!<br>";
        echo "Email: " . $email . "<br>";
        echo "New Password: " . $new_password . "<br>";
    } else {
        echo "Admin user not found!";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 