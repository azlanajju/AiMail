<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: ../index.php');
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
$emails = $emailViewer->fetchSpamEmails();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Spam</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./spam.css">
</head>
<body>
    <?php $activePage="spam"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="warning-banner">
                <i class="fas fa-exclamation-triangle"></i>
                Messages in this folder may contain suspicious content. Exercise caution when opening links or attachments.
            </div>

            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-shield-alt"></i>
                    Spam
                </h2>
            </div>

            <div class="email-list">
                <?php 
                if (isset($emails['error'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-exclamation-circle"></i>';
                    echo '<p>Error loading spam emails</p>';
                    echo '</div>';
                } else if (empty($emails)) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-shield-alt"></i>';
                    echo '<p>No spam emails</p>';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        ?>
                        <div class="email-item" data-email-id="<?php echo htmlspecialchars($email['id']); ?>">
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-from">
                                        <?php echo htmlspecialchars($email['from']['emailAddress']['name'] ?? $email['from']['emailAddress']['address']); ?>
                                        <span class="spam-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Spam
                                        </span>
                                    </div>
                                    <div class="email-actions">
                                        <button class="not-spam-btn" title="Not Spam">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="delete-btn" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <span class="email-date">
                                            <?php echo $date->format('M j, g:i A'); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="email-subject">
                                    <?php echo htmlspecialchars($email['subject']); ?>
                                </div>
                                <div class="email-preview">
                                    <?php echo htmlspecialchars($email['bodyPreview']); ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <?php  include '../includes/email-modal.php'; ?>

    <script>
    document.querySelectorAll('.email-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.not-spam-btn') || e.target.closest('.delete-btn')) {
                return;
            }
            const emailId = this.getAttribute('data-email-id');
            if (emailId) {
                openEmail(emailId);
            }
        });
    });

    function openEmail(emailId) {
        const modal = document.getElementById('email-modal');
        if (!modal) {
            console.error('Modal not found');
            return;
        }

        // Show loading state
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        modal.style.display = 'block';

        // Fetch and display email content
        fetch(`get_email_spam.php?id=${encodeURIComponent(emailId)}`)
            .then(response => response.json())
            .then(email => {
                modal.setAttribute('data-email-id', email.id);
                renderEmail(email);
            })
            .catch(error => {
                modalBody.innerHTML = `<div class="error">Error loading email: ${error.message}</div>`;
            });
    }

    function renderEmail(email) {
        const modal = document.getElementById('email-modal');
        const modalBody = modal.querySelector('.modal-body');
        
        modalBody.innerHTML = `
            <div class="email-full-content">
                <div class="sender-info">
                    <div class="sender-details">
                        <div class="sender-name">${email.from?.emailAddress?.name || 'Unknown Sender'}</div>
                        <div class="sender-email">${email.from?.emailAddress?.address || ''}</div>
                    </div>
                    <div class="email-date">${new Date(email.receivedDateTime).toLocaleString()}</div>
                </div>
                <div class="email-subject-full">
                    <h3>${email.subject || 'No Subject'}</h3>
                </div>
                <div class="email-body-content">
                    ${email.body?.content || ''}
                </div>
            </div>
        `;
    }

    // Close modal when clicking the close button
    document.querySelector('.close-modal')?.addEventListener('click', function() {
        document.getElementById('email-modal').style.display = 'none';
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('email-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    // Handle Not Spam button clicks
    document.querySelectorAll('.not-spam-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation();
            const emailItem = this.closest('.email-item');
            const emailId = emailItem.getAttribute('data-email-id');
            
            if (confirm('Move this email to inbox?')) {
                try {
                    const response = await fetch('move_to_inbox.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ emailId })
                    });
                    
                    if (response.ok) {
                        emailItem.remove();
                        if (document.querySelectorAll('.email-item').length === 0) {
                            location.reload();
                        }
                    } else {
                        throw new Error('Failed to move email');
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        });
    });

    // Handle Delete button clicks
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation();
            const emailItem = this.closest('.email-item');
            const emailId = emailItem.getAttribute('data-email-id');
            
            if (confirm('Delete this email?')) {
                try {
                    const response = await fetch('delete_email.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ emailId })
                    });
                    
                    if (response.ok) {
                        emailItem.remove();
                        if (document.querySelectorAll('.email-item').length === 0) {
                            location.reload();
                        }
                    } else {
                        throw new Error('Failed to delete email');
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                }
            }
        });
    });
    </script>
</body>
</html> 