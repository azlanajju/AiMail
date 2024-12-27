<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['access_token'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

class AIComposer {
    private $apiKey;
    private $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

    public function __construct() {
        $this->apiKey = 'AIzaSyBhXfHEDoZw41AmFmFCUFnQEOOimQiS_s8'; // Your Gemini API key
    }

    public function generateEmail($prompt, $tone = 'professional', $length = 'medium') {
        try {
            $systemPrompt = $this->getSystemPrompt($tone, $length);
            $fullPrompt = $systemPrompt . "\n\nUser request: " . $prompt;
            
            $payload = json_encode([
                'contents' => [[
                    'parts' => [[
                        'text' => $fullPrompt
                    ]]
                ]]
            ]);

            $ch = curl_init($this->endpoint . '?key=' . urlencode($this->apiKey));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            
            if(curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Invalid response format from AI');
            }

            return [
                'success' => true,
                'content' => $result['candidates'][0]['content']['parts'][0]['text'],
                'tone' => $tone,
                'length' => $length
            ];

        } catch (Exception $e) {
            error_log('AI Compose Error: ' . $e->getMessage());
            return ['error' => 'Failed to generate email: ' . $e->getMessage()];
        }
    }

    private function getSystemPrompt($tone, $length) {
        $toneGuide = match($tone) {
            'friendly' => 'Write in a warm, approachable, and conversational tone',
            'formal' => 'Write in a strictly professional and formal tone',
            'casual' => 'Write in a relaxed and informal tone',
            default => 'Write in a balanced professional tone'
        };

        $lengthGuide = match($length) {
            'short' => 'Keep the response concise and brief (around 100 words)',
            'long' => 'Provide a detailed and comprehensive response (around 300 words)',
            default => 'Provide a moderately detailed response (around 200 words)'
        };

        return "You are an expert email composer. Create a professional email with the following specifications:

1. {$toneGuide}
2. {$lengthGuide}
3. Include appropriate greeting and closing
4. Format with clear paragraphs
5. Focus on clarity and effectiveness
6. Maintain professional email etiquette
7. Be direct and purposeful
8. Use appropriate business language";
    }

    public function suggestSubject($emailBody) {
        try {
            $prompt = "Generate a concise, professional email subject line for the following email content. Return only the subject line, nothing else:\n\n" . $emailBody;
            
            $payload = json_encode([
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt
                    ]]
                ]]
            ]);

            $ch = curl_init($this->endpoint . '?key=' . urlencode($this->apiKey));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json']
            ]);

            $response = curl_exec($ch);
            
            if(curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Invalid response format from AI');
            }

            return [
                'success' => true,
                'subject' => trim($result['candidates'][0]['content']['parts'][0]['text'])
            ];

        } catch (Exception $e) {
            error_log('Subject Generation Error: ' . $e->getMessage());
            return ['error' => 'Failed to generate subject: ' . $e->getMessage()];
        }
    }
}

try {
    // Handle incoming requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['prompt'])) {
            throw new Exception('No prompt provided');
        }

        $composer = new AIComposer();
        
        // Handle different request types
        switch($data['action'] ?? 'compose') {
            case 'compose':
                $result = $composer->generateEmail(
                    $data['prompt'],
                    $data['tone'] ?? 'professional',
                    $data['length'] ?? 'medium'
                );
                break;
                
            case 'suggest_subject':
                $result = $composer->suggestSubject($data['prompt']);
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
        echo json_encode($result);
        exit;
    }

    throw new Exception('Invalid request method');

} catch (Exception $e) {
    error_log('AI Compose Error: ' . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
} 