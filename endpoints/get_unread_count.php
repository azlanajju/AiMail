<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

function getUnreadCount($accessToken) {
    $endpoint = 'https://graph.microsoft.com/v1.0/me/mailFolders/inbox/messages?$filter=isRead eq false&$count=true&$top=1';
    
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Prefer: outlook.body-content-type="text"',
            'ConsistencyLevel: eventual'
        ]
    ]);
    
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        error_log('Error fetching unread count: ' . curl_error($ch));
        return 0;
    }
    
    curl_close($ch);
    $data = json_decode($response, true);
    
    return $data['@odata.count'] ?? 0;
}

echo json_encode([
    'count' => getUnreadCount($_SESSION['access_token'])
]); 