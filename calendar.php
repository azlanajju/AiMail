<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Calendar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="container">
        <div class="main-content">
            <div class="calendar-header">
                <h2><i class="fas fa-calendar-alt"></i> My Calendar</h2>
                <div class="view-controls">
                    <button class="view-btn active" data-view="upcoming">
                        <i class="fas fa-list"></i> Upcoming
                    </button>
                    <button class="view-btn" data-view="today">
                        <i class="fas fa-calendar-day"></i> Today
                    </button>
                </div>
            </div>

            <div class="loader-container">
                <div class="loader"></div>
                <div class="loader-text">Loading calendar events...</div>
            </div>

            <div id="events-container"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        fetchEvents();

        // View switching
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterEvents(this.dataset.view);
            });
        });
    });

    function fetchEvents() {
        document.querySelector('.loader-container').style.display = 'block';
        
        fetch('endpoints/get_calendar_events.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.events) {
                    renderEvents(data.events);
                } else {
                    throw new Error(data.error || 'Failed to load events');
                }
            })
            .catch(error => {
                document.getElementById('events-container').innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> 
                        ${error.message}
                    </div>`;
            })
            .finally(() => {
                document.querySelector('.loader-container').style.display = 'none';
            });
    }

    function renderEvents(events) {
        const container = document.getElementById('events-container');
        const today = new Date().setHours(0, 0, 0, 0);
        let currentDate = null;

        const eventsByDate = events.reduce((acc, event) => {
            const date = new Date(event.start.dateTime).toDateString();
            if (!acc[date]) {
                acc[date] = [];
            }
            acc[date].push(event);
            return acc;
        }, {});

        let html = '';
        
        Object.entries(eventsByDate).forEach(([date, dayEvents]) => {
            html += `
                <div class="date-group">
                    <div class="date-header">
                        <i class="fas fa-calendar"></i>
                        ${formatDate(date)}
                    </div>
                    <div class="events-list">
                        ${dayEvents.map(event => `
                            <div class="event-card">
                                <div class="event-time">
                                    ${formatTime(event.start.dateTime)} - ${formatTime(event.end.dateTime)}
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
                                    <div class="event-preview">${event.bodyPreview}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html || '<div class="no-events">No upcoming events</div>';
    }

    function filterEvents(view) {
        // Implement view filtering logic here
        fetchEvents(); // For now, just refresh all events
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        if (date.toDateString() === today.toDateString()) {
            return 'Today';
        } else if (date.toDateString() === tomorrow.toDateString()) {
            return 'Tomorrow';
        } else {
            return date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                month: 'long', 
                day: 'numeric' 
            });
        }
    }

    function formatTime(dateString) {
        return new Date(dateString).toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }
    </script>

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

        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .calendar-header h2 {
            color: #5e64ff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-controls {
            display: flex;
            gap: 10px;
        }

        .view-btn {
            background: white;
            border: 1px solid #5e64ff;
            color: #5e64ff;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .view-btn.active {
            background: #5e64ff;
            color: white;
        }

        .date-group {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(94, 100, 255, 0.1);
        }

        .date-header {
            background: #5e64ff;
            color: white;
            padding: 15px 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .events-list {
            padding: 20px;
        }

        .event-card {
            display: flex;
            gap: 20px;
            padding: 15px;
            border-bottom: 1px solid #eef0ff;
        }

        .event-card:last-child {
            border-bottom: none;
        }

        .event-time {
            min-width: 140px;
            color: #5e64ff;
            font-weight: 500;
        }

        .event-details h3 {
            margin: 0 0 10px 0;
            color: #333;
        }

        .event-location, .online-meeting {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .event-preview {
            color: #666;
            font-size: 14px;
            margin-top: 10px;
        }

        .loader-container {
            display: none;
            text-align: center;
            padding: 40px;
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

        .no-events {
            text-align: center;
            padding: 40px;
            color: #666;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(94, 100, 255, 0.1);
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</body>
</html> 