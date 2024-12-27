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
    <style>
* {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
        }

        .container {
            margin-left: 250px;
            padding: 20px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .email-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .email-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .email-item:hover {
            background-color: #f8f9fa;
        }

        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .email-to {
            color: #666;
            font-size: 14px;
        }

        .email-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .delete-btn:hover {
            background-color: #fff1f1;
        }

        .email-date {
            color: #666;
            font-size: 12px;
        }

        .email-subject {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .email-preview {
            color: #666;
            font-size: 14px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .search-bar input {
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 300px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            position: relative;
            background: white;
            margin: 50px auto;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        .restore-btn {
            color: #28a745;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .restore-btn:hover {
            background-color: #f0fff4;
        }

        .warning-banner {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .empty-trash-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .empty-trash-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

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

        function handleEmailAction(emailId, action, emailItem) {
            fetch('trash.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&emailId=${encodeURIComponent(emailId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    emailItem.remove();
                    if (document.querySelectorAll('.email-item').length === 0) {
                        location.reload(); // Refresh to show empty state
                    }
                } else {
                    alert(`Failed to ${action} email`);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(`Error ${action}ing email`);
            });
        }

        function emptyTrash() {
            if (confirm('Permanently delete all items in trash? This cannot be undone.')) {
                const emailItems = document.querySelectorAll('.email-item');
                let processed = 0;
                
                emailItems.forEach(item => {
                    const emailId = item.dataset.emailId;
                    handleEmailAction(emailId, 'delete', item);
                    processed++;
                    
                    if (processed === emailItems.length) {
                        location.reload();
                    }
                });
            }
        }
    </script>
</body>
</html> 