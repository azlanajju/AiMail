<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

// Function to get deleted emails
function getDeletedEmails($accessToken) {
    $endpoint = "https://graph.microsoft.com/v1.0/me/messages?\$filter=parentFolderId eq 'deleteditems'&\$orderby=receivedDateTime desc";
    
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

$emails = getDeletedEmails($_SESSION['access_token']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Trash</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./trash.css">
</head>
<body>
    <?php $activePage="trash"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-trash-alt"></i>
                    Trash
                </h2>
                <div class="action-buttons">
                    <button class="action-btn restore-btn" id="restoreSelected" disabled>
                        <i class="fas fa-trash-restore"></i>
                        Restore
                    </button>
                    <button class="action-btn delete-btn" id="deleteSelected" disabled>
                        <i class="fas fa-trash"></i>
                        Delete Forever
                    </button>
                </div>
            </div>

            <div class="email-list">
                <?php 
                if (isset($emails['error'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-exclamation-circle"></i>';
                    echo '<p>Error loading deleted emails</p>';
                    echo '</div>';
                } else if (empty($emails['value'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-trash-alt"></i>';
                    echo '<p>No items in trash</p>';
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

    <?php $path="../"; include '../includes/email-modal.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const restoreBtn = document.getElementById('restoreSelected');
        const deleteBtn = document.getElementById('deleteSelected');
        const checkboxes = document.querySelectorAll('.email-select');

        // Handle checkbox selection
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const selectedCount = document.querySelectorAll('.email-select:checked').length;
                restoreBtn.disabled = selectedCount === 0;
                deleteBtn.disabled = selectedCount === 0;
            });
        });

        // Handle restore action
        restoreBtn.addEventListener('click', async function() {
            const selectedEmails = Array.from(document.querySelectorAll('.email-select:checked'))
                .map(checkbox => checkbox.closest('.email-item').getAttribute('data-email-id'));
            
            if (confirm('Restore selected emails?')) {
                try {
                    // Add your restore logic here
                    console.log('Restoring emails:', selectedEmails);
                } catch (error) {
                    alert('Error restoring emails: ' + error.message);
                }
            }
        });

        // Handle permanent delete action
        deleteBtn.addEventListener('click', async function() {
            const selectedEmails = Array.from(document.querySelectorAll('.email-select:checked'))
                .map(checkbox => checkbox.closest('.email-item').getAttribute('data-email-id'));
            
            if (confirm('Permanently delete selected emails? This action cannot be undone.')) {
                try {
                    // Add your delete logic here
                    console.log('Deleting emails:', selectedEmails);
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