<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

function classifyEmails($emails) {
    $url = "https://generativelanguage.googleapis.com/v1/models/gemini-pro:generateContent";
    
    // Prepare email data for classification
    $emailData = array_map(function($email) {
        return [
            'id' => $email['id'],
            'subject' => $email['subject'] ?? 'No Subject',
            'preview' => substr($email['bodyPreview'] ?? '', 0, 200),
            'from' => $email['from']['emailAddress']['address'] ?? 'unknown'
        ];
    }, array_slice($emails['value'], 0, 30)); // Limit to 30 emails

    $prompt = <<<EOT
Analyze these emails and create exactly 5 categories. Follow these rules strictly:

1. Create these specific categories:
based on different projects and tasks 

2. Format the response as valid JSON like this:
{
    "categories": [
        {
            "name": "Project Communications",
            "description": "Emails related to ongoing projects",
            "emailIds": [0, 1, 2]
        }
    ]
}

3. Each email must be assigned to exactly one category
4. Use emailIds array to indicate which emails belong to each category
5. Provide a brief description for each category

Analyze these emails and categorize them accordingly.
EOT;

    $postData = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt . "\n\nEmails:\n" . json_encode($emailData, JSON_PRETTY_PRINT)]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 2048
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "x-goog-api-key: AIzaSyBhXfHEDoZw41AmFmFCUFnQEOOimQiS_s8",
            "Content-Type: application/json"
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        throw new Exception('Invalid API response');
    }

    $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'];
    
    // Extract JSON from response
    preg_match('/{.*}/s', $aiResponse, $matches);
    if (empty($matches)) {
        throw new Exception('No valid JSON found in response');
    }

    return json_decode($matches[0], true);
}

try {
    // Fetch emails
    $endpoint = 'https://graph.microsoft.com/v1.0/me/messages';
    $params = http_build_query([
        '$top' => 30,
        '$select' => 'id,subject,bodyPreview,from,receivedDateTime',
        '$orderby' => 'receivedDateTime desc',
        '$filter' => "isDraft eq false"
    ]);

    $ch = curl_init($endpoint . '?' . $params);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $_SESSION['access_token'],
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $emails = json_decode($response, true);
    
    if (!isset($emails['value'])) {
        throw new Exception('No emails found');
    }

    $classifications = classifyEmails($emails);
    
    echo json_encode([
        'status' => 'success',
        'classifications' => $classifications,
        'email_count' => count($emails['value'])
    ]);

} catch (Exception $e) {
    error_log('Classification Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?> 