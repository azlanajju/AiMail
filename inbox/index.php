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
    <link rel="stylesheet" href="./inbox.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9ff;
        }

        .container {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .inbox-header {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(94, 100, 255, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: #f5f7ff;
            color: #5e64ff;
        }

        .email-list {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(94, 100, 255, 0.1);
            overflow: hidden;
        }

        .email-item {
            display: flex;
            padding: 16px 20px;
            border-bottom: 1px solid #eef0ff;
            cursor: pointer;
            transition: all 0.2s ease;
            align-items: flex-start;
            gap: 15px;
        }

        .email-item:hover {
            background: #f8f9ff;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(94, 100, 255, 0.1);
        }

        .email-item.unread {
            background: #f8f9ff;
        }

        .email-item.unread .email-from,
        .email-item.unread .email-subject {
            font-weight: 600;
            color: #5e64ff;
        }

        .email-checkbox {
            padding-right: 15px;
            display: flex;
            align-items: center;
        }

        .email-content {
            flex: 1;
            min-width: 0;
        }

        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .email-from {
            font-weight: 500;
            color: #333;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .email-time {
            color: #666;
            font-size: 12px;
            white-space: nowrap;
        }

        .email-subject {
            color: #333;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .email-preview {
            color: #666;
            font-size: 13px;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .email-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .reply-link {
            color: #5e64ff;
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .reply-link:hover {
            background: #f5f7ff;
        }

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
            box-shadow: 0 5px 15px rgba(94, 100, 255, 0.15);
        }

        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eef0ff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9ff;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 200px);
        }

        .error-message {
            background: #fff5f5;
            color: #ff4757;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .no-emails {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .no-emails i {
            font-size: 3em;
            color: #5e64ff;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                padding: 10px;
            }

            .email-meta {
                flex-direction: column;
                align-items: flex-end;
            }

            .email-preview {
                display: none;
            }
        }

        .email-content-body {
            padding: 20px 0;
            line-height: 1.6;
        }

        .email-content-body img {
            max-width: 100%;
            height: auto;
        }

        .attachments-title {
            font-size: 16px;
            color: #333;
            margin: 20px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eef0ff;
        }

        .attachments-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .attachment-item {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9ff;
            border-radius: 8px;
            border: 1px solid #eef0ff;
            transition: all 0.2s ease;
        }

        .attachment-item:hover {
            background: #f5f7ff;
            border-color: #5e64ff;
        }

        .attachment-icon {
            font-size: 24px;
            color: #5e64ff;
            margin-right: 12px;
        }

        .attachment-details {
            flex: 1;
            min-width: 0;
        }

        .attachment-name {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .attachment-size {
            font-size: 12px;
            color: #666;
        }

        .attachment-download {
            color: #5e64ff;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .attachment-download:hover {
            background: #eef0ff;
        }
    </style>
</head>
<body>
    <?php $activePage="inbox";
 $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="inbox-header">
                <div class="header-actions">
                    <button class="action-btn">
                        <i class="fas fa-check-square"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-redo"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-archive"></i>
                    </button>
                    <button class="action-btn">
                        <i class="fas fa-trash"></i>
                    </button>
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
                    echo '<i class="fas fa-inbox"></i>';
                    echo '<p>Your inbox is empty</p>';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        ?>
                        <div class="email-item <?php echo isset($email['isRead']) && !$email['isRead'] ? 'unread' : ''; ?>" 
                             data-email-id="<?php echo htmlspecialchars($email['id']); ?>">
                            <div class="email-checkbox">
                                <input type="checkbox" id="check-<?php echo htmlspecialchars($email['id']); ?>">
                                <label for="check-<?php echo htmlspecialchars($email['id']); ?>"></label>
                            </div>
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-from">
                                        <?php 
                                        $from = $email['from']['emailAddress']['address'] ?? '';
                                        echo htmlspecialchars($from);
                                        ?>
                                    </div>
                                    <div class="email-actions">
                                        <?php if ($email['hasAttachments'] ?? false): ?>
                                            <i class="fas fa-paperclip" style="color: #666;"></i>
                                        <?php endif; ?>
                                        <span class="email-time">
                                            <?php echo $date->format('M j, g:i A'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="email-subject">
                                    <?php echo htmlspecialchars($email['subject'] ?? 'No Subject'); ?>
                                </div>
                                <div class="email-preview">
                                    <?php echo htmlspecialchars($email['bodyPreview'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                        <?php
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
        fetch(`../endpoints/get_email.php?id=${encodeURIComponent(emailId)}`)
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
        if (!modal) {
            console.error('Modal not found for rendering');
            return;
        }

        console.log('Rendering email:', email); // Debug log

        const modalBody = modal.querySelector('.modal-body');
        
        // Create a safe version of the HTML content
        const createSafeHtml = (htmlContent) => {
            // Create a new div to hold the content
            const div = document.createElement('div');
            // Set the HTML content
            div.innerHTML = htmlContent;
            
            // Find all images and set their src attributes to be loaded securely
            div.querySelectorAll('img').forEach(img => {
                // Preserve original source as data attribute
                img.setAttribute('data-original-src', img.src);
                // Set max dimensions for images
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
            });
            
            return div.innerHTML;
        };

        // Add modal header with actions
        const modalHeader = `
            <div class="modal-header">
                <div class="modal-actions">
                    <button class="modal-action-btn" onclick="redirectToReply('${email.id}')" title="Reply">
                        <i class="fas fa-reply"></i> Reply
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

            </div>
        `;

        modalBody.innerHTML = modalHeader + `
            <div class="email-details">
                <div class="email-subject-line">${email.subject || 'No Subject'}</div>
                <div class="email-info">
                    <div class="sender-info">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(email.from?.emailAddress?.name || 'U')}&background=5e64ff&color=fff" 
                             alt="" 
                             class="sender-avatar">
                        <div class="sender-details">
                            <div class="sender-name">${email.from?.emailAddress?.name || 'Unknown Sender'}</div>
                            <div class="sender-email">${email.from?.emailAddress?.address || ''}</div>
                        </div>
                    </div>
                    <div class="email-date">${new Date(email.receivedDateTime).toLocaleString()}</div>
                </div>
                <div class="email-content-body">
                    ${createSafeHtml(email.body?.content || '')}
                </div>
                ${email.hasAttachments ? `
                    <div class="email-attachments">
                        <h3 class="attachments-title">
                            <i class="fas fa-paperclip"></i> 
                            Attachments (${email.attachments?.length || 0})
                        </h3>
                        <div class="attachments-list">
                            ${email.attachments?.map(attachment => `
                                <div class="attachment-item">
                                    <div class="attachment-icon">
                                        <i class="fas ${getAttachmentIcon(attachment.contentType)}"></i>
                                    </div>
                                    <div class="attachment-details">
                                        <div class="attachment-name">${attachment.name}</div>
                                        <div class="attachment-size">${formatFileSize(attachment.size)}</div>
                                    </div>
                                    <a href="../endpoints/download_attachment.php?messageId=${email.id}&attachmentId=${attachment.id}" 
                                       class="attachment-download" 
                                       download="${attachment.name}">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            `).join('') || ''}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    // Helper function to get appropriate icon based on file type
    function getAttachmentIcon(contentType) {
        if (contentType.includes('image')) return 'fa-image';
        if (contentType.includes('pdf')) return 'fa-file-pdf';
        if (contentType.includes('word')) return 'fa-file-word';
        if (contentType.includes('excel') || contentType.includes('spreadsheet')) return 'fa-file-excel';
        if (contentType.includes('powerpoint') || contentType.includes('presentation')) return 'fa-file-powerpoint';
        if (contentType.includes('zip') || contentType.includes('compressed')) return 'fa-file-archive';
        return 'fa-file';
    }

    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Add this function to handle the reply redirect
    function redirectToReply(emailId) {
        if (!emailId) {
            console.error('No email ID provided for reply');
            return;
        }
        window.location.href = `reply.php?id=${encodeURIComponent(emailId)}`;
    }
    </script>


</body>
</html> 