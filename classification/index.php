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
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <?php include '../includes/topbar.php'; ?>

        <div class="container mt-4">
            <div class="row">
                <!-- Categories Sidebar -->
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Categories</h5>
                            <button onclick="refreshClassifications()" class="btn btn-sm btn-outline-primary" id="refresh-btn">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="list-group" id="categoryList">
                                <a href="#" class="list-group-item list-group-item-action active" data-category="all">
                                    All Emails
                                </a>
                                <!-- Categories will be loaded here -->
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        refreshClassifications(); // Load classifications on page load
    });

    function refreshClassifications() {
        const refreshBtn = document.getElementById('refresh-btn');
        const categoryList = document.getElementById('categoryList');
        
        // Show loading state
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        fetch('../endpoints/classify_emails.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.classifications && data.classifications.categories) {
                    // Update category list
                    categoryList.innerHTML = `
                        <a href="#" class="list-group-item list-group-item-action active" data-category="all">
                            All Emails (${data.email_count})
                        </a>
                    `;
                    
                    data.classifications.categories.forEach(category => {
                        categoryList.innerHTML += `
                            <a href="#" class="list-group-item list-group-item-action" data-category="${category.name}">
                                ${category.name}
                                <span class="badge bg-primary rounded-pill">${category.emailIds.length}</span>
                            </a>
                        `;
                    });
                    
                    // Attach click handlers
                    attachCategoryListeners(data.classifications);
                } else {
                    throw new Error(data.message || 'Failed to load classifications');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            })
            .finally(() => {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
            });
    }

    function attachCategoryListeners(classifications) {
        const emailItems = document.querySelectorAll('.email-item');
        const categoryLinks = document.querySelectorAll('#categoryList a');

        categoryLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                categoryLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                const category = this.dataset.category;
                
                emailItems.forEach(item => {
                    const emailIndex = parseInt(item.dataset.index);
                    
                    if (category === 'all') {
                        item.style.display = 'block';
                    } else {
                        const categoryData = classifications.categories.find(c => c.name === category);
                        item.style.display = categoryData && categoryData.emailIds.includes(emailIndex) ? 'block' : 'none';
                    }
                });
            });
        });
    }
    </script>

    <style>
                
.content-wrapper {
margin-left: 250px;
margin-top: 60px; 
padding: 20px;
max-width: 100%;
}
    .email-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        cursor: pointer;
    }

    .email-item:hover {
        background-color: #f8f9fa;
    }

    .email-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .email-subject {
        font-weight: 500;
        margin-bottom: 5px;
    }

    .email-preview {
        color: #666;
        font-size: 0.9em;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .list-group-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    </style>
</body>
</html>
