<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch initial emails
$endpoint = 'https://graph.microsoft.com/v1.0/me/messages';
$params = http_build_query([
    '$top' => 30,
    '$select' => 'id,subject,bodyPreview,from,receivedDateTime',
    '$orderby' => 'receivedDateTime desc',
    '$filter' => "isDraft eq false"
]);

$ch = curl_init($endpoint . '?' . $params);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_SESSION['access_token'],
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$emails = json_decode($response, true);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Classification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="classification.css">
</head>
<body>
    <?php $activePage="classification"; $path="../"; include '../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php  $path="../"; include '../includes/topbar.php'; ?>

        <div class="container mt-4">
            <div class="row">
                <!-- Left Sidebar -->
                <div class="col-md-3">
                    <!-- Main Categories -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Categories</h5>
                            <button onclick="refreshClassifications()" class="btn btn-sm btn-outline-primary" id="refresh-btn">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="list-group" id="mainCategoryList">
                                <a href="#" class="list-group-item list-group-item-action active" data-filter="all">
                                    All Emails
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Priority Levels -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Priority Levels</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group" id="priorityList">
                                <a href="#" class="list-group-item list-group-item-action" data-filter="urgent">
                                    <span class="priority-dot urgent"></span>
                                    Urgent
                                    <span class="badge bg-danger rounded-pill">0</span>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" data-filter="high">
                                    <span class="priority-dot high"></span>
                                    High
                                    <span class="badge bg-warning rounded-pill">0</span>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" data-filter="medium">
                                    <span class="priority-dot medium"></span>
                                    Medium
                                    <span class="badge bg-info rounded-pill">0</span>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action" data-filter="low">
                                    <span class="priority-dot low"></span>
                                    Low
                                    <span class="badge bg-secondary rounded-pill">0</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email List -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div id="emailList">
                                <?php if (isset($emails['value'])): ?>
                                    <?php foreach ($emails['value'] as $index => $email): ?>
                                        <div class="email-item" data-index="<?php echo $index; ?>">
                                            <div class="email-header">
                                                <strong><?php echo htmlspecialchars($email['from']['emailAddress']['name']); ?></strong>
                                                <span class="text-muted">
                                                    <?php 
                                                        $date = new DateTime($email['receivedDateTime']);
                                                        $date->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                                        echo $date->format('M j, g:i A'); 
                                                    ?>
                                                </span>
                                            </div>
                                            <div class="email-subject">
                                                <?php echo htmlspecialchars($email['subject']); ?>
                                            </div>
                                            <div class="email-preview">
                                                <?php echo htmlspecialchars($email['bodyPreview']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this modal HTML just before the closing body tag -->
    <div id="email-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-actions">
                    <button class="action-btn reply-btn" title="Reply">
                        <i class="fas fa-reply"></i> Reply
                    </button>
                </div>
                <button class="close-modal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="email-details"></div>
                <div class="reply-form" style="display: none;">
                    <textarea placeholder="Type your reply here..."></textarea>
                    <div class="reply-actions">
                        <button class="btn-reply">Send Reply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add this CSS just after your existing styles -->
    <style>
    /* Modal and Email Viewing Styles */
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
    }

    .modal-body {
        padding: 20px;
        overflow-y: auto;
        max-height: calc(100vh - 200px);
    }

    .email-details {
        padding: 20px;
    }

    .email-subject-line {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1a202c;
    }

    .email-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .sender-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .email-content-body {
        line-height: 1.6;
        color: #333;
    }

    .reply-form {
        margin-top: 20px;
        padding: 20px;
        border-top: 1px solid #eef0ff;
    }

    .reply-form textarea {
        width: 100%;
        min-height: 150px;
        padding: 12px;
        border: 1px solid #eef0ff;
        border-radius: 8px;
        margin-bottom: 15px;
        font-family: inherit;
    }

    .action-btn, .btn-reply {
        background: none;
        border: none;
        padding: 8px 16px;
        cursor: pointer;
        border-radius: 6px;
        transition: all 0.2s ease;
    }

    .action-btn {
        color: #666;
    }

    .action-btn:hover {
        background-color: #f5f7ff;
        color: #5e64ff;
    }

    .btn-reply {
        background: #5e64ff;
        color: white;
    }

    .btn-reply:hover {
        background: #4b51e6;
    }

    .close-modal {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
    }

    .close-modal:hover {
        background: #f5f7ff;
    }
    </style>

    <!-- Add this JavaScript just before your existing script's closing tag -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        refreshClassifications(); // Load classifications on page load

        // Add email viewing functionality
        const modal = document.getElementById('email-modal');
        const closeBtn = document.querySelector('.close-modal');
        const replyBtn = document.querySelector('.reply-btn');
        const replyForm = document.querySelector('.reply-form');

        // Close modal functionality
        closeBtn.addEventListener('click', () => modal.style.display = "none");
        window.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = "none";
        });

        // Reply functionality
        replyBtn.addEventListener('click', () => {
            replyForm.style.display = replyForm.style.display === 'none' ? 'block' : 'none';
        });

        // Add click event to email items
        function attachEmailClickHandlers() {
            document.querySelectorAll('.email-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    // Prevent click if clicking on checkbox
                    if (e.target.type === 'checkbox') return;
                    const emailId = this.dataset.emailId;
                    openEmail(emailId);
                });
            });
        }

        // Call this after loading emails
        attachEmailClickHandlers();

        function openEmail(emailId) {
            modal.style.display = "block";
            const emailDetails = modal.querySelector('.email-details');
            emailDetails.innerHTML = '<div class="loader">Loading...</div>';

            fetch(`../endpoints/get_email.php?id=${emailId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) throw new Error(data.error);
                    renderEmail(data);
                })
                .catch(error => {
                    emailDetails.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            Failed to load email: ${error.message}
                        </div>
                    `;
                });
        }

        function renderEmail(email) {
            const emailDetails = modal.querySelector('.email-details');
            emailDetails.dataset.emailId = email.id;
            emailDetails.innerHTML = `
                <div class="email-subject-line">${email.subject}</div>
                <div class="email-info">
                    <div class="sender-info">
                        <div class="sender-details">
                            <div class="sender-name">${email.from.emailAddress.name}</div>
                            <div class="sender-email">${email.from.emailAddress.address}</div>
                        </div>
                    </div>
                    <div class="email-date">
                        ${new Date(email.receivedDateTime).toLocaleString()}
                    </div>
                </div>
                <div class="email-content-body">
                    ${email.body.content}
                </div>
            `;
        }

        // Handle reply submission
        document.querySelector('.btn-reply').addEventListener('click', function() {
            const replyText = document.querySelector('.reply-form textarea').value;
            const emailId = modal.querySelector('.email-details').dataset.emailId;

            if (!replyText.trim()) {
                alert('Please enter a reply message');
                return;
            }

            fetch('../endpoints/reply_email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    emailId: emailId,
                    replyText: replyText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Reply sent successfully');
                    modal.style.display = 'none';
                    replyForm.style.display = 'none';
                    document.querySelector('.reply-form textarea').value = '';
                } else {
                    throw new Error(data.error || 'Failed to send reply');
                }
            })
            .catch(error => {
                alert('Error sending reply: ' + error.message);
            });
        });
    });

    let currentClassifications = null;

    function refreshClassifications() {
        const refreshBtn = document.getElementById('refresh-btn');
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        fetch('../endpoints/classify_emails.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) throw new Error(data.error);
                
                // Store the raw classification data
                currentClassifications = data.classifications;
                
                // Debug log to check data
                console.log('Classifications received:', currentClassifications);
                
                updateUI(currentClassifications);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Classification failed: ' + error.message);
            })
            .finally(() => {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
            });
    }

    function updateUI(classifications) {
        // Update main categories
        const mainCategoryList = document.getElementById('mainCategoryList');
        mainCategoryList.innerHTML = `
            <a href="#" class="list-group-item list-group-item-action active" data-filter="all">
                All Emails
            </a>
        `;
        
        // Debug log for categories
        console.log('Updating categories:', classifications.mainCategories);
        
        classifications.mainCategories.forEach(category => {
            mainCategoryList.innerHTML += `
                <a href="#" class="list-group-item list-group-item-action" data-filter="category-${category.name}">
                    ${category.name}
                    <span class="badge bg-primary rounded-pill">${category.emailIds.length}</span>
                </a>
            `;
        });

        // Update priority counts
        Object.entries(classifications.priorityLevels).forEach(([priority, data]) => {
            const badge = document.querySelector(`[data-filter="${priority}"] .badge`);
            if (badge) {
                badge.textContent = data.emailIds.length;
            }
        });

        // Update email list with classifications
        updateEmailList(classifications);

        // Reattach event listeners
        attachFilterListeners();
    }

    function updateEmailList(classifications) {
        const emailItems = document.querySelectorAll('.email-item');
        
        emailItems.forEach(item => {
            const emailIndex = parseInt(item.dataset.index);
            
            // Find priority and category for this email
            let priority = null;
            let category = null;

            // Check priorities
            Object.entries(classifications.priorityLevels).forEach(([level, data]) => {
                if (data.emailIds.includes(emailIndex)) {
                    priority = level;
                }
            });

            // Check categories
            classifications.mainCategories.forEach(cat => {
                if (cat.emailIds.includes(emailIndex)) {
                    category = cat.name;
                }
            });

            // Store classifications as data attributes
            item.dataset.emailPriority = priority || '';
            item.dataset.emailCategory = category || '';

            // Update the item's content
            const originalContent = item.querySelector('.email-content') ? 
                item.querySelector('.email-content').innerHTML : 
                item.innerHTML;

            item.innerHTML = `
                <div class="email-content">
                    ${originalContent}
                </div>
                <div class="email-classifications">
                    ${priority ? `<span class="priority-badge ${priority}">${priority}</span>` : ''}
                    ${category ? `<span class="category-badge">${category}</span>` : ''}
                </div>
            `;
        });
    }

    function attachFilterListeners() {
        document.querySelectorAll('#mainCategoryList a, #priorityList a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all links
                document.querySelectorAll('#mainCategoryList a, #priorityList a')
                    .forEach(l => l.classList.remove('active'));
                
                // Add active class to clicked link
                this.classList.add('active');

                const filter = this.dataset.filter;
                filterEmails(filter);
            });
        });
    }

    function filterEmails(filter) {
        console.log('Filtering by:', filter); // Debug log

        if (!currentClassifications) {
            console.error('No classifications available');
            return;
        }

        const emailItems = document.querySelectorAll('.email-item');
        
        emailItems.forEach(item => {
            const emailIndex = parseInt(item.dataset.index);
            
            if (filter === 'all') {
                item.style.display = 'block';
                return;
            }

            let shouldShow = false;

            if (filter.startsWith('category-')) {
                const categoryName = filter.replace('category-', '');
                const category = currentClassifications.mainCategories
                    .find(cat => cat.name === categoryName);
                
                shouldShow = category && category.emailIds.includes(emailIndex);
                console.log(`Email ${emailIndex} category check:`, categoryName, shouldShow); // Debug log
            } else {
                const priority = currentClassifications.priorityLevels[filter];
                shouldShow = priority && priority.emailIds.includes(emailIndex);
                console.log(`Email ${emailIndex} priority check:`, filter, shouldShow); // Debug log
            }

            item.style.display = shouldShow ? 'block' : 'none';
        });
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        refreshClassifications();
    });
    </script>

</body>
</html>
