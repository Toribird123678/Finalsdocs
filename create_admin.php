<?php
require_once 'config/db.config.php';

// Admin user details
$username = 'admin';
$email = 'admin@example.com';
$password = 'admin123';
$role = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    // Check if admin user already exists
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? OR username = ?");
    $stmt->execute([$email, $username]);
    
    if ($stmt->rowCount() > 0) {
        echo "Admin user already exists!";
    } else {
        // Create new admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, role)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$username, $email, $hashed_password, $role]);
        
        echo "Admin user created successfully!<br>";
        echo "Username: " . $username . "<br>";
        echo "Email: " . $email . "<br>";
        echo "Password: " . $password . "<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 