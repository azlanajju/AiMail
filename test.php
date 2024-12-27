<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class EmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchAllEmails() {
        // URL encode the query parameters
        $endpoint = 'https://graph.microsoft.com/v1.0/me/messages';
        $params = http_build_query([
            '$top' => 50,
            '$select' => 'subject,bodyPreview,from,receivedDateTime',
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
            error_log('Curl error: ' . curl_error($ch));
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);

        // Debug logging
        error_log('API Response: ' . $response);
        
        if (isset($data['error'])) {
            error_log('API Error: ' . print_r($data['error'], true));
            return ['error' => $data['error']['message'] ?? 'Unknown error'];
        }

        return $data['value'] ?? ['error' => 'No emails found'];
    }
}

$emailViewer = new EmailViewer($_SESSION['access_token']);
$emails = $emailViewer->fetchAllEmails();

// Debug logging
error_log('Emails data: ' . print_r($emails, true));
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Inbox</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
        }

        .container {
            margin-left: 250px;
            padding: 20px;
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-bar input {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 300px;
            font-size: 14px;
        }

        .email-list {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .email-item {
            padding: 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .email-item:hover {
            background-color: #f8f9fa;
        }

        .email-item:last-child {
            border-bottom: none;
        }

        .email-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .email-from {
            font-weight: 600;
            color: #333;
        }

        .email-date {
            color: #666;
            font-size: 0.9em;
        }

        .email-subject {
            font-weight: 500;
            color: #1a73e8;
            margin-bottom: 8px;
        }

        .email-preview {
            color: #666;
            font-size: 0.9em;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .no-emails {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="header">
                <div class="search-bar">
                    <input type="text" placeholder="Search in mail">
                </div>
                <div class="user-profile">
                    <i class="fas fa-cog"></i>
                    <i class="fas fa-user-circle"></i>
                </div>
            </div>

            <div class="email-list">
                <?php
                if (isset($emails['error'])) {
                    echo '<div class="error-message">';
                    echo '<i class="fas fa-exclamation-circle"></i> ';
                    echo htmlspecialchars($emails['error']);
                    echo '</div>';
                } else if (empty($emails)) {
                    echo '<div class="no-emails">';
                    echo '<i class="fas fa-inbox"></i> No emails found';
                    echo '</div>';
                } else {
                    foreach ($emails as $email) {
                        $date = new DateTime($email['receivedDateTime']);
                        echo '<div class="email-item">';
                        echo '<div class="email-header">';
                        echo '<span class="email-from">' . htmlspecialchars($email['from']['emailAddress']['address']) . '</span>';
                        echo '<span class="email-date">' . $date->format('M j, g:i A') . '</span>';
                        echo '</div>';
                        echo '<div class="email-subject">' . htmlspecialchars($email['subject']) . '</div>';
                        echo '<div class="email-preview">' . htmlspecialchars($email['bodyPreview']) . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>

            <script>
                // Debug data in console
                console.log('Emails:', <?php echo json_encode($emails); ?>);
            </script>
        </div>
    </div>
</body>
</html> 