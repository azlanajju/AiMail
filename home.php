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
            <div class="two-column-layout">
                <div class="column">
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

                <div class="column">
                    <div class="today-events-section">
                        <div class="section-header">
                            <h2><i class="fas fa-calendar-day"></i> Today's Schedule</h2>
                        </div>

                        <div class="events-loader-container">
                            <div class="loader"></div>
                            <div class="loader-text">Loading today's events...</div>
                        </div>

                        <div id="today-events" class="events-container">
                            <!-- Events will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($shouldRefreshSummary): ?>
        document.querySelector('.loader-container').style.display = 'block';
        fetchSummary();
        <?php endif; ?>
        fetchTodayEvents();
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

    function fetchTodayEvents() {
        document.querySelector('.events-loader-container').style.display = 'block';
        
        fetch('endpoints/get_calendar_events.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.events) {
                    renderTodayEvents(data.events);
                } else {
                    throw new Error(data.error || 'Failed to load events');
                }
            })
            .catch(error => {
                document.getElementById('today-events').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${error.message}
                    </div>`;
            })
            .finally(() => {
                document.querySelector('.events-loader-container').style.display = 'none';
            });
    }

    function renderTodayEvents(events) {
        const todayEvents = events.filter(event => {
            const eventDate = new Date(event.start.dateTime);
            const today = new Date();
            return eventDate.toDateString() === today.toDateString();
        });

        const container = document.getElementById('today-events');
        
        if (todayEvents.length === 0) {
            container.innerHTML = `
                <div class="no-events">
                    <i class="fas fa-calendar-check"></i>
                    <p>No events scheduled for today</p>
                </div>`;
            return;
        }

        const eventsHtml = todayEvents
            .sort((a, b) => new Date(a.start.dateTime) - new Date(b.start.dateTime))
            .map(event => `
                <div class="event-card ${isEventCurrent(event) ? 'current-event' : ''}">
                    <div class="event-time">
                        <i class="far fa-clock"></i>
                        ${formatEventTime(event.start.dateTime)} - ${formatEventTime(event.end.dateTime)}
                    </div>
                    <div class="event-details">
                        <h3>${event.subject}</h3>
                        ${event.location?.displayName ? `
                            <div class="event-location">
                                <i class="fas fa-map-marker-alt"></i> ${event.location.displayName}
                            </div>
                        ` : ''}
                        ${event.isOnlineMeeting ? `
                            <div class="online-meeting">
                                <i class="fas fa-video"></i> Online Meeting
                            </div>
                        ` : ''}
                    </div>
                </div>
            `).join('');

        container.innerHTML = eventsHtml;
    }

    function isEventCurrent(event) {
        const now = new Date();
        const start = new Date(event.start.dateTime);
        const end = new Date(event.end.dateTime);
        return now >= start && now <= end;
    }

    function formatEventTime(dateString) {
        return new Date(dateString).toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
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
            max-width: 100%;
        }

        .main-content {
            padding: 20px;
            max-width: 100%;
        }

        .two-column-layout {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            max-width: 1600px;
            margin: 0 auto;
        }

        .column {
            min-width: 0; /* Prevents overflow in flex/grid layouts */
        }

        .summary-section,
        .today-events-section {
            height: calc(100vh - 100px); /* Adjust based on your header/padding */
            overflow-y: auto;
            position: relative;
        }

        /* Responsive layout */
        @media (max-width: 1200px) {
            .two-column-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .summary-section,
            .today-events-section {
                height: auto;
                max-height: 600px;
            }
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

        .today-events-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(94, 100, 255, 0.1);
            margin-top: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eef0ff;
        }

        .section-header h2 {
            color: #5e64ff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5em;
        }

        .events-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .event-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #eef0ff;
            transition: all 0.2s ease;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(94, 100, 255, 0.1);
        }

        .current-event {
            border-left: 4px solid #5e64ff;
            background-color: #f8f9ff;
        }

        .event-time {
            color: #5e64ff;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .event-details h3 {
            margin: 0;
            color: #333;
            font-size: 1.1em;
        }

        .event-location, .online-meeting {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .no-events {
            text-align: center;
            padding: 30px;
            color: #666;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .no-events i {
            font-size: 2em;
            color: #5e64ff;
            opacity: 0.5;
        }

        .events-loader-container {
            display: none;
            text-align: center;
            padding: 20px;
        }

        /* Responsive design */
        @media (min-width: 768px) {
            .event-card {
                flex-direction: row;
                align-items: center;
            }

            .event-time {
                min-width: 180px;
            }
        }

        /* Animation for current event */
        @keyframes pulse {
            0% { border-color: #5e64ff; }
            50% { border-color: #8f93ff; }
            100% { border-color: #5e64ff; }
        }

        .current-event {
            animation: pulse 2s infinite;
        }

        /* Custom scrollbar for sections */
        .summary-section::-webkit-scrollbar,
        .today-events-section::-webkit-scrollbar {
            width: 6px;
        }

        .summary-section::-webkit-scrollbar-track,
        .today-events-section::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .summary-section::-webkit-scrollbar-thumb,
        .today-events-section::-webkit-scrollbar-thumb {
            background: #5e64ff;
            border-radius: 10px;
        }

        .summary-section::-webkit-scrollbar-thumb:hover,
        .today-events-section::-webkit-scrollbar-thumb:hover {
            background: #4a4fff;
        }

        /* Ensure consistent card heights */
        .event-card {
            min-height: 80px;
        }

        /* Add shadows to make sections stand out */
        .summary-section,
        .today-events-section {
            box-shadow: 0 4px 12px rgba(94, 100, 255, 0.1);
            border-radius: 12px;
            background: white;
        }

        /* Ensure headers stay at top when scrolling */
        .summary-header,
        .section-header {
            position: sticky;
            top: -100px;
            background: white;
            z-index: 10;
            padding-top: 15px;
            margin-top: -15px;
        }
    </style>
</body>
</html>
