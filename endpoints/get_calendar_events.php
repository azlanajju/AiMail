<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

function fetchCalendarEvents($accessToken) {
    // Set timezone to prevent date issues
    date_default_timezone_set('UTC');
    
    $endpoint = 'https://graph.microsoft.com/v1.0/me/calendar/events';
    
    // Format dates properly for Microsoft Graph API
    $today = date('Y-m-d\TH:i:s\Z');
    $nextMonth = date('Y-m-d\TH:i:s\Z', strtotime('+30 days'));
    
    $params = http_build_query([
        '$select' => 'subject,start,end,location,bodyPreview,isOnlineMeeting,onlineMeeting',
        '$orderby' => 'start/dateTime',
        '$filter' => "start/dateTime ge '$today'",
        '$top' => 50
    ]);

    $url = $endpoint . '?' . $params;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Prefer: outlook.timezone="UTC"'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Only for development
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
    ]);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Curl error: " . $error);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $responseData = json_decode($response, true);
        $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
        throw new Exception("API error (HTTP $httpCode): $errorMessage");
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("JSON decode error: " . json_last_error_msg());
    }
    
    return $data;
}

try {
    // Log access token length for debugging (don't log the full token)
    error_log("Access token length: " . strlen($_SESSION['access_token']));
    
    $events = fetchCalendarEvents($_SESSION['access_token']);
    
    if (!isset($events['value'])) {
        throw new Exception('No events data in response');
    }
    
    // Process events to ensure consistent format
    $processedEvents = array_map(function($event) {
        return [
            'subject' => $event['subject'] ?? 'Untitled Event',
            'start' => $event['start'] ?? null,
            'end' => $event['end'] ?? null,
            'location' => $event['location'] ?? null,
            'bodyPreview' => $event['bodyPreview'] ?? '',
            'isOnlineMeeting' => $event['isOnlineMeeting'] ?? false,
            'onlineMeeting' => $event['onlineMeeting'] ?? null
        ];
    }, $events['value']);
    
    echo json_encode([
        'success' => true,
        'events' => $processedEvents
    ]);

} catch (Exception $e) {
    error_log("Calendar API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'details' => 'Check server logs for more information'
    ]);
} 