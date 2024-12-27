<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

// Function to get starred emails
function getStarredEmails($accessToken) {
    $endpoint = "https://graph.microsoft.com/v1.0/me/messages?\$filter=flag/flagStatus eq 'flagged'&\$orderby=receivedDateTime desc";
    
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

$emails = getStarredEmails($_SESSION['access_token']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Starred Emails</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="./starred.css">
</head>
<body>
    <?php $activePage="starred"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="page-header">
                <h2 class="page-title">
                    <i class="fas fa-star"></i>
                    Starred Emails
                </h2>
            </div>

            <div class="email-list">
                <?php 
                if (isset($emails['error'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-exclamation-circle"></i>';
                    echo '<p>Error loading starred emails</p>';
                    echo '</div>';
                } else if (empty($emails['value'])) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-star"></i>';
                    echo '<p>No starred emails</p>';
                    echo '</div>';
                } else {
                    foreach ($emails['value'] as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        ?>
                        <div class="email-item" data-email-id="<?php echo htmlspecialchars($email['id']); ?>">
                            <div class="email-checkbox">
                                <input type="checkbox" id="check-<?php echo htmlspecialchars($email['id']); ?>">
                            </div>
                            <div class="email-content">
                                <div class="email-header">
                                    <div class="email-from">
                                        <?php echo htmlspecialchars($email['from']['emailAddress']['name'] ?? $email['from']['emailAddress']['address']); ?>
                                    </div>
                                    <div class="email-meta">
                                        <i class="fas fa-star email-star"></i>
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

    <!-- Include your email modal code here -->
    <?php  include '../includes/email-modal.php'; ?>

    <script>
        // Add your email opening and starring functionality here
        document.querySelectorAll('.email-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.email-checkbox') || e.target.closest('.email-star')) {
                    return;
                }
                const emailId = this.getAttribute('data-email-id');
                // Your email opening code here
            });
        });
    </script>
</body>
</html> 