<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class SpamEmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchSpamEmails() {
        $endpoint = 'https://graph.microsoft.com/v1.0/me/mailFolders/JunkEmail/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'id,subject,bodyPreview,from,receivedDateTime,body',
            '$orderby' => 'receivedDateTime desc'
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
        return $data['value'] ?? ['error' => 'No spam emails found'];
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

    public function moveToInbox($emailId) {
        $endpoint = "https://graph.microsoft.com/v1.0/me/messages/{$emailId}/move";
        $data = [
            'destinationId' => 'inbox'
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

$emailViewer = new SpamEmailViewer($_SESSION['access_token']);

// Handle actions
if (isset($_POST['action']) && isset($_POST['emailId'])) {
    $response = ['success' => false];
    
    switch ($_POST['action']) {
        case 'delete':
            $response['success'] = $emailViewer->deleteEmail($_POST['emailId']);
            break;
        case 'moveToInbox':
            $response['success'] = $emailViewer->moveToInbox($_POST['emailId']);
            break;
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$emails = $emailViewer->fetchSpamEmails();
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Spam</title>
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

        .warning-banner {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
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

        .email-item:last-child {
            border-bottom: none;
        }

        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .email-from {
            font-weight: 600;
            color: #333;
            flex: 1;
        }

        .email-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .email-date {
            color: #666;
            font-size: 0.9em;
            min-width: 100px;
            text-align: right;
        }

        .email-subject {
            font-weight: 500;
            color: #1a73e8;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .email-preview {
            color: #666;
            font-size: 0.9em;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .not-spam-btn {
            color: #28a745;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .not-spam-btn:hover {
            background: #e8f5e9;
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
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .close-btn:hover {
            background-color: #f0f0f0;
        }

        #modalSubject {
            font-size: 20px;
            color: #333;
            margin-bottom: 15px;
            padding-right: 40px;
        }

        #modalFrom,
        #modalDate {
            color: #666;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .email-body {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            line-height: 1.6;
            color: #333;
        }

        .no-emails {
            text-align: center;
            padding: 40px;
            color: #666;
            font-size: 15px;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile {
            display: flex;
            gap: 15px;
            color: #666;
        }

        .user-profile i {
            font-size: 18px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .user-profile i:hover {
            color: #1a73e8;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <div class="search-bar">
                    <input type="text" placeholder="Search in spam">
                </div>
                <div class="user-profile">
                    <i class="fas fa-cog"></i>
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>

            <div class="warning-banner">
                <i class="fas fa-exclamation-triangle"></i>
                Messages in this folder were identified as spam. Be careful when opening links or attachments.
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
                    echo '<i class="fas fa-check-circle"></i> No spam emails found';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        echo '<div class="email-item" data-email-id="' . htmlspecialchars($email['id']) . '">';
                        echo '<div class="email-header">';
                        echo '<span class="email-from">' . htmlspecialchars($email['from']['emailAddress']['address']) . '</span>';
                        echo '<div class="email-actions">';
                        echo '<span class="not-spam-btn" title="Not Spam"><i class="fas fa-check"></i></span>';
                        echo '<span class="delete-btn" title="Delete"><i class="fas fa-trash"></i></span>';
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

    <!-- Email Detail Modal -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modalSubject"></h2>
            <div id="modalFrom"></div>
            <div id="modalDate"></div>
            <div id="modalBody" class="email-body"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('emailModal');
            const closeBtn = document.querySelector('.close-btn');
            
            // Modal close handlers
            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }

            closeBtn.onclick = function() {
                modal.style.display = "none";
            }

            // Email item click handler
            document.querySelectorAll('.email-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (e.target.closest('.delete-btn') || e.target.closest('.not-spam-btn')) {
                        return;
                    }

                    const emailId = this.dataset.emailId;
                    const subject = this.querySelector('.email-subject').textContent;
                    const from = this.querySelector('.email-from').textContent;
                    const date = this.querySelector('.email-date').textContent;
                    const preview = this.querySelector('.email-preview').textContent;

                    document.getElementById('modalSubject').textContent = subject;
                    document.getElementById('modalFrom').textContent = 'From: ' + from;
                    document.getElementById('modalDate').textContent = 'Date: ' + date;
                    document.getElementById('modalBody').textContent = preview;

                    modal.style.display = "block";
                });
            });

            // Delete button handler
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to delete this email?')) {
                        const emailItem = this.closest('.email-item');
                        const emailId = emailItem.dataset.emailId;

                        fetch('spam.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: 'action=delete&emailId=' + encodeURIComponent(emailId)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                emailItem.remove();
                            } else {
                                alert('Failed to delete email');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error deleting email');
                        });
                    }
                });
            });

            // Not spam button handler
            document.querySelectorAll('.not-spam-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const emailItem = this.closest('.email-item');
                    const emailId = emailItem.dataset.emailId;

                    fetch('spam.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=moveToInbox&emailId=' + encodeURIComponent(emailId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            emailItem.remove();
                        } else {
                            alert('Failed to move email to inbox');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error moving email to inbox');
                    });
                });
            });
        });
    </script>
</body>
</html> 