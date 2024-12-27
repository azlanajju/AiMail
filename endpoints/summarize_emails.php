<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

function fetchEmails($accessToken) {
    $endpoint = 'https://graph.microsoft.com/v1.0/me/messages';
    $params = http_build_query([
        '$top' => 10,
        '$select' => 'subject,bodyPreview,receivedDateTime',
        '$orderby' => 'receivedDateTime desc'
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
    
    if(curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

function generateSummary($emails) {
    $apiKey = 'AIzaSyBhXfHEDoZw41AmFmFCUFnQEOOimQiS_s8'; // Your Gemini API key
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

    // Prepare email data for summarization
    $emailContent = '';
    foreach ($emails['value'] as $email) {
        $emailContent .= "Subject: {$email['subject']}\n";
        $emailContent .= "Preview: {$email['bodyPreview']}\n\n";
    }

    $prompt = "Summarize these recent emails in a clear, concise way. Format the response in markdown with:
    - Use headers (##) for main sections
    - Bullet points for key items
    - Bold for important terms
    - Include a brief overview at the top

    Recent emails to summarize:\n\n" . $emailContent;
    
    $payload = json_encode([
        'contents' => [[
            'parts' => [[
                'text' => $prompt
            ]]
        ]]
    ]);

    $ch = curl_init($endpoint . '?key=' . urlencode($apiKey));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        throw new Exception(curl_error($ch));
    }
    
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }
    
    throw new Exception('Failed to generate summary from AI');
}

try {
    $emails = fetchEmails($_SESSION['access_token']);
    
    if (!isset($emails['value'])) {
        throw new Exception('Failed to fetch emails');
    }

    $summary = generateSummary($emails);
    
    echo json_encode([
        'summary' => nl2br(htmlspecialchars($summary))
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 