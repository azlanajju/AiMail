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
$endpoint = "https://graph.microsoft.com/v1.0/me/messages/$emailId";

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $_SESSION['access_token'],
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Failed to fetch email: ' . curl_error($ch)]);
    exit;
}

curl_close($ch);
$email = json_decode($response, true);

if (!$email || isset($email['error'])) {
    echo json_encode(['error' => $email['error']['message'] ?? 'Failed to fetch email']);
    exit;
}

echo json_encode($email); 