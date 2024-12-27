<?php
session_start();

// Load environment variables
class DotEnv {
    protected $path;

    public function __construct(string $path) {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    public function load(): void {
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env file
(new DotEnv(__DIR__ . '/../.env'))->load();

class EmailSummarizer {
    private $geminiApiKey;

    public function __construct() {
        $this->geminiApiKey = $_ENV['GEMINI_API_KEY'];
    }

    private function summarizeWithGemini($emailContent) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->geminiApiKey;

        $prompt = "Please provide a concise summary of these emails, focusing on key points and common themes. Here are the emails:\n\n" . $emailContent;

        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
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

        if (curl_errno($ch)) {
            throw new Exception('Error calling Gemini API: ' . curl_error($ch));
        }

        curl_close($ch);

        $result = json_decode($response, true);
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No summary generated';
    }

    public function generateSummary($emails) {
        try {
            if (empty($emails)) {
                throw new Exception('No emails provided');
            }

            $emailContent = '';
            foreach ($emails as $email) {
                $emailContent .= "Subject: " . ($email['subject'] ?? 'No subject') . "\n";
                $emailContent .= "Body: " . ($email['body'] ?? 'No body') . "\n\n";
            }

            $summary = $this->summarizeWithGemini($emailContent);

            return [
                'success' => true,
                'summary' => $summary
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

// Handle the request
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['emails']) || !is_array($input['emails'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid or missing email data'
    ]);
    exit;
}

$summarizer = new EmailSummarizer();
echo json_encode($summarizer->generateSummary($input['emails']));
