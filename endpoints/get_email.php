<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Email ID not provided']);
    exit;
}

$emailId = $_GET['id'];
$accessToken = $_SESSION['access_token'];

// Debug logging
error_log('Fetching email with ID: ' . $emailId);

$endpoint = "https://graph.microsoft.com/v1.0/me/messages/$emailId";
$params = http_build_query([
    '$select' => 'id,subject,body,from,receivedDateTime,hasAttachments,attachments'
]);

$ch = curl_init($endpoint . '?' . $params);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);

if(curl_errno($ch)) {
    error_log('Curl error: ' . curl_error($ch));
    echo json_encode(['error' => curl_error($ch)]);
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Debug logging
error_log('API Response Code: ' . $httpCode);
error_log('API Response: ' . $response);

$data = json_decode($response, true);

if (isset($data['error'])) {
    error_log('API Error: ' . print_r($data['error'], true));
    echo json_encode(['error' => $data['error']['message'] ?? 'Unknown error']);
    exit;
}

echo json_encode($data); 