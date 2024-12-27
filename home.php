<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}

require_once 'vendor/autoload.php';
$parsedown = new Parsedown();

// Add summary timestamp check
$summaryExpiration = 5 * 60; // 5 minutes in seconds
$shouldRefreshSummary = true;

if (isset($_SESSION['summary']) && isset($_SESSION['summary_timestamp'])) {
    $timeSinceLastSummary = time() - $_SESSION['summary_timestamp'];
    $shouldRefreshSummary = $timeSinceLastSummary > $summaryExpiration;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="summary-section">
                <div class="summary-header">
                    <h2><i class="fas fa-envelope"></i> Email Summary</h2>
                    <button id="refresh-btn" class="refresh-button" onclick="refreshSummary()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>

                <div class="loader-container">
                    <div class="loader"></div>
                    <div class="loader-text">Analyzing your emails...</div>
                </div>

                <div id="summary-content" class="markdown-body">
                    <?php 
                    if (isset($_SESSION['summary']) && !$shouldRefreshSummary) {
                        echo $_SESSION['summary'];
                    }
                    ?>
                </div>

                <?php if (isset($_SESSION['summary_timestamp']) && !$shouldRefreshSummary): ?>
                <div class="last-updated">
                    Last updated: <?php echo date('g:i A', $_SESSION['summary_timestamp']); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($shouldRefreshSummary): ?>
        document.querySelector('.loader-container').style.display = 'block';
        fetchSummary();
        <?php endif; ?>
    });

    function fetchSummary() {
        const button = document.getElementById('refresh-btn');
        if (button) button.disabled = true;

        fetch('endpoints/summarize_emails.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data && data.summary) {
                    const markdownContent = convertToMarkdown(data.summary);
                    document.getElementById('summary-content').innerHTML = markdownContent;
                    
                    // Update last updated time
                    const timeString = new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
                    const lastUpdated = document.querySelector('.last-updated');
                    if (lastUpdated) {
                        lastUpdated.textContent = `Last updated: ${timeString}`;
                    } else {
                        const newLastUpdated = document.createElement('div');
                        newLastUpdated.className = 'last-updated';
                        newLastUpdated.textContent = `Last updated: ${timeString}`;
                        document.querySelector('.summary-section').appendChild(newLastUpdated);
                    }
                } else {
                    throw new Error(data.error || 'No summary available');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('summary-content').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${error.message}
                    </div>`;
            })
            .finally(() => {
                document.querySelector('.loader-container').style.display = 'none';
                if (button) button.disabled = false;
            });
    }

    function refreshSummary() {
        document.querySelector('.loader-container').style.display = 'block';
        document.getElementById('summary-content').innerHTML = '';
        fetchSummary();
    }

    function convertToMarkdown(text) {
        return text
            .replace(/^# (.*$)/gm, '<h1>$1</h1>')
            .replace(/^## (.*$)/gm, '<h2>$1</h2>')
            .replace(/^### (.*$)/gm, '<h3>$1</h3>')
            .replace(/^\* (.*$)/gm, '<li>$1</li>')
            .replace(/^- (.*$)/gm, '<li>$1</li>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>')
            .replace(/\n/g, '<br>');
    }
    </script>

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

        .refresh-button {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .refresh-button:hover {
            background: #1557b0;
        }

        .refresh-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .last-updated {
            color: #666;
            font-size: 12px;
            text-align: right;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</body>
</html>
