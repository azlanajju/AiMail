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
<link rel="stylesheet" href="./compose.css">
</head>
<body>
<?php $activePage="compose"; $path="../"; include '../includes/sidebar.php'; ?>
<?php $path="../"; include '../includes/topbar.php'; ?>    
    <div class="container">
        <div class="main-content">
            <div class="compose-header">
                <h1><i class="fas fa-pen"></i> Compose Email</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                    <i class="fas <?php echo strpos($message, 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

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
                    <div class="character-count">0 characters</div>
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

    <script>
        // Add character count functionality
        const textarea = document.getElementById('body');
        const charCount = document.querySelector('.character-count');
        
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = `${count} character${count !== 1 ? 's' : ''}`;
        });

        document.addEventListener('DOMContentLoaded', function() {
            const aiButton = document.createElement('button');
            aiButton.type = 'button';
            aiButton.className = 'ai-compose-btn';
            aiButton.innerHTML = '<i class="fas fa-robot"></i> AI Assist';
            
            const aiOptions = document.createElement('div');
            aiOptions.className = 'ai-options';
            aiOptions.innerHTML = `
                <div class="ai-prompt-container">
                    <textarea placeholder="Describe the email you want to write..." class="ai-prompt"></textarea>
                    <div class="ai-controls">
                        <select class="ai-tone">
                            <option value="professional">Professional</option>
                            <option value="friendly">Friendly</option>
                            <option value="formal">Formal</option>
                            <option value="casual">Casual</option>
                        </select>
                        <select class="ai-length">
                            <option value="medium">Medium Length</option>
                            <option value="short">Short</option>
                            <option value="long">Long</option>
                        </select>
                        <button class="ai-generate">Generate</button>
                    </div>
                </div>
            `;
            
            document.querySelector('.button-group').prepend(aiButton);
            document.querySelector('.compose-form').appendChild(aiOptions);
            
            aiButton.addEventListener('click', function() {
                aiOptions.classList.toggle('show');
            });
            
            document.querySelector('.ai-generate').addEventListener('click', async function() {
                const prompt = document.querySelector('.ai-prompt').value;
                const tone = document.querySelector('.ai-tone').value;
                const length = document.querySelector('.ai-length').value;
                
                if (!prompt) {
                    alert('Please enter a prompt');
                    return;
                }
                
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
                
                try {
                    const response = await fetch('../endpoints/ai_compose.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'compose',
                            prompt,
                            tone,
                            length
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        document.getElementById('body').value = data.content;
                        
                        // Also get a subject suggestion
                        const subjectResponse = await fetch('../endpoints/ai_compose.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                action: 'suggest_subject',
                                prompt: data.content
                            })
                        });
                        
                        const subjectData = await subjectResponse.json();
                        if (subjectData.success) {
                            document.getElementById('subject').value = subjectData.subject;
                        }
                        
                        aiOptions.classList.remove('show');
                    } else {
                        alert(data.error || 'Failed to generate email');
                    }
                } catch (error) {
                    alert('Error generating email');
                    console.error(error);
                } finally {
                    this.disabled = false;
                    this.innerHTML = 'Generate';
                }
            });
        });
    </script>
</body>
</html>