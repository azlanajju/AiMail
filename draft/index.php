<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: ../index.php');
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
    <link rel="stylesheet" href="./draft.css">
</head>
<body>
    <?php $activePage="draft"; $path="../"; include '../includes/sidebar.php'; ?>
    
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