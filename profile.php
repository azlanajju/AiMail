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
    <?php include 'includes/sidebar.php'; ?>
    <?php include 'includes/topbar.php'; ?>

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
            </div>
        </div>
    </div>
</body>
</html> 