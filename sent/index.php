<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: ../index.php');
    exit;
}

class SentEmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchSentEmails() {
        $endpoint = 'https://graph.microsoft.com/v1.0/me/mailFolders/SentItems/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'id,subject,bodyPreview,toRecipients,receivedDateTime,hasAttachments,body',
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
            curl_close($ch);
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['value'] ?? ['error' => 'No sent emails found'];
    }
}

$emailViewer = new SentEmailViewer($_SESSION['access_token']);
$emails = $emailViewer->fetchSentEmails();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sent Items</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./sent.css">
</head>
<body>
    <?php $activePage="sent"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-paper-plane"></i>
                    Sent Items
                </h2>
            </div>

            <div class="email-list">
                <?php 
                if (isset($emails['error'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-exclamation-circle"></i>';
                    echo '<p>Error loading sent emails</p>';
                    echo '</div>';
                } else if (empty($emails)) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-paper-plane"></i>';
                    echo '<p>No sent emails</p>';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        $recipients = array_map(function($recipient) {
                            return $recipient['emailAddress']['name'] ?? $recipient['emailAddress']['address'];
                        }, $email['toRecipients']);
                        ?>
                        <div class="email-item" data-email-id="<?php echo htmlspecialchars($email['id']); ?>">
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-to">
                                        To: <?php echo htmlspecialchars(implode(', ', $recipients)); ?>
                                    </div>
                                    <div class="email-actions">
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

    <?php include '../includes/email-modal.php'; ?>

    <script>
    document.querySelectorAll('.email-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target.closest('.delete-btn')) {
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

        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        modal.style.display = 'block';

        fetch(`../get_email.php?id=${encodeURIComponent(emailId)}`)
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
        
        const recipients = email.toRecipients.map(recipient => 
            recipient.emailAddress.name || recipient.emailAddress.address
        ).join(', ');

        modalBody.innerHTML = `
            <div class="email-full-content">
                <div class="sender-info">
                    <div class="sender-details">
                        <div class="sender-name">To: ${recipients}</div>
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

    // Handle Delete button clicks
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation();
            const emailItem = this.closest('.email-item');
            const emailId = emailItem.getAttribute('data-email-id');
            
            if (confirm('Delete this email?')) {
                try {
                    const response = await fetch('../delete_email.php', {
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