<?php
session_start();

// Check if user is not authenticated
if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class EmailViewer {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function fetchEmails() {
        $ch = curl_init('https://graph.microsoft.com/v1.0/me/messages?$top=50');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Only for development
        
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            return ['error' => curl_error($ch)];
        }
        
        curl_close($ch);
        $data = json_decode($response, true);
        return $data['value'] ?? ['error' => 'No emails found'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Email Viewer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .email-subject {
            color: #333;
            margin-bottom: 10px;
        }
        .email-meta {
            color: #666;
            font-size: 0.9em;
        }
        .logout-btn {
            float: right;
            padding: 10px 20px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
        }
        .summarize-btn {
            float: right;
            padding: 10px 20px;
            background-color: #0078d4;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .summarize-btn:hover {
            background-color: #006cbd;
        }
        .header {
            margin-bottom: 30px;
            overflow: hidden;
        }
        .summary-container {
            background-color: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            border-left: 4px solid #0078d4;
            display: none;
        }
        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .email-count {
            color: #666;
            font-size: 0.9em;
        }
        #summary-content {
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .loading {
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Your Emails</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
        <button onclick="summarizeEmails()" class="summarize-btn">Summarize All Emails</button>
    </div>

    <div id="summary-container" class="summary-container">
        <div class="summary-header">
            <h2>Email Summary</h2>
            <span id="email-count" class="email-count"></span>
        </div>
        <div id="summary-content"></div>
    </div>

    <?php
    $emailViewer = new EmailViewer($_SESSION['access_token']);
    $emails = $emailViewer->fetchEmails();

    if (!isset($emails['error'])) {
        foreach ($emails as $email) {
            echo '<div class="email-container">';
            echo '<h2 class="email-subject">' . htmlspecialchars($email['subject']) . '</h2>';
            echo '<div class="email-meta">';
            echo '<p>From: ' . htmlspecialchars($email['from']['emailAddress']['address']) . '</p>';
            echo '<p>Received: ' . date('Y-m-d H:i:s', strtotime($email['receivedDateTime'])) . '</p>';
            echo '</div>';
            echo '</div>';
        }
    } else {
        echo '<div class="email-container">';
        echo '<p>Error fetching emails: ' . htmlspecialchars($emails['error']) . '</p>';
        echo '</div>';
    }
    ?>

    <script>
    function summarizeEmails() {
        const button = document.querySelector('.summarize-btn');
        const summaryContainer = document.getElementById('summary-container');
        const summaryContent = document.getElementById('summary-content');
        const emailCount = document.getElementById('email-count');
        
        button.disabled = true;
        button.textContent = 'Summarizing...';
        summaryContainer.style.display = 'block';
        summaryContent.innerHTML = '<div class="loading">Analyzing your emails...</div>';
        
        fetch('endpoints/summarize_all.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    emailCount.textContent = `Analyzed ${data.emailCount} emails`;
                    summaryContent.textContent = data.summary;
                } else {
                    summaryContent.innerHTML = `<div style="color: red;">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                summaryContent.innerHTML = `<div style="color: red;">Error: ${error.message}</div>`;
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Summarize All Emails';
            });
    }
    </script>
</body>
</html> 