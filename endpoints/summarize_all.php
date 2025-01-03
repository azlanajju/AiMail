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
    curl_close($ch);

    return json_decode($response, true);
}

function generateSummary($emails) {
    $API_KEY = 'YOUR_GEMINI_API_KEY';
    $GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

    // Prepare email data for summarization
    $emailContent = '';
    foreach ($emails['value'] as $email) {
        $emailContent .= "Subject: {$email['subject']}\n";
        $emailContent .= "Preview: {$email['bodyPreview']}\n\n";
    }

    $prompt = "Summarize these recent emails in a clear, concise way. Focus on key points and action items:\n\n" . $emailContent;

    $payload = json_encode([
        'contents' => [[
            'parts' => [[
                'text' => $prompt
            ]]
        ]]
    ]);

    $ch = curl_init($GEMINI_ENDPOINT . '?key=' . urlencode($API_KEY));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);

    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    return null;
}

try {
    // Fetch recent emails
    $emails = fetchEmails($_SESSION['access_token']);
    
    if (!isset($emails['value'])) {
        throw new Exception('Failed to fetch emails');
    }

    // Generate summary
    $summary = generateSummary($emails);
    
    if (!$summary) {
        throw new Exception('Failed to generate summary');
    }

    // Return formatted summary
    echo json_encode([
        'success' => true,
        'summary' => nl2br(htmlspecialchars($summary))
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}