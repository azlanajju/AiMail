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
    <link rel="stylesheet" href="./profile.css">
</head>
<body>
    <?php $path = "../"; include '../includes/sidebar.php'; ?>
    <?php $path = "../"; include '../includes/topbar.php'; ?>

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