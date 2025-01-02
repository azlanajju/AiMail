<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Chat Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Include Sidebar -->
    <?php $activePage = 'home'; $path = '../'; include '../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="content-wrapper">
        <!-- Include Topbar -->
        <?php $path = '../'; include '../includes/topbar.php'; ?>

        <!-- Original Chat Interface (unchanged) -->
        <div class="main-container">
            <div class="chat-container">
                <div class="chat-messages" id="chat-messages">
                    <div class="message bot-message">
                        <div class="avatar bot-avatar">AI</div>
                        <div class="message-content">
                            Hello! I'm your email assistant. I can help you with:
                            <ul>
                                <li>Summarizing your recent emails</li>
                                <li>Finding specific emails</li>
                                <li>Checking important messages</li>
                                <li>Analyzing email content</li>
                            </ul>
                            How can I assist you today?
                        </div>
                    </div>
                </div>
                <div class="typing-indicator" id="typing-indicator">AI is thinking...</div>
                <div class="input-container">
                    <div class="input-box">
                        <textarea id="user-input" placeholder="Ask about your emails..." rows="1"></textarea>
                        <button id="send-button">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Your existing JavaScript remains unchanged -->
    <script>
        marked.setOptions({
            highlight: function(code, language) {
                if (language && hljs.getLanguage(language)) {
                    return hljs.highlight(code, { language: language }).value;
                }
                return hljs.highlightAuto(code).value;
            },
            breaks: true
        });

        const chatMessages = document.getElementById('chat-messages');
        const userInput = document.getElementById('user-input');
        const sendButton = document.getElementById('send-button');
        const typingIndicator = document.getElementById('typing-indicator');

        // Auto-resize textarea
        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
        });

        function addMessage(message, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user-message' : 'bot-message'} d-flex align-items-start mb-3`;
            
            const avatar = document.createElement('div');
            avatar.className = `avatar ${isUser ? 'user-avatar' : 'bot-avatar'} bg-primary text-white`;
            avatar.textContent = isUser ? 'U' : 'AI';

            const messageContent = document.createElement('div');
            messageContent.className = 'message-content  rounded p-2 w-75';
            
            if (isUser) {
                messageContent.textContent = message;
            } else {
                messageContent.innerHTML = marked.parse(message);
            }

            messageDiv.appendChild(avatar);
            messageDiv.appendChild(messageContent);

            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            if (!isUser) {
                messageContent.querySelectorAll('pre code').forEach((block) => {
                    hljs.highlightBlock(block);
                });
            }
        }

        async function sendMessage() {
            const message = userInput.value.trim();
            if (!message) return;

            addMessage(message, true);
            userInput.value = '';
            userInput.style.height = 'auto';

            typingIndicator.style.display = 'block';

            try {
                const response = await fetch('process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ question: message })
                });

                const data = await response.json();
                typingIndicator.style.display = 'none';

                if (data.error) {
                    addMessage('Sorry, something went wrong. Please try again.');
                } else {
                    addMessage(data.answer);
                }
            } catch (error) {
                typingIndicator.style.display = 'none';
                addMessage('Sorry, there was an error processing your request.');
            }
        }

        sendButton.addEventListener('click', sendMessage);
        userInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
                chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to the new message
            }
        });

        userInput.focus();
    </script>

    <!-- Only add minimal necessary styles for sidebar/topbar integration -->
    <style>
    .content-wrapper {
        margin-left: 250px;
        padding-top: 60px;
    }
    </style>
</body>
</html>