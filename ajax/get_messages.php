<?php
session_start();
require_once '../config/db.config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['document_id'])) {
    http_response_code(400);
    exit('Invalid request');
}

$documentId = $_GET['document_id'];
$userId = $_SESSION['user_id'];

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

$messages = getDocumentMessages($pdo, $documentId);

$html = '';
foreach ($messages as $message) {
    $html .= sprintf(
        '<div class="message">
            <strong>%s</strong>
            <p>%s</p>
            <small>%s</small>
        </div>',
        htmlspecialchars($message['username']),
        htmlspecialchars($message['message']),
        date('M d, H:i', strtotime($message['created_at']))
    );
}

echo $html;
?> 