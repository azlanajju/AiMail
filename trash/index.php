<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class TrashEmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchTrashEmails() {
        $endpoint = 'https://graph.microsoft.com/v1.0/me/mailFolders/deletedItems/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'id,subject,bodyPreview,from,receivedDateTime,isDraft',
            '$orderby' => 'receivedDateTime desc'
        ]);

        $url = $endpoint . '?' . $params;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['value'] ?? ['error' => 'No deleted emails found'];
    }

    public function permanentlyDeleteEmail($emailId) {
        $endpoint = "https://graph.microsoft.com/v1.0/me/messages/{$emailId}";
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 204;
    }

    public function restoreEmail($emailId) {
        $endpoint = "https://graph.microsoft.com/v1.0/me/messages/{$emailId}/move";
        
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'destinationId' => 'inbox'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 201;
    }
}

$emailViewer = new TrashEmailViewer($_SESSION['access_token']);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && isset($_POST['emailId'])) {
        switch ($_POST['action']) {
            case 'delete':
                $success = $emailViewer->permanentlyDeleteEmail($_POST['emailId']);
                echo json_encode(['success' => $success]);
                break;
            case 'restore':
                $success = $emailViewer->restoreEmail($_POST['emailId']);
                echo json_encode(['success' => $success]);
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
        }
    } else {
        echo json_encode(['error' => 'Missing parameters']);
    }
    exit;
}

$emails = $emailViewer->fetchTrashEmails();
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Trash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./trash.css">
    <style>

    </style>
</head>
<body>
<?php $activePage="trash"; $path="../"; include '../includes/sidebar.php'; ?>
<?php $path="../"; include '../includes/topbar.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-trash"></i> Trash</h1>
                <button class="empty-trash-btn" onclick="emptyTrash()">
                    <i class="fas fa-trash-alt"></i> Empty Trash
                </button>
            </div>

            <div class="warning-banner">
                <i class="fas fa-exclamation-triangle"></i>
                Items in trash will be automatically deleted after 30 days
            </div>

            <div class="email-list">
                <?php
                if (isset($emails['error'])) {
                    echo '<div class="error-message">';
                    echo '<i class="fas fa-exclamation-circle"></i> ';
                    echo htmlspecialchars($emails['error']);
                    echo '</div>';
                } else if (empty($emails)) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-trash"></i> Trash is empty';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        echo '<div class="email-item" data-email-id="' . htmlspecialchars($email['id']) . '">';
                        echo '<div class="email-header">';
                        echo '<span class="email-from">' . htmlspecialchars($email['from']['emailAddress']['address']) . '</span>';
                        echo '<div class="email-actions">';
                        echo '<span class="restore-btn" title="Restore"><i class="fas fa-undo"></i></span>';
                        echo '<span class="delete-btn" title="Delete Permanently"><i class="fas fa-trash"></i></span>';
                        echo '<span class="email-date">' . $date->format('M j, g:i A') . '</span>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="email-subject">' . htmlspecialchars($email['subject']) . '</div>';
                        echo '<div class="email-preview">' . htmlspecialchars($email['bodyPreview']) . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle restore button clicks
            document.querySelectorAll('.restore-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const emailItem = this.closest('.email-item');
                    const emailId = emailItem.dataset.emailId;

                    if (confirm('Restore this email to inbox?')) {
                        handleEmailAction(emailId, 'restore', emailItem);
                    }
                });
            });

            // Handle delete button clicks
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const emailItem = this.closest('.email-item');
                    const emailId = emailItem.dataset.emailId;

                    if (confirm('Permanently delete this email? This cannot be undone.')) {
                        handleEmailAction(emailId, 'delete', emailItem);
                    }
                });
            });
        });

        async function handleEmailAction(emailId, action, emailItem) {
            try {
                const response = await fetch('../endpoints/trash_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: action,
                        emailId: emailId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    emailItem.remove();
                    // Check if trash is empty after removal
                    if (document.querySelectorAll('.email-item').length === 0) {
                        location.reload();
                    }
                } else {
                    throw new Error(data.error || `Failed to ${action} email`);
                }
            } catch (error) {
                console.error('Error:', error);
                alert(`Error ${action}ing email: ${error.message}`);
            }
        }

        function emptyTrash() {
            if (!confirm('Permanently delete all items in trash? This cannot be undone.')) {
                return;
            }

            const emailItems = document.querySelectorAll('.email-item');
            let processed = 0;
            let errors = [];

            emailItems.forEach(async (item) => {
                try {
                    const emailId = item.dataset.emailId;
                    const response = await fetch('../endpoints/trash_actions.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            emailId: emailId
                        })
                    });

                    const data = await response.json();
                    processed++;

                    if (data.success) {
                        item.remove();
                    } else {
                        errors.push(`Failed to delete email ${emailId}`);
                    }

                    // Check if all items have been processed
                    if (processed === emailItems.length) {
                        if (errors.length > 0) {
                            alert('Some emails could not be deleted:\n' + errors.join('\n'));
                        }
                        location.reload();
                    }
                } catch (error) {
                    console.error('Error:', error);
                    errors.push(`Error deleting email: ${error.message}`);
                    processed++;
                }
            });
        }
    </script>
</body>
</html> 