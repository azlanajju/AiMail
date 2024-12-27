<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class EmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchAllEmails() {
        // URL encode the query parameters
        $endpoint = 'https://graph.microsoft.com/v1.0/me/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'subject,bodyPreview,from,receivedDateTime',
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
            error_log('Curl error: ' . curl_error($ch));
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);

        // Debug logging
        error_log('API Response: ' . $response);
        
        if (isset($data['error'])) {
            error_log('API Error: ' . print_r($data['error'], true));
            return ['error' => $data['error']['message'] ?? 'Unknown error'];
        }

        return $data['value'] ?? ['error' => 'No emails found'];
    }
}

$emailViewer = new EmailViewer($_SESSION['access_token']);
$emails = $emailViewer->fetchAllEmails();

// Debug logging
error_log('Emails data: ' . print_r($emails, true));
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Inbox</title>
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
        }

        .container {
            margin-left: 250px;
            padding: 20px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
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
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 300px;
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
            margin-bottom: 10px;
        }

        .email-from {
            font-weight: 600;
            color: #333;
        }

        .email-date {
            color: #666;
            font-size: 0.9em;
        }

        .email-subject {
            font-weight: 500;
            color: #1a73e8;
            margin-bottom: 8px;
        }

        .email-preview {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .no-emails {
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

        .email-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .reply-btn {
            color: #1a73e8;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .reply-btn:hover {
            background-color: #f0f7ff;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <div class="search-bar">
                    <input type="text" placeholder="Search in mail">
                </div>
                <div class="user-profile">
                    <i class="fas fa-cog"></i>
                    <i class="fas fa-user-circle"></i>
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
                    echo '<i class="fas fa-inbox"></i> No emails found';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        echo '<div class="email-item" data-email-id="' . htmlspecialchars($email['id']) . '">';
                        echo '<div class="email-header">';
                        echo '<span class="email-from">' . htmlspecialchars($email['from']['emailAddress']['address']) . '</span>';
                        echo '<div class="email-actions">';
                        echo '<a href="reply.php?id=' . htmlspecialchars($email['id']) . '" class="reply-btn"><i class="fas fa-reply"></i> Reply</a>';
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

            <script>
                // Debug data in console
                console.log('Emails:', <?php echo json_encode($emails); ?>);
            </script>
        </div>
    </div>

    <div id="email-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-actions">
                    <button class="modal-action-btn" title="Reply">
                        <i class="fas fa-reply"></i>
                    </button>
                    <button class="modal-action-btn" title="Forward">
                        <i class="fas fa-forward"></i>
                    </button>
                    <button class="modal-action-btn" title="Archive">
                        <i class="fas fa-archive"></i>
                    </button>
                    <button class="modal-action-btn delete-btn" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <button class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="email-details">
                    <div class="email-subject-line"></div>
                    <div class="email-info">
                        <div class="sender-info">
                            <img src="" alt="" class="sender-avatar">
                            <div class="sender-details">
                                <div class="sender-name"></div>
                                <div class="sender-email"></div>
                            </div>
                        </div>
                        <div class="email-date"></div>
                    </div>
                    <div class="email-content-body"></div>
                    <div class="email-attachments"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('email-modal');
        const closeBtn = document.querySelector('.close-modal');
        
        if (!modal || !closeBtn) {
            console.error('Modal elements not found');
            return;
        }

        // Close modal when clicking the close button
        closeBtn.addEventListener('click', function() {
            modal.style.display = "none";
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });

        // Add click event to email items
        document.querySelectorAll('.email-item').forEach(item => {
            item.addEventListener('click', function(e) {
                // Prevent opening email when clicking checkbox
                if (e.target.closest('.email-checkbox')) {
                    return;
                }

                const emailId = this.getAttribute('data-email-id');
                if (!emailId) {
                    console.error('No email ID found');
                    return;
                }

                openEmail(emailId);
            });
        });
    });

    function openEmail(emailId) {
        const modal = document.getElementById('email-modal');
        if (!modal) {
            console.error('Modal not found');
            return;
        }

        // Reset modal content
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="modal-loading">
                <div class="loader"></div>
                <div>Loading email...</div>
            </div>
        `;
        
        modal.style.display = "block";

        // Fetch email content
        fetch(`endpoints/get_email.php?id=${encodeURIComponent(emailId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                renderEmail(data);
            })
            .catch(error => {
                modalBody.innerHTML = `
                    <div class="modal-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>Failed to load email: ${error.message}</div>
                    </div>
                `;
            });
    }

    function renderEmail(email) {
        const modal = document.getElementById('email-modal');
        if (!modal) return;

        const subjectLine = modal.querySelector('.email-subject-line');
        const senderName = modal.querySelector('.sender-name');
        const senderEmail = modal.querySelector('.sender-email');
        const emailDate = modal.querySelector('.email-date');
        const contentBody = modal.querySelector('.email-content-body');
        const senderAvatar = modal.querySelector('.sender-avatar');

        if (subjectLine) subjectLine.textContent = email.subject || 'No Subject';
        if (senderName) senderName.textContent = email.from?.emailAddress?.name || 'Unknown Sender';
        if (senderEmail) senderEmail.textContent = email.from?.emailAddress?.address || '';
        if (emailDate) emailDate.textContent = new Date(email.receivedDateTime).toLocaleString();
        if (contentBody) contentBody.innerHTML = email.body?.content || '';
        
        if (senderAvatar) {
            const name = email.from?.emailAddress?.name || 'U';
            const initials = name.split(' ').map(n => n[0]).join('');
            senderAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(initials)}&background=5e64ff&color=fff`;
        }

        // Handle attachments if present
        const attachmentsDiv = modal.querySelector('.email-attachments');
        if (attachmentsDiv && email.hasAttachments && email.attachments) {
            attachmentsDiv.innerHTML = email.attachments.map(attachment => `
                <div class="attachment">
                    <i class="fas fa-paperclip"></i>
                    <span>${attachment.name}</span>
                </div>
            `).join('');
        }
    }
    </script>

    <style>
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        position: relative;
        background: white;
        margin: 50px auto;
        width: 90%;
        max-width: 800px;
        height: calc(100vh - 100px);
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #eef0ff;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
    }

    .modal-action-btn {
        background: none;
        border: none;
        padding: 8px;
        border-radius: 6px;
        color: #666;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .modal-action-btn:hover {
        background: #f5f7ff;
        color: #5e64ff;
    }

    .modal-action-btn.delete-btn:hover {
        background: #fff5f5;
        color: #ff4757;
    }

    .close-modal {
        background: none;
        border: none;
        padding: 8px;
        cursor: pointer;
        color: #666;
        transition: all 0.2s ease;
    }

    .close-modal:hover {
        color: #5e64ff;
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    .email-details {
        max-width: 700px;
        margin: 0 auto;
    }

    .email-subject-line {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        margin-bottom: 20px;
    }

    .email-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eef0ff;
    }

    .sender-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .sender-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    .sender-name {
        font-weight: 600;
        color: #333;
    }

    .sender-email {
        color: #666;
        font-size: 0.9em;
    }

    .email-date {
        color: #666;
        font-size: 0.9em;
    }

    .email-content-body {
        line-height: 1.6;
        color: #333;
    }

    .email-attachments {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eef0ff;
    }

    .modal-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        gap: 15px;
        color: #666;
    }

    .modal-error {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        gap: 15px;
        color: #ff4757;
    }

    @media (max-width: 768px) {
        .modal-content {
            width: 95%;
            margin: 20px auto;
            height: calc(100vh - 40px);
        }

        .email-subject-line {
            font-size: 20px;
        }
    }
    </style>
</body>
</html> 