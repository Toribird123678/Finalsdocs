<?php
session_start();
require_once '../config/db.config.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['query'])) {
    http_response_code(400);
    exit('Invalid request');
}

$query = $_GET['query'];
$users = searchUsers($pdo, $query);

$html = '';
foreach ($users as $user) {
    $html .= sprintf(
        '<div class="search-result" data-user-id="%d">
            <span class="username">%s</span>
            <span class="email">%s</span>
            <button class="add-collaborator" data-user-id="%d">Add</button>
        </div>',
        $user['id'],
        htmlspecialchars($user['username']),
        htmlspecialchars($user['email']),
        $user['id']
    );
}

echo $html;
?> 