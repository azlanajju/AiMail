<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    // Revoke the access token
    $endpoint = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout';
    
    // Clear session
    session_destroy();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 