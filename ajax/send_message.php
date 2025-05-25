<?php
session_start();
require_once '../config/db.config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['document_id']) || !isset($_POST['message'])) {
    http_response_code(400);
    exit('Invalid request');
}

$documentId = $_POST['document_id'];
$userId = $_SESSION['user_id'];
$message = $_POST['message'];

// Check if user has access to the document
$stmt = $pdo->prepare("
    SELECT 1 FROM documents d
    LEFT JOIN document_permissions dp ON d.id = dp.document_id AND dp.user_id = ?
    WHERE d.id = ? AND (d.owner_id = ? OR dp.user_id = ?)
");
$stmt->execute([$userId, $documentId, $userId, $userId]);
if ($stmt->rowCount() === 0) {
    http_response_code(403);
    exit('Permission denied');
}

try {
    if (addMessage($pdo, $documentId, $userId, $message)) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 