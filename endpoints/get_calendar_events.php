<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

function fetchCalendarEvents($accessToken) {
    $endpoint = 'https://graph.microsoft.com/v1.0/me/calendar/events';
    $today = date('Y-m-d');
    $nextMonth = date('Y-m-d', strtotime('+30 days'));
    
    $params = http_build_query([
        '$select' => 'subject,start,end,location,bodyPreview,isOnlineMeeting,onlineMeeting',
        '$orderby' => 'start/dateTime',
        '$filter' => "start/dateTime ge '$today' and end/dateTime le '$nextMonth'",
        '$top' => 50
    ]);

    $ch = curl_init($endpoint . '?' . $params);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

try {
    $events = fetchCalendarEvents($_SESSION['access_token']);
    
    if (!isset($events['value'])) {
        throw new Exception('Failed to fetch calendar events');
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events['value']
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 