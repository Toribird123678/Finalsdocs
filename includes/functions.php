<?php
// Function to check if user has permission to edit a document
function canEditDocument($pdo, $userId, $documentId) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM documents d
        LEFT JOIN document_permissions dp ON d.id = dp.document_id AND dp.user_id = ?
        WHERE d.id = ? AND (d.owner_id = ? OR dp.can_edit = 1)
    ");
    $stmt->execute([$userId, $documentId, $userId]);
    return $stmt->rowCount() > 0;
}

// Function to log activity
function logActivity($pdo, $documentId, $userId, $action, $details = '') {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (document_id, user_id, action, details)
        VALUES (?, ?, ?, ?)
    ");
    return $stmt->execute([$documentId, $userId, $action, $details]);
}

// Function to get document collaborators
function getDocumentCollaborators($pdo, $documentId) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, dp.can_edit
        FROM users u
        JOIN document_permissions dp ON u.id = dp.user_id
        WHERE dp.document_id = ?
    ");
    $stmt->execute([$documentId]);
    return $stmt->fetchAll();
}

// Function to search users
function searchUsers($pdo, $query) {
    $stmt = $pdo->prepare("
        SELECT id, username, email
        FROM users
        WHERE username LIKE ? OR email LIKE ?
        LIMIT 10
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm]);
    return $stmt->fetchAll();
}

// Function to add collaborator to document
function addCollaborator($pdo, $documentId, $userId, $canEdit = false) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO document_permissions (document_id, user_id, can_edit)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE can_edit = ?
        ");
        return $stmt->execute([$documentId, $userId, $canEdit, $canEdit]);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to remove collaborator from document
function removeCollaborator($pdo, $documentId, $userId) {
    $stmt = $pdo->prepare("
        DELETE FROM document_permissions
        WHERE document_id = ? AND user_id = ?
    ");
    return $stmt->execute([$documentId, $userId]);
}

// Function to get document messages
function getDocumentMessages($pdo, $documentId, $limit = 50) {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.document_id = ?
        ORDER BY m.created_at DESC
        LIMIT " . (int)$limit
    );
    $stmt->execute([$documentId]);
    return $stmt->fetchAll();
}

// Function to add message to document
function addMessage($pdo, $documentId, $userId, $message) {
    $stmt = $pdo->prepare("
        INSERT INTO messages (document_id, user_id, message)
        VALUES (?, ?, ?)
    ");
    return $stmt->execute([$documentId, $userId, $message]);
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Function to validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to check if user is admin
function isAdmin($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return $user && $user['role'] === 'admin';
}

// Function to suspend user
function suspendUser($pdo, $userId, $suspended = true) {
    $stmt = $pdo->prepare("UPDATE users SET is_suspended = ? WHERE id = ?");
    return $stmt->execute([$suspended, $userId]);
}
?> 