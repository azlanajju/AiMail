<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

// Get email ID from URL
$emailId = $_GET['id'] ?? '';
if (empty($emailId)) {
    header('Location: inbox.php');
    exit;
}

// Fetch original email details
function getEmailDetails($accessToken, $emailId) {
    $endpoint = "https://graph.microsoft.com/v1.0/me/messages/$emailId";
    
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

$email = getEmailDetails($_SESSION['access_token'], $emailId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reply to Email</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ... (keep your existing styles) ... */
        .compose-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .original-email {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .compose-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .ai-generate-btn,
        .send-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .ai-generate-btn {
            background: #f0f7ff;
            color: #1a73e8;
        }

        .send-btn {
            background: #1a73e8;
            color: white;
        }

        .prompt-input {
            display: none;
            margin-top: 10px;
        }

        .prompt-input.visible {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="container">
        <div class="compose-container">
            <h2><i class="fas fa-reply"></i> Compose Reply</h2>

            <div class="original-email">
                <strong>From:</strong> <?php echo htmlspecialchars($email['from']['emailAddress']['address']); ?><br>
                <strong>Subject:</strong> <?php echo htmlspecialchars($email['subject']); ?><br>
                <strong>Received:</strong> <?php echo (new DateTime($email['receivedDateTime']))->format('M j, g:i A'); ?><br>
                <hr>
                <div class="email-body">
                    <?php echo htmlspecialchars($email['bodyPreview']); ?>
                </div>
            </div>

            <form class="compose-form" id="replyForm">
                <input type="hidden" id="emailId" value="<?php echo htmlspecialchars($emailId); ?>">
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" value="Re: <?php echo htmlspecialchars($email['subject']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="body">Message</label>
                    <textarea id="body" required></textarea>
                </div>

                <div class="form-group prompt-input">
                    <label for="prompt">AI Instructions (Optional)</label>
                    <input type="text" id="prompt" placeholder="E.g., Make it professional and concise">
                </div>

                <div class="button-group">
                    <button type="button" class="ai-generate-btn" onclick="togglePrompt()">
                        <i class="fas fa-robot"></i> Generate with AI
                    </button>
                    <button type="submit" class="send-btn">
                        <i class="fas fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function togglePrompt() {
        const promptInput = document.querySelector('.prompt-input');
        promptInput.classList.toggle('visible');
        
        if (promptInput.classList.contains('visible')) {
            generateAIReply();
        }
    }

    async function generateAIReply() {
        const emailId = document.getElementById('emailId').value;
        const subject = document.getElementById('subject').value;
        const prompt = document.getElementById('prompt').value;
        
        try {
            const response = await fetch('endpoints/compose_reply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    subject: subject,
                    body: document.querySelector('.email-body').textContent,
                    prompt: prompt || 'Generate a professional reply'
                })
            });

            const data = await response.json();
            
            if (data.success && data.reply) {
                document.getElementById('body').value = data.reply.body;
            } else {
                alert('Failed to generate reply: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            alert('Error generating reply: ' + error.message);
        }
    }

    document.getElementById('replyForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        // Add your email sending logic here
        alert('Reply sent!'); // Replace with actual sending logic
    });
    </script>
</body>
</html> 