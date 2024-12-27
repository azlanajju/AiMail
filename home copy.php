<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

require 'vendor/autoload.php'; // Make sure you have this for Parsedown

class EmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchAllEmails() {
        $ch = curl_init('https://graph.microsoft.com/v1.0/me/messages?$top=50');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development
        
        $response = curl_exec($ch);
        
        // Log the raw response
        error_log("Raw API Response: " . $response);
        
        if(curl_errno($ch)) {
            error_log("Curl Error: " . curl_error($ch));
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);
        
        // Log the decoded data
        error_log("Decoded Data: " . print_r($data, true));
        
        $emails = [];
        if (isset($data['value'])) {
            foreach ($data['value'] as $email) {
                // Parse Markdown body
                $parsedown = new Parsedown();
                $parsedBody = $parsedown->text($email['bodyPreview']); // Parse the bodyPreview as Markdown
                
                $emails[] = [
                    'subject' => $email['subject'],
                    'body' => $parsedBody  // Store the parsed Markdown as HTML
                ];
            }
        }
        
        // Log the formatted emails
        error_log("Formatted Emails: " . print_r($emails, true));
        
        return $emails;
    }

    public function getSummary($emails) {
        if (empty($emails)) {
            error_log("No emails to summarize");
            return ['success' => false, 'error' => 'No emails to summarize'];
        }

        $data = ['emails' => $emails];
        
        // Log the data being sent to summarize_all.php
        error_log("Sending to summarize_all.php: " . json_encode($data));

        $ch = curl_init('http://localhost/smartCompose/endpoints/summarize_all.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        // Log the summary response
        error_log("Summary Response: " . $response);
        
        curl_close($ch);
        return json_decode($response, true);
    }
}

// Log the access token (first 10 characters only for security)
error_log("Access Token (first 10 chars): " . substr($_SESSION['access_token'], 0, 10));

$emailViewer = new EmailViewer($_SESSION['access_token']);
$emails = $emailViewer->fetchAllEmails();

// Log the final emails array
error_log("Final Emails Array: " . print_r($emails, true));

$summary = $emailViewer->getSummary($emails);

// Log the final summary
error_log("Final Summary: " . print_r($summary, true));

// Add this debug section right before rendering
if (!empty($summary)) {
    error_log('Raw Summary Response: ' . print_r($summary, true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Home</title>
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
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .search-bar input {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 20px;
            width: 300px;
        }

        .greeting {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .summary-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .summary-header h2 {
            color: #1a73e8;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .email-count {
            background: #e8f0fe;
            color: #1a73e8;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 14px;
        }

        .summary-content {
            line-height: 1.6;
            color: #333;
            font-size: 16px;
        }

        .actions {
            margin-bottom: 20px;
        }

        .action-btn {
            padding: 10px 20px;
            background: #1a73e8;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-btn:hover {
            background: #1557b0;
        }

        .action-btn:disabled {
            background: #ccc;
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

            <h1 class="greeting">Good <?php 
                $hour = date('H');
                if ($hour < 12) echo "Morning";
                else if ($hour < 17) echo "Afternoon";
                else echo "Evening";
            ?></h1>

            <?php if ($summary && $summary['success']): ?>
            <div class="summary-section">
                <?php
                if (isset($summary['error'])) {
                    echo '<div class="error-message">';
                    echo '<i class="fas fa-exclamation-circle"></i> ';
                    echo htmlspecialchars($summary['error']);
                    echo '</div>';
                } elseif (empty($summary)) {
                    echo '<div class="no-summary">';
                    echo '<i class="fas fa-inbox"></i> No summary available';
                    echo '</div>';
                } else {
                    echo '<div class="summary-header">';
                    echo '<h2><i class="fas fa-robot"></i> AI Email Summary</h2>';
                    echo '<span class="email-count">' . count($emails) . ' emails analyzed</span>';
                    echo '</div>';
                    echo '<div class="markdown-content">';
                    // Debug: Show raw markdown
                    echo '<pre style="display:none">' . htmlspecialchars($summary['summary']) . '</pre>';
                    // Convert markdown to HTML with error checking
                    $parsedown = new Parsedown();
                    $markdownContent = $summary['summary'] ?? 'No summary generated';
                    $htmlContent = $parsedown->text($markdownContent);
                    echo $htmlContent;
                    echo '</div>';
                }
                ?>
            </div>
            <?php endif; ?>


        </div>
    </div>

    <!-- Add console logging -->
    <script>
        console.log('Raw Summary:', <?php echo json_encode($summary); ?>);
        console.log('Markdown Content:', <?php echo json_encode($markdownContent ?? null); ?>);
        console.log('HTML Content:', <?php echo json_encode($htmlContent ?? null); ?>);
    </script>
</body>
</html>
