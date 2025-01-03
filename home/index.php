<?php
session_start();
$activePage = "home";
if (!isset($_SESSION['access_token'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../vendor/autoload.php';
$parsedown = new Parsedown();

// Add summary and calendar timestamp checks
$summaryExpiration = 15 * 60; // 15 minutes in seconds
$calendarExpiration = 15 * 60; // 15 minutes in seconds

$shouldRefreshSummary = true;
$shouldRefreshCalendar = true;

// Check if summary needs refresh
if (isset($_SESSION['summary']) && isset($_SESSION['summary_timestamp'])) {
    $timeSinceLastSummary = time() - $_SESSION['summary_timestamp'];
    $shouldRefreshSummary = $timeSinceLastSummary > $summaryExpiration;
}

// Check if calendar needs refresh
if (isset($_SESSION['calendar_events']) && isset($_SESSION['calendar_timestamp'])) {
    $timeSinceLastCalendar = time() - $_SESSION['calendar_timestamp'];
    $shouldRefreshCalendar = $timeSinceLastCalendar > $calendarExpiration;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Compose - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/github-markdown-css/5.2.0/github-markdown.min.css">
    <link rel="stylesheet" href="./home.css">
    <link rel="icon" type="image/x-icon" href="../images/favocon.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <?php $activePage="home"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>

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
                        <?php if (isset($_SESSION['summary_timestamp']) && !$shouldRefreshSummary): ?>
                        <div class="last-updated">
                            Last updated: <?php 
                                $timestamp = new DateTime();
                                $timestamp->setTimestamp($_SESSION['summary_timestamp']);
                                $timestamp->setTimezone(new DateTimeZone('Asia/Kolkata'));
                                echo $timestamp->format('g:i A'); 
                            ?>
                        </div>
                        <?php endif; ?>
                        <div class="loader-container" style="display: none;">
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
                    </div>
                </div>

                <div class="column">
                    <div class="today-events-section">
                        <div class="section-header">
                            <h2><i class="fas fa-calendar-day"></i> Today's Schedule</h2>
                        </div>

                        <div class="events-loader-container" style="display: none;">
                            <div class="loader"></div>
                            <div class="loader-text">Loading today's events...</div>
                        </div>

                        <div id="today-events" class="events-container">
                            <?php 
                            if (isset($_SESSION['calendar_events']) && !$shouldRefreshCalendar) {
                                $events = $_SESSION['calendar_events'];
                                if (empty($events)) {
                                    echo '<div class="no-events">';
                                    echo '<i class="fas fa-calendar-check"></i>';
                                    echo '<p>No events scheduled for today</p>';
                                    echo '</div>';
                                } else {
                                    foreach ($events as $event) {
                                        // Your existing event rendering code here
                                        // ...
                                    }
                                }
                            }
                            ?>
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
        
        <?php if ($shouldRefreshCalendar): ?>
        document.querySelector('.events-loader-container').style.display = 'block';
        fetchTodayEvents();
        <?php endif; ?>
    });

    function fetchSummary() {
        const button = document.getElementById('refresh-btn');
        if (button) button.disabled = true;

        fetch('../endpoints/summarize_emails.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.summary) {
                    document.getElementById('summary-content').innerHTML = marked.parse(data.summary);
                    
                    // Convert time to IST
                    const date = new Date();
                    const options = { 
                        hour: 'numeric', 
                        minute: '2-digit', 
                        hour12: true,
                        timeZone: 'Asia/Kolkata'
                    };
                    const timeString = date.toLocaleTimeString('en-US', options);
                    
                    const lastUpdated = document.querySelector('.last-updated');
                    if (lastUpdated) {
                        lastUpdated.textContent = `Last updated: ${timeString}`;
                    } else {
                        const newLastUpdated = document.createElement('div');
                        newLastUpdated.className = 'last-updated';
                        newLastUpdated.textContent = `Last updated: ${timeString}`;
                        document.querySelector('.summary-section').insertBefore(newLastUpdated, document.querySelector('.loader-container'));
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

    function fetchTodayEvents() {
        document.querySelector('.events-loader-container').style.display = 'block';
        
        fetch('../endpoints/get_calendar_events.php')
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
            hour12: true,
            timeZone: 'Asia/Kolkata'
        });
    }

    function refreshSummary() {
        const summaryContent = document.getElementById('summary-content');
        const refreshBtn = document.getElementById('refresh-btn');
        
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> Refreshing...';
        }

        // Show loader
        document.querySelector('.loader-container').style.display = 'block';
        if (summaryContent) {
            summaryContent.style.opacity = '0.6';
        }

        fetch('../endpoints/summarize_emails.php')
            .then(response => response.json())
            .then(data => {
                if (data && data.summary) {
                    // Update the summary content
                    summaryContent.innerHTML = marked.parse(data.summary);
                    
                    // Update last updated time (IST)
                    const date = new Date();
                    const options = { 
                        hour: 'numeric', 
                        minute: '2-digit', 
                        hour12: true,
                        timeZone: 'Asia/Kolkata'
                    };
                    const timeString = date.toLocaleTimeString('en-US', options);
                    
                    const lastUpdated = document.querySelector('.last-updated');
                    if (lastUpdated) {
                        lastUpdated.textContent = `Last updated: ${timeString}`;
                    } else {
                        const newLastUpdated = document.createElement('div');
                        newLastUpdated.className = 'last-updated';
                        newLastUpdated.textContent = `Last updated: ${timeString}`;
                        document.querySelector('.summary-section').insertBefore(
                            newLastUpdated, 
                            document.querySelector('.loader-container')
                        );
                    }

                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'alert alert-success';
                    successMessage.innerHTML = '<i class="fas fa-check-circle"></i> Summary updated successfully';
                    successMessage.style.position = 'fixed';
                    successMessage.style.top = '20px';
                    successMessage.style.right = '20px';
                    successMessage.style.zIndex = '1000';
                    document.body.appendChild(successMessage);

                    // Remove success message after 3 seconds
                    setTimeout(() => {
                        successMessage.remove();
                    }, 3000);
                } else {
                    throw new Error(data.error || 'No summary available');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                summaryContent.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${error.message}
                    </div>`;
            })
            .finally(() => {
                // Hide loader and reset button
                document.querySelector('.loader-container').style.display = 'none';
                if (summaryContent) {
                    summaryContent.style.opacity = '1';
                }
                if (refreshBtn) {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                }
            });
    }

    // Add click event listener to refresh button
    document.addEventListener('DOMContentLoaded', function() {
        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', refreshSummary);
        }
    });

    // Optional: Auto-refresh every 15 minutes
    // setInterval(refreshSummary, 15 * 60 * 1000);
    </script>

    <!-- Add marked.js for markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</body>
</html>
