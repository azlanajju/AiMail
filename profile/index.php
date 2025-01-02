<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

// Fetch user profile from Microsoft Graph API
function getUserProfile($accessToken) {
    $endpoint = 'https://graph.microsoft.com/v1.0/me';
    
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$userProfile = getUserProfile($_SESSION['access_token']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        .container {
            margin-left: 250px;
            padding: 20px;
        }

        .main-content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-bottom: 20px;
            border: 4px solid #5e64ff;
            padding: 4px;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .profile-email {
            color: #666;
            margin-bottom: 20px;
        }

        .profile-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 600;
            color: #5e64ff;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .profile-details {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .details-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #5e64ff;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eef0ff;
        }

        .detail-label {
            color: #666;
            font-size: 14px;
        }

        .detail-value {
            color: #1a1a1a;
            font-weight: 500;
        }

        .edit-button {
            background: #5e64ff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .edit-button:hover {
            background: #4c52cc;
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
            }

            .profile-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="profile-header">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($userProfile['displayName']); ?>&background=5e64ff&color=fff" 
                     alt="Profile" 
                     class="profile-image">
                <h1 class="profile-name"><?php echo htmlspecialchars($userProfile['displayName']); ?></h1>
                <div class="profile-email"><?php echo htmlspecialchars($userProfile['mail']); ?></div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $userProfile['mailboxSettings']['timeZone'] ?? 'N/A'; ?></div>
                    <div class="stat-label">Timezone</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $userProfile['officeLocation'] ?? 'N/A'; ?></div>
                    <div class="stat-label">Office Location</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $userProfile['jobTitle'] ?? 'N/A'; ?></div>
                    <div class="stat-label">Job Title</div>
                </div>
            </div>

            <div class="profile-details">
                <div class="details-section">
                    <h2 class="section-title">
                        <i class="fas fa-user"></i>
                        Personal Information
                    </h2>
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userProfile['displayName']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userProfile['mail']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Job Title</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userProfile['jobTitle'] ?? 'Not set'); ?></span>
                    </div>
                </div>

                <div class="details-section">
                    <h2 class="section-title">
                        <i class="fas fa-building"></i>
                        Work Information
                    </h2>
                    <div class="detail-item">
                        <span class="detail-label">Department</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userProfile['department'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Office Location</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userProfile['officeLocation'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Preferred Language</span>
                        <span class="detail-value"><?php echo htmlspecialchars($userProfile['preferredLanguage'] ?? 'Not set'); ?></span>
                    </div>
                </div>

                <div class="details-section">
                    <h2 class="section-title">
                        <i class="fas fa-envelope-open"></i>
                        Email Permissions
                    </h2>
                    
                    <div class="permissions-grid">
                        <div class="permission-card">
                            <div class="permission-header">
                                <i class="fas fa-envelope-open-text"></i>
                                <h3>Read Access</h3>
                            </div>
                            <ul class="permission-list">
                                <li><i class="fas fa-check"></i> View emails and attachments</li>
                                <li><i class="fas fa-check"></i> Access email folders</li>
                                <li><i class="fas fa-check"></i> Search emails</li>
                                <li><i class="fas fa-check"></i> View email metadata</li>
                            </ul>
                            <div class="permission-status granted">
                                <i class="fas fa-shield-check"></i> Granted
                            </div>
                        </div>

                        <div class="permission-card">
                            <div class="permission-header">
                                <i class="fas fa-pen-to-square"></i>
                                <h3>Write Access</h3>
                            </div>
                            <ul class="permission-list">
                                <li><i class="fas fa-check"></i> Send new emails</li>
                                <li><i class="fas fa-check"></i> Delete emails</li>
                                <li><i class="fas fa-check"></i> Move emails between folders</li>
                                <li><i class="fas fa-check"></i> Mark emails as read/unread</li>
                            </ul>
                            <div class="permission-status granted">
                                <i class="fas fa-shield-check"></i> Granted
                            </div>
                        </div>
                    </div>

                    <div class="permissions-actions">
                        <button class="revoke-button" onclick="revokePermissions()">
                            <i class="fas fa-ban"></i> Revoke All Permissions
                        </button>
                        <button class="refresh-button" onclick="refreshPermissions()">
                            <i class="fas fa-sync"></i> Refresh Permissions
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    async function revokePermissions() {
        if (confirm('Are you sure you want to revoke all permissions? You will need to re-authenticate to use the application.')) {
            try {
                const response = await fetch('../endpoints/revoke_permissions.php', {
                    method: 'POST'
                });
                
                if (response.ok) {
                    window.location.href = '../logout.php';
                } else {
                    throw new Error('Failed to revoke permissions');
                }
            } catch (error) {
                alert('Error revoking permissions: ' + error.message);
            }
        }
    }

    async function refreshPermissions() {
        try {
            const response = await fetch('endpoints/refresh_permissions.php');
            const data = await response.json();
            
            if (data.success) {
                const cards = document.querySelectorAll('.permission-status');
                cards.forEach(card => {
                    card.className = 'permission-status granted';
                    card.innerHTML = '<i class="fas fa-shield-check"></i> Granted';
                });
                alert('Permissions refreshed successfully');
            } else {
                throw new Error(data.error || 'Failed to refresh permissions');
            }
        } catch (error) {
            alert('Error refreshing permissions: ' + error.message);
        }
    }
    </script>
</body>
</html> 