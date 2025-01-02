<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['emailIds']) || empty($data['emailIds'])) {
        throw new Exception('No emails selected');
    }

    $accessToken = $_SESSION['access_token'];
    $success = true;
    $errors = [];

    foreach ($data['emailIds'] as $emailId) {
        // Move to deleted items instead of permanent deletion
        $endpoint = "https://graph.microsoft.com/v1.0/me/messages/$emailId/move";
        
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'destinationId' => 'deleteditems'
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if ($httpCode !== 201) {
            $success = false;
            $errors[] = "Failed to delete email $emailId";
        }
        
        curl_close($ch);
    }

    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception(implode(', ', $errors));
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 