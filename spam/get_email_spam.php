<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Email ID not provided']);
    exit;
}

$emailId = $_GET['id'];
$accessToken = $_SESSION['access_token'];

// Function to get email details
function getEmailDetails($accessToken, $emailId) {
    $endpoint = "https://graph.microsoft.com/v1.0/me/messages/{$emailId}";
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ['error' => $error];
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// Get email details and return JSON response
header('Content-Type: application/json');
$email = getEmailDetails($accessToken, $emailId);

if (isset($email['error'])) {
    echo json_encode(['error' => $email['error']]);
} else {
    echo json_encode($email);
} 