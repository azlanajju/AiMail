<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gemini API configuration
$API_KEY = 'AIzaSyBhXfHEDoZw41AmFmFCUFnQEOOimQiS_s8';
$GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

function getUserInfo($accessToken) {
    $graphEndpoint = 'https://graph.microsoft.com/v1.0/me';
    
    $ch = curl_init($graphEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function generateEmailReply($originalSubject, $originalBody, $customPrompt, $apiKey, $userName) {
    $prompt = "Generate a professional email reply with the following details:

Original Subject: {$originalSubject}
Original Message: {$originalBody}
Additional Instructions: {$customPrompt}
Sender Name: {$userName}

Please provide a reply in the following format:
Subject: [Your subject line]
Body: [Your message body]

Make sure to use the sender's name '{$userName}' in the signature.";

    // Prepare the payload
    $payload = json_encode([
        'contents' => [[
            'parts' => [[
                'text' => $prompt
            ]]
        ]]
    ]);

    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $GLOBALS['GEMINI_ENDPOINT'] . '?key=' . urlencode($apiKey));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch) || $httpCode >= 400) {
        $error = curl_error($ch) ?: "HTTP Error: $httpCode";
        curl_close($ch);
        return ['error' => $error];
    }

    curl_close($ch);

    $data = json_decode($response, true);
    $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$content) {
        return ['error' => 'No response generated'];
    }

    preg_match('/Subject:\s*(.+?)(?=\nBody:)/s', $content, $subjectMatch);
    preg_match('/Body:\s*(.+)$/s', $content, $bodyMatch);

    if (empty($subjectMatch) || empty($bodyMatch)) {
        return ['error' => 'Failed to parse response format'];
    }

    return [
        'success' => true,
        'reply' => [
            'subject' => trim($subjectMatch[1]),
            'body' => trim($bodyMatch[1])
        ]
    ];
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['access_token'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['subject']) || !isset($input['body']) || !isset($input['prompt'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    // Get user info from Azure AD
    $userInfo = getUserInfo($_SESSION['access_token']);
    $userName = $userInfo['displayName'] ?? 'User';

    // Generate reply with user's name
    $result = generateEmailReply(
        $input['subject'],
        $input['body'],
        $input['prompt'],
        $API_KEY,
        $userName
    );
    
    echo json_encode($result);
    exit;
}

// Handle preflight CORS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
} 