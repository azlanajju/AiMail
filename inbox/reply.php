<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: ../index.php');
    exit;
}

// Get email ID from URL
$emailId = $_GET['id'] ?? '';
if (empty($emailId)) {
    header('Location: ./');
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
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9ff;
        }

        .container {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
        }

        .compose-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(94, 100, 255, 0.1);
        }

        .compose-header {
            padding: 20px;
            border-bottom: 1px solid #eef0ff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .compose-header h2 {
            margin: 0;
            color: #5e64ff;
            font-size: 1.5em;
        }

        .original-email {
            background: #f8f9ff;
            margin: 20px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eef0ff;
        }

        .original-email strong {
            color: #5e64ff;
            display: inline-block;
            width: 80px;
        }

        .email-body {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eef0ff;
            color: #666;
        }

        .compose-form {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #5e64ff;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #eef0ff;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #5e64ff;
            box-shadow: 0 0 0 3px rgba(94, 100, 255, 0.1);
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .button-group {
            display: flex;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #eef0ff;
            background: #f8f9ff;
            border-radius: 0 0 12px 12px;
        }

        .ai-generate-btn,
        .send-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .ai-generate-btn {
            background: #f0f7ff;
            color: #5e64ff;
        }

        .ai-generate-btn:hover {
            background: #e5f0ff;
        }

        .send-btn {
            background: #5e64ff;
            color: white;
        }

        .send-btn:hover {
            background: #4b51cc;
        }

        .prompt-input {
            display: none;
            margin-top: 10px;
        }

        .prompt-input.visible {
            display: block;
        }

        .prompt-input input {
            width: 100%;
            padding: 10px;
            border: 1px solid #eef0ff;
            border-radius: 8px;
            font-size: 14px;
        }

        .prompt-input input:focus {
            outline: none;
            border-color: #5e64ff;
            box-shadow: 0 0 0 3px rgba(94, 100, 255, 0.1);
        }

        .prompt-container {
            display: flex;
            gap: 10px;
        }

        .prompt-container input {
            flex: 1;
        }

        .generate-btn {
            padding: 10px 20px;
            background: #5e64ff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .generate-btn:hover {
            background: #4b51cc;
        }

        .generate-btn i {
            font-size: 14px;
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
                padding: 10px;
            }

            .compose-container {
                margin: 10px;
            }
        }
    </style>
</head>
<body>
    <?php $activePage="inbox"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>
    
    <div class="container">
        <div class="compose-container">
            <div class="compose-header">
                <i class="fas fa-reply"></i>
                <h2>Compose Reply</h2>
            </div>

            <div class="original-email">
                <strong>From:</strong> <?php echo htmlspecialchars($email['from']['emailAddress']['address']); ?><br>
                <strong>Subject:</strong> <?php echo htmlspecialchars($email['subject']); ?><br>
                <strong>Received:</strong> <?php echo (new DateTime($email['receivedDateTime']))->format('M j, g:i A'); ?><br>
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
                    <label for="prompt">AI Instructions</label>
                    <div class="prompt-container">
                        <input type="text" id="prompt" placeholder="E.g., Make it professional and concise">
                        <button type="button" class="generate-btn" onclick="generateAIReply()">
                            <i class="fas fa-wand-magic-sparkles"></i> Generate
                        </button>
                    </div>
                </div>
            </form>

            <div class="button-group">
                <button type="button" class="ai-generate-btn" onclick="togglePrompt()">
                    <i class="fas fa-robot"></i> Generate with AI
                </button>
                <button type="submit" class="send-btn" form="replyForm">
                    <i class="fas fa-paper-plane"></i> Send Reply
                </button>
            </div>
        </div>
    </div>

    <script>
    function togglePrompt() {
        const promptInput = document.querySelector('.prompt-input');
        promptInput.classList.toggle('visible');
        
        if (promptInput.classList.contains('visible')) {
            document.getElementById('prompt').focus();
        }
    }

    async function generateAIReply() {
        const emailId = document.getElementById('emailId').value;
        const subject = document.getElementById('subject').value;
        const prompt = document.getElementById('prompt').value;
        const generateBtn = document.querySelector('.generate-btn');
        
        try {
            // Disable button and show loading state
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            
            const response = await fetch('../endpoints/compose_reply.php', {
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
        } finally {
            // Reset button state
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Generate';
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