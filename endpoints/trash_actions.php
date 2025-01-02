<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['action']) || !isset($data['emailId'])) {
        throw new Exception('Missing required parameters');
    }

    $accessToken = $_SESSION['access_token'];
    $emailId = $data['emailId'];

    switch ($data['action']) {
        case 'delete':
            // Permanently delete the email
            $endpoint = "https://graph.microsoft.com/v1.0/me/messages/{$emailId}";
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => "DELETE",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 204) { // 204 is success for DELETE
                throw new Exception('Failed to delete email');
            }
            break;

        case 'restore':
            // Move email back to inbox
            $endpoint = "https://graph.microsoft.com/v1.0/me/messages/{$emailId}/move";
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'destinationId' => 'inbox'
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 201) { // 201 is success for POST
                throw new Exception('Failed to restore email');
            }
            break;

        default:
            throw new Exception('Invalid action');
    }

    echo json_encode([
        'success' => true,
        'action' => $data['action']
    ]);

} catch (Exception $e) {
    error_log('Trash action error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'success' => false
    ]);
} 