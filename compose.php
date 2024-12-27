<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

class EmailSender {
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function sendEmail($to, $subject, $body) {
        $endpoint = 'https://graph.microsoft.com/v1.0/me/sendMail';
        
        $emailData = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $body
                ],
                'toRecipients' => [
                    ['emailAddress' => ['address' => $to]]
                ]
            ]
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            return ['success' => false, 'error' => curl_error($ch)];
        }
        
        curl_close($ch);
        
        return [
            'success' => $httpCode === 202,
            'error' => $httpCode !== 202 ? 'Failed to send email' : null
        ];
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailSender = new EmailSender($_SESSION['access_token']);
    $result = $emailSender->sendEmail(
        $_POST['to'],
        $_POST['subject'],
        $_POST['body']
    );
    
    if ($result['success']) {
        $message = '<div class="alert success">Email sent successfully!</div>';
    } else {
        $message = '<div class="alert error">Failed to send email: ' . htmlspecialchars($result['error']) . '</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Compose Email</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .compose-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .compose-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1a73e8;
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .send-btn {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .send-btn:hover {
            background: #1557b0;
        }

        .draft-btn {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .draft-btn:hover {
            background: #e8eaed;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="main-content">
            <div class="compose-header">
                <h1><i class="fas fa-pen"></i> Compose Email</h1>
            </div>

            <?php echo $message; ?>

            <form class="compose-form" method="POST">
                <div class="form-group">
                    <label for="to">To:</label>
                    <input type="email" id="to" name="to" required 
                           placeholder="recipient@example.com">
                </div>

                <div class="form-group">
                    <label for="subject">Subject:</label>
                    <input type="text" id="subject" name="subject" required 
                           placeholder="Enter subject">
                </div>

                <div class="form-group">
                    <label for="body">Message:</label>
                    <textarea id="body" name="body" required 
                              placeholder="Write your message here..."></textarea>
                </div>

                <div class="button-group">
                    <button type="button" class="draft-btn">
                        <i class="fas fa-save"></i> Save Draft
                    </button>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>