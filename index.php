<?php
require_once 'autoload.php';

// For loading .env file
class DotEnv {
    protected $path;

    public function __construct(string $path) {
        if(!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    public function load() :void {
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

// Load environment variables
(new DotEnv(__DIR__ . '/.env'))->load();

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model;
use GuzzleHttp\Client;

session_start();

// If already authenticated, redirect to home
if (isset($_SESSION['access_token'])) {
    header('Location: home.php');
    exit;
}

class OutlookMailFetcher {
    private $clientId;
    private $clientSecret;
    private $tenantId;
    private $scopes = [
        'offline_access',
        'https://graph.microsoft.com/Mail.Read',
        'https://graph.microsoft.com/User.Read',
        'https://graph.microsoft.com/Calendars.Read',
        'https://graph.microsoft.com/Calendars.ReadBasic'
    ];

    public function __construct() {
        $this->clientId = $_ENV['CLIENT_ID'];
        $this->clientSecret = $_ENV['CLIENT_SECRET'];
        $this->tenantId = $_ENV['TENANT_ID'];
    }

    private function makePostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return json_decode($response, true);
    }

    public function getDeviceCode() {
        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/devicecode';
        $data = [
            'client_id' => $this->clientId,
            'scope' => implode(' ', $this->scopes)
        ];

        return $this->makePostRequest($url, $data);
    }

    public function pollForToken($deviceCode) {
        $url = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
        $data = [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:device_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'device_code' => $deviceCode
        ];

        return $this->makePostRequest($url, $data);
    }
}

$mailFetcher = new OutlookMailFetcher();

// If we don't have a device code yet, get one
if (!isset($_SESSION['device_code'])) {
    try {
        $deviceCodeResponse = $mailFetcher->getDeviceCode();
        $_SESSION['device_code'] = $deviceCodeResponse['device_code'];
        $_SESSION['user_code'] = $deviceCodeResponse['user_code'];
        $_SESSION['verification_uri'] = $deviceCodeResponse['verification_uri'];
    } catch (Exception $e) {
        echo "Error getting device code: " . $e->getMessage();
        exit;
    }
}

// If polling for token
if (isset($_GET['poll']) && isset($_SESSION['device_code'])) {
    try {
        $tokenData = $mailFetcher->pollForToken($_SESSION['device_code']);
        if (isset($tokenData['access_token'])) {
            $_SESSION['access_token'] = $tokenData['access_token'];
            $_SESSION['refresh_token'] = $tokenData['refresh_token'] ?? null;
            $_SESSION['expires_in'] = time() + ($tokenData['expires_in'] ?? 3600);
            
            unset($_SESSION['device_code']);
            unset($_SESSION['user_code']);
            unset($_SESSION['verification_uri']);
            
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'pending']);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>AIINBOX - Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }
        
        .login-container {
            background: white;
            padding: 2.5em;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .logo {
            margin-bottom: 1.5em;
        }
        
        h2 {
            color: #333;
            margin-bottom: 1em;
        }
        
        .code {
            font-size: 2.5em;
            font-weight: bold;
            color: #1a73e8;
            margin: 1em 0;
            letter-spacing: 2px;
            font-family: monospace;
        }
        
        .link {
            color: #1a73e8;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
            margin-top: 1em;
            padding: 0.5em 1em;
            border: 2px solid #1a73e8;
            border-radius: 24px;
            transition: all 0.3s ease;
        }
        
        .link:hover {
            background: #1a73e8;
            color: white;
        }
        
        .instructions {
            color: #666;
            margin: 1.5em 0;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="assets/images/aiinbox-logo.png" alt="AIINBOX" height="40">
        </div>
        <h2>Sign in to AIINBOX</h2>
        <?php if (isset($_SESSION['user_code'])): ?>
            <p class="instructions">To continue, please enter this code:</p>
            <div class="code"><?php echo htmlspecialchars($_SESSION['user_code']); ?></div>
            <p class="instructions">at Microsoft's device login page:</p>
            <a href="<?php echo htmlspecialchars($_SESSION['verification_uri']); ?>" 
               target="_blank" 
               class="link">Open Microsoft Login</a>
            
            <script>
            function pollForToken() {
                fetch('index.php?poll=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'home.php';
                        } else if (data.error === 'pending') {
                            setTimeout(pollForToken, 5000);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        setTimeout(pollForToken, 5000);
                    });
            }
            
            pollForToken();
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
?>
