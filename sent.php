<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class SentEmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchSentEmails() {
        $endpoint = 'https://graph.microsoft.com/v1.0/me/mailFolders/sentitems/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'id,subject,bodyPreview,toRecipients,sentDateTime,body',
            '$orderby' => 'sentDateTime desc'
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
        return $data['value'] ?? ['error' => 'No sent emails found'];
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

$emailViewer = new SentEmailViewer($_SESSION['access_token']);

if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['emailId'])) {
    $success = $emailViewer->deleteEmail($_POST['emailId']);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
    exit;
}

$emails = $emailViewer->fetchSentEmails();
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Sent Items</title>
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-paper-plane"></i> Sent Items</h1>
                <div class="search-bar">
                    <input type="text" placeholder="Search sent emails...">
                </div>
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
                    echo '<i class="fas fa-paper-plane"></i> No sent emails';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['sentDateTime']);
                        $recipients = array_map(function($recipient) {
                            return $recipient['emailAddress']['address'];
                        }, $email['toRecipients']);

                        echo '<div class="email-item" data-email-id="' . htmlspecialchars($email['id']) . '">';
                        echo '<div class="email-header">';
                        echo '<span class="email-to">To: ' . htmlspecialchars(implode(', ', $recipients)) . '</span>';
                        echo '<div class="email-actions">';
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

    <div class="modal" id="emailModal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2 id="modalSubject"></h2>
            <p id="modalTo"></p>
            <p id="modalDate"></p>
            <div class="email-body" id="modalBody"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('emailModal');
            const closeBtn = document.querySelector('.close-btn');

            // Handle email item clicks
            document.querySelectorAll('.email-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.closest('.delete-btn')) {
                        const subject = this.querySelector('.email-subject').textContent;
                        const to = this.querySelector('.email-to').textContent;
                        const date = this.querySelector('.email-date').textContent;
                        const preview = this.querySelector('.email-preview').textContent;

                        document.getElementById('modalSubject').textContent = subject;
                        document.getElementById('modalTo').textContent = to;
                        document.getElementById('modalDate').textContent = date;
                        document.getElementById('modalBody').textContent = preview;

                        modal.style.display = 'block';
                    }
                });
            });

            // Handle delete button clicks
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (confirm('Are you sure you want to delete this email?')) {
                        const emailItem = this.closest('.email-item');
                        const emailId = emailItem.dataset.emailId;

                        fetch('sent.php', {
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

            // Close modal
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>