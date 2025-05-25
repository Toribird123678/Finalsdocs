<?php
session_start();
require_once '../config/db.config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['document_id']) || !isset($_POST['content'])) {
    http_response_code(400);
    exit('Invalid request');
}

$documentId = $_POST['document_id'];
$userId = $_SESSION['user_id'];
$content = $_POST['content'];

// Check if user has permission to edit
if (!canEditDocument($pdo, $userId, $documentId)) {
    http_response_code(403);
    exit('Permission denied');
}

try {
    // Update document content
    $stmt = $pdo->prepare("UPDATE documents SET content = ? WHERE id = ?");
    $stmt->execute([$content, $documentId]);

    // Log the activity
    logActivity($pdo, $documentId, $userId, 'edited the document');

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 