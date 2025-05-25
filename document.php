<?php
session_start();
require_once 'config/db.config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$documentId = $_GET['id'];
$userId = $_SESSION['user_id'];

// Get document details
$stmt = $pdo->prepare("
    SELECT d.*, u.username as owner_name
    FROM documents d
    JOIN users u ON d.owner_id = u.id
    WHERE d.id = ?
");
$stmt->execute([$documentId]);
$document = $stmt->fetch();

if (!$document) {
    header('Location: index.php');
    exit();
}

// Check if user has access to the document
$stmt = $pdo->prepare("
    SELECT 1 FROM documents d
    LEFT JOIN document_permissions dp ON d.id = dp.document_id AND dp.user_id = ?
    WHERE d.id = ? AND (d.owner_id = ? OR dp.user_id = ?)
");
$stmt->execute([$userId, $documentId, $userId, $userId]);
if ($stmt->rowCount() === 0) {
    header('Location: index.php');
    exit();
}

$canEdit = canEditDocument($pdo, $userId, $documentId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($document['title']); ?> - Google Docs Clone</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.tiny.cloud/1/qagffr3pkuv17a8on1afax661irst1hbr4e6tbv888sz91jc/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Google Docs Clone</div>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="document-container">
        <div class="document-header">
            <h1><?php echo htmlspecialchars($document['title']); ?></h1>
            <div class="document-info">
                <p>Owner: <?php echo htmlspecialchars($document['owner_name']); ?></p>
                <p>Last updated: <?php echo date('M d, Y H:i', strtotime($document['updated_at'])); ?></p>
                <p id="save-status" class="save-status">All changes saved</p>
            </div>
        </div>

        <div class="editor-container">
            <textarea id="editor" <?php echo $canEdit ? '' : 'readonly'; ?>>
                <?php echo htmlspecialchars($document['content']); ?>
            </textarea>
        </div>

        <div class="sidebar">
            <div class="collaborators-panel">
                <h3>Collaborators</h3>
                <div class="search-box">
                    <input type="text" id="user-search" placeholder="Search users...">
                    <div id="search-results"></div>
                </div>
                <div class="collaborators-list">
                    <?php
                    $collaborators = getDocumentCollaborators($pdo, $documentId);
                    foreach ($collaborators as $collaborator): ?>
                        <div class="collaborator">
                            <span><?php echo htmlspecialchars($collaborator['username']); ?></span>
                            <?php if ($document['owner_id'] === $userId): ?>
                                <button class="remove-collaborator" data-user-id="<?php echo $collaborator['id']; ?>">
                                    Remove
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="activity-panel">
                <h3>Activity Log</h3>
                <div class="activity-list">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT al.*, u.username
                        FROM activity_logs al
                        JOIN users u ON al.user_id = u.id
                        WHERE al.document_id = ?
                        ORDER BY al.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$documentId]);
                    $activities = $stmt->fetchAll();
                    foreach ($activities as $activity): ?>
                        <div class="activity-item">
                            <p>
                                <strong><?php echo htmlspecialchars($activity['username']); ?></strong>
                                <?php echo htmlspecialchars($activity['action']); ?>
                                <small><?php echo date('M d, H:i', strtotime($activity['created_at'])); ?></small>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chat-panel">
                <h3>Chat</h3>
                <div class="chat-messages">
                    <?php
                    $messages = getDocumentMessages($pdo, $documentId);
                    foreach ($messages as $message): ?>
                        <div class="message">
                            <strong><?php echo htmlspecialchars($message['username']); ?></strong>
                            <p><?php echo htmlspecialchars($message['message']); ?></p>
                            <small><?php echo date('M d, H:i', strtotime($message['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-input">
                    <input type="text" id="message-input" placeholder="Type a message...">
                    <button id="send-message">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        tinymce.init({
            selector: '#editor',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount',
                'emoticons template wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help | fontselect fontsizeselect | ' +
                'forecolor backcolor | table | link image | ' +
                'emoticons | fullscreen',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
            font_formats: 'Andale Mono=andale mono,times; Arial=arial,helvetica,sans-serif; Arial Black=arial black,avant garde; Book Antiqua=book antiqua,palatino; Comic Sans MS=comic sans ms,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Symbol=symbol; Tahoma=tahoma,arial,helvetica,sans-serif; Terminal=terminal,monaco; Times New Roman=times new roman,times; Verdana=verdana,geneva; Webdings=webdings; Wingdings=wingdings,zapf dingbats',
            fontsize_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
            readonly: <?php echo $canEdit ? 'false' : 'true'; ?>,
            setup: function(editor) {
                editor.on('change', function() {
                    updateSaveStatus('Saving...');
                    saveDocument();
                });
            }
        });

        function updateSaveStatus(status) {
            const saveStatus = document.getElementById('save-status');
            saveStatus.textContent = status;
            if (status === 'Saving...') {
                saveStatus.style.color = '#f0ad4e';
            } else if (status === 'All changes saved') {
                saveStatus.style.color = '#5cb85c';
            } else {
                saveStatus.style.color = '#d9534f';
            }
        }

        function saveDocument() {
            const content = tinymce.get('editor').getContent();
            $.post('ajax/save_document.php', {
                document_id: <?php echo $documentId; ?>,
                content: content
            })
            .done(function() {
                updateSaveStatus('All changes saved');
            })
            .fail(function() {
                updateSaveStatus('Failed to save');
            });
        }

        // Auto-save every 30 seconds
        setInterval(function() {
            if (tinymce.get('editor').isDirty()) {
                updateSaveStatus('Auto-saving...');
                saveDocument();
            }
        }, 30000);

        // User search functionality
        $('#user-search').on('input', function() {
            const query = $(this).val();
            if (query.length > 2) {
                $.get('ajax/search_users.php', { query: query }, function(data) {
                    $('#search-results').html(data);
                });
            } else {
                $('#search-results').empty();
            }
        });

        // Add collaborator functionality
        $(document).on('click', '.add-collaborator', function() {
            const userId = $(this).data('user-id');
            $.post('ajax/add_collaborator.php', {
                document_id: <?php echo $documentId; ?>,
                user_id: userId
            })
            .done(function(response) {
                if (response.success) {
                    // Refresh the collaborators list
                    location.reload();
                } else {
                    alert('Failed to add collaborator: ' + (response.error || 'Unknown error'));
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                alert('Failed to add collaborator: ' + (jqXHR.responseJSON?.error || errorThrown || 'Unknown error'));
            });
        });

        // Remove collaborator functionality
        $(document).on('click', '.remove-collaborator', function() {
            const userId = $(this).data('user-id');
            $.post('ajax/remove_collaborator.php', {
                document_id: <?php echo $documentId; ?>,
                user_id: userId
            })
            .done(function(response) {
                if (response.success) {
                    // Refresh the collaborators list
                    location.reload();
                } else {
                    alert('Failed to remove collaborator: ' + (response.error || 'Unknown error'));
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                alert('Failed to remove collaborator: ' + (jqXHR.responseJSON?.error || errorThrown || 'Unknown error'));
            });
        });

        // Send message functionality
        $('#send-message').click(function() {
            const message = $('#message-input').val();
            if (message.trim()) {
                $.post('ajax/send_message.php', {
                    document_id: <?php echo $documentId; ?>,
                    message: message
                }, function() {
                    $('#message-input').val('');
                    loadMessages();
                });
            }
        });

        function loadMessages() {
            $.get('ajax/get_messages.php', {
                document_id: <?php echo $documentId; ?>
            }, function(data) {
                $('.chat-messages').html(data);
            });
        }

        // Refresh messages every 5 seconds
        setInterval(loadMessages, 5000);
    </script>
</body>
</html> 