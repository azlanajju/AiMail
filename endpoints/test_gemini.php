<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once ( '../.env');

// Load environment variables
(new DotEnv('../.env'))->load();

$apiKey = $_ENV['GEMINI_API_KEY'];
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;

$data = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => "Please respond with 'Hello World' if you can receive this message."
                ]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";

if(curl_errno($ch)) {
    echo "Curl Error: " . curl_error($ch);
}

curl_close($ch); 