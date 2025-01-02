<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();
if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$apiKey = 'AIzaSyBhXfHEDoZw41AmFmFCUFnQEOOimQiS_s8';

function fetchRecentEmails($accessToken) {
    $endpoint = 'https://graph.microsoft.com/v1.0/me/messages';
    $params = http_build_query([
        '$top' => 50,
        '$select' => 'subject,bodyPreview,from,receivedDateTime,importance',
        '$orderby' => 'receivedDateTime desc',
        '$filter' => "isDraft eq false"
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
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['value'] ?? [];
}

function formatEmailsForContext($emails) {
    $context = "Here are the recent emails:\n\n";
    foreach ($emails as $index => $email) {
        $date = new DateTime($email['receivedDateTime']);
        $context .= "Email " . ($index + 1) . ":\n";
        $context .= "From: " . $email['from']['emailAddress']['name'] . "\n";
        $context .= "Subject: " . $email['subject'] . "\n";
        $context .= "Preview: " . $email['bodyPreview'] . "\n";
        $context .= "Date: " . $date->format('Y-m-d H:i:s') . "\n";
        $context .= "Importance: " . $email['importance'] . "\n\n";
    }
    return $context;
}

function getGeminiResponse($emailContext, $userQuestion, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent";

    $systemPrompt = "You are an AI-powered email assistant. Your role is to assist users in analyzing, interpreting, and responding to email content.  
    Using the email context provided, answer all questions as accurately as possible. 
    
    - Always base your responses strictly on the details in the emails.
    - Keep your responses concise, clear, and precise.
    - Ensure that your answers are easy to read and understand.
    - Provide relevant information without introducing external details or assumptions.
    
    Your goal is to offer insightful and helpful replies to every question about the emails presented.";
    
    $fullPrompt = $systemPrompt . "\n\n" . $emailContext . "\n\nUser Question: " . $userQuestion;

    $postData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $fullPrompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1024,
        ]
    ];

    $headers = [
        "x-goog-api-key: $apiKey",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new Exception('Failed to connect to API: ' . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("API Error: HTTP code $httpCode");
    }

    $result = json_decode($response, true);
    return $result['candidates'][0]['content']['parts'][0]['text']
           ?? throw new Exception('Unexpected response format');
}

try {
    $rawData = file_get_contents('php://input');
    if (!$rawData) {
        throw new Exception('No input received');
    }

    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON data');
    }

    if (empty($data['question'])) {
        throw new Exception('Question is required');
    }

    // Fetch recent emails
    $recentEmails = fetchRecentEmails($_SESSION['access_token']);
    if (empty($recentEmails)) {
        throw new Exception('No emails found or failed to fetch emails');
    }

    // Format emails for context
    $emailContext = formatEmailsForContext($recentEmails);

    // Get AI response based on emails
    $response = getGeminiResponse($emailContext, $data['question'], $apiKey);

    echo json_encode([
        'status' => 'success',
        'answer' => $response,
        'email_count' => count($recentEmails),
        'conversation_id' => session_id()
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>