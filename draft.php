<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class DraftEmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchDraftEmails() {
        $endpoint = 'https://graph.microsoft.com/v1.0/me/mailFolders/drafts/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'id,subject,bodyPreview,toRecipients,createdDateTime,body',
            '$orderby' => 'createdDateTime desc'
        ]);

        $url = $endpoint . '?' . $params;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['value'] ?? ['error' => 'No draft emails found'];
    }

    public function deleteEmail($emailId) {
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
}

$emailViewer = new DraftEmailViewer($_SESSION['access_token']);

// Handle delete request
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['emailId'])) {
    $success = $emailViewer->deleteEmail($_POST['emailId']);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

$drafts = $emailViewer->fetchDraftEmails();
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Drafts</title>
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
            min-height: 100vh;
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

        .page-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .draft-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .draft-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .draft-item:hover {
            background-color: #f8f9fa;
        }

        .draft-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .draft-to {
            font-weight: 600;
            color: #333;
            flex: 1;
        }

        .draft-date {
            color: #666;
            font-size: 0.9em;
        }

        .draft-subject {
            font-weight: 500;
            color: #1a73e8;
            margin-bottom: 8px;
        }

        .draft-preview {
            color: #666;
            font-size: 0.9em;
            line-height: 1.5;
        }

        .draft-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .edit-btn {
            color: #1a73e8;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .edit-btn:hover {
            background: #f1f7fe;
        }

        .delete-btn {
            color: #dc3545;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .delete-btn:hover {
            background: #fee;
        }

        .no-drafts {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .search-bar input {
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 300px;
            font-size: 14px;
        }

        .search-bar input:focus {
            outline: none;
            border-color: #1a73e8;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <div class="search-bar">
                    <input type="text" placeholder="Search in drafts">
                </div>
                <div class="user-profile">
                    <i class="fas fa-cog"></i>
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>

            <h1 class="page-title">
                <i class="fas fa-file-alt"></i> Drafts
            </h1>

            <div class="draft-list">
                <?php
                if (isset($drafts['error'])) {
                    echo '<div class="error-message">';
                    echo '<i class="fas fa-exclamation-circle"></i> ';
                    echo htmlspecialchars($drafts['error']);
                    echo '</div>';
                } else if (empty($drafts)) {
                    echo '<div class="no-drafts">';
                    echo '<i class="fas fa-file-alt"></i> No draft emails';
                    echo '</div>';
                } else {
                    foreach ($drafts as $draft) {
                        $date = new DateTime($draft['createdDateTime']);
                        $recipients = array_map(function($recipient) {
                            return $recipient['emailAddress']['address'];
                        }, $draft['toRecipients'] ?? []);

                        echo '<div class="draft-item" data-draft-id="' . htmlspecialchars($draft['id']) . '">';
                        echo '<div class="draft-header">';
                        echo '<span class="draft-to">To: ' . htmlspecialchars(implode(', ', $recipients)) . '</span>';
                        echo '<div class="draft-actions">';
                        echo '<span class="edit-btn" title="Edit"><i class="fas fa-edit"></i></span>';
                        echo '<span class="delete-btn" title="Delete"><i class="fas fa-trash"></i></span>';
                        echo '<span class="draft-date">' . $date->format('M j, g:i A') . '</span>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="draft-subject">' . htmlspecialchars($draft['subject'] ?? 'No subject') . '</div>';
                        echo '<div class="draft-preview">' . htmlspecialchars($draft['bodyPreview'] ?? '') . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle edit button clicks
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const draftId = this.closest('.draft-item').dataset.draftId;
                    window.location.href = 'compose.php?draft=' + encodeURIComponent(draftId);
                });
            });

            // Handle delete button clicks
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to delete this draft?')) {
                        const draftItem = this.closest('.draft-item');
                        const draftId = draftItem.dataset.draftId;

                        fetch('draft.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=delete&emailId=' + encodeURIComponent(draftId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                draftItem.remove();
                            } else {
                                alert('Failed to delete draft');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error deleting draft');
                        });
                    }
                });
            });

            // Handle draft item clicks (open in compose)
            document.querySelectorAll('.draft-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.closest('.edit-btn') && !e.target.closest('.delete-btn')) {
                        const draftId = this.dataset.draftId;
                        window.location.href = 'compose.php?draft=' + encodeURIComponent(draftId);
                    }
                });
            });
        });
    </script>
</body>
</html>