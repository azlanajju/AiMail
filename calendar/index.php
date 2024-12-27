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
    <link rel="stylesheet" href="./calendar.css">
</head>
<body>
    <?php $activePage="calendar"; $path="../"; include '../includes/sidebar.php'; ?>
    <?php $path="../"; include '../includes/topbar.php'; ?>

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
        
        fetch('../endpoints/get_calendar_events.php')
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

</body>
</html> 