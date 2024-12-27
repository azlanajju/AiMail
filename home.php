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
                    <h2><i class="fas fa-chart-bar"></i> Email Analytics</h2>
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
                        // Use Parsedown to render the markdown
                        echo $parsedown->text($_SESSION['summary']);
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
                    // Use marked.js to parse markdown
                    document.getElementById('summary-content').innerHTML = marked.parse(data.summary);
                    
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
    </script>

    <!-- Add marked.js for markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <style>
        body {
            background-color: #f5f7ff;
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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

        .summary-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(94, 100, 255, 0.1);
        }

        .summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eef0ff;
        }

        .summary-header h2 {
            color: #5e64ff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .refresh-button {
            background: #5e64ff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .refresh-button:hover {
            background: #4a4fff;
            transform: translateY(-1px);
        }

        .refresh-button:disabled {
            background: #c5c7ff;
            cursor: not-allowed;
            transform: none;
        }

        .loader-container {
            display: none;
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .loader {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #eef0ff;
            border-radius: 50%;
            border-top: 3px solid #5e64ff;
            animation: spin 1s linear infinite;
        }

        .loader-text {
            margin-top: 15px;
            color: #5e64ff;
            font-size: 14px;
        }

        .markdown-body {
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #eef0ff;
        }

        .markdown-body h1,
        .markdown-body h2,
        .markdown-body h3 {
            color: #5e64ff;
        }

        .markdown-body ul {
            list-style-type: none;
            padding-left: 0;
        }

        .markdown-body li {
            padding: 8px 0;
            border-bottom: 1px solid #eef0ff;
        }

        .markdown-body li:last-child {
            border-bottom: none;
        }

        .last-updated {
            color: #8f95ff;
            font-size: 12px;
            text-align: right;
            margin-top: 20px;
            font-style: italic;
        }

        .error-message {
            color: #ff4757;
            padding: 15px;
            background: #fff5f5;
            border-radius: 8px;
            margin: 10px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #ffe0e0;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html>
