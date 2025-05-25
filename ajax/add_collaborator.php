<?php
session_start();
require_once '../config/db.config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['document_id']) || !isset($_POST['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

$documentId = $_POST['document_id'];
$userId = $_SESSION['user_id'];
$collaboratorId = $_POST['user_id'];

// Check if the current user is the document owner
$stmt = $pdo->prepare("SELECT owner_id FROM documents WHERE id = ?");
$stmt->execute([$documentId]);
$document = $stmt->fetch();

if (!$document || $document['owner_id'] !== $userId) {
    http_response_code(403);
    echo json_encode(['error' => 'Permission denied']);
    exit();
}

try {
    if (addCollaborator($pdo, $documentId, $collaboratorId, true)) {
        // Log the activity
        logActivity($pdo, $documentId, $userId, 'added a collaborator');
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add collaborator']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 