<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: ../index.php');
    exit;
}

// Function to get archived emails
function getArchivedEmails($accessToken) {
    $endpoint = "https://graph.microsoft.com/v1.0/me/messages?\$filter=parentFolderId eq 'archive'&\$orderby=receivedDateTime desc";
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$emails = getArchivedEmails($_SESSION['access_token']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Archive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./archive.css">
</head>
<body>
    <?php $activePage="archive"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-archive"></i>
                    Archive
                </h2>
                <div class="action-buttons">
                    <button class="action-btn move-inbox-btn" id="moveToInbox" disabled>
                        <i class="fas fa-inbox"></i>
                        Move to Inbox
                    </button>
                    <button class="action-btn delete-btn" id="deleteSelected" disabled>
                        <i class="fas fa-trash"></i>
                        Delete
                    </button>
                </div>
            </div>

            <div class="email-list">
                <?php 
                if (isset($emails['error'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-exclamation-circle"></i>';
                    echo '<p>Error loading archived emails</p>';
                    echo '</div>';
                } else if (empty($emails['value'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-archive"></i>';
                    echo '<p>No archived emails</p>';
                    echo '</div>';
                } else {
                    foreach ($emails['value'] as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        ?>
                        <div class="email-item" data-email-id="<?php echo htmlspecialchars($email['id']); ?>">
                            <div class="email-checkbox">
                                <input type="checkbox" class="email-select" id="check-<?php echo htmlspecialchars($email['id']); ?>">
                            </div>
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-from">
                                        <?php echo htmlspecialchars($email['from']['emailAddress']['name'] ?? $email['from']['emailAddress']['address']); ?>
                                    </div>
                                    <div class="email-meta">
                                        <?php if ($email['hasAttachments']): ?>
                                            <i class="fas fa-paperclip"></i>
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
        </div>
    </div>

    <?php include '../includes/email-modal.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const moveToInboxBtn = document.getElementById('moveToInbox');
        const deleteBtn = document.getElementById('deleteSelected');
        const checkboxes = document.querySelectorAll('.email-select');

        // Handle checkbox selection
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selectedCount = document.querySelectorAll('.email-select:checked').length;
                moveToInboxBtn.disabled = selectedCount === 0;
                deleteBtn.disabled = selectedCount === 0;
            });
        });

        // Handle move to inbox action
        moveToInboxBtn.addEventListener('click', async function() {
            const selectedEmails = Array.from(document.querySelectorAll('.email-select:checked'))
                .map(checkbox => checkbox.closest('.email-item').getAttribute('data-email-id'));
            
            if (confirm('Move selected emails to inbox?')) {
                try {
                    // Add your move to inbox logic here
                    console.log('Moving emails to inbox:', selectedEmails);
                } catch (error) {
                    alert('Error moving emails: ' + error.message);
                }
            }
        });

        // Handle delete action
        deleteBtn.addEventListener('click', async function() {
            const selectedEmails = Array.from(document.querySelectorAll('.email-select:checked'))
                .map(checkbox => checkbox.closest('.email-item').getAttribute('data-email-id'));
            
            if (confirm('Move selected emails to trash?')) {
                try {
                    // Add your delete logic here
                    console.log('Moving emails to trash:', selectedEmails);
                } catch (error) {
                    alert('Error deleting emails: ' + error.message);
                }
            }
        });

        // Email opening functionality
        document.querySelectorAll('.email-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.email-checkbox')) {
                    return;
                }
                const emailId = this.getAttribute('data-email-id');
                if (emailId) {
                    openEmail(emailId);
                }
            });
        });
    });
    </script>
</body>
</html> 