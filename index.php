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
    header('Location: ./home');
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
    <title>Smart Compose - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #5e64ff 0%, #8b92ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            margin: 20px;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #5e64ff, #8b92ff);
        }

        .logo {
            margin-bottom: 2rem;
            position: relative;
        }

        .logo img {
            height: 70px;
            margin-bottom: 1rem;
        }

        .logo::after {
            content: '';
            display: block;
            width: 50px;
            height: 2px;
            background: #5e64ff;
            margin: 1rem auto;
        }

        h2 {
            color: #2d3748;
            font-size: 1.75rem;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .instructions {
            color: #4a5568;
            margin: 1.5rem 0;
            line-height: 1.6;
            font-size: 1rem;
        }

        .code {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 12px;
            font-family: 'Courier New', monospace;
            font-size: 2rem;
            font-weight: bold;
            color: #5e64ff;
            letter-spacing: 4px;
            margin: 1.5rem 0;
            border: 2px dashed #e2e8f0;
            text-shadow: 1px 1px 0 rgba(94, 100, 255, 0.1);
        }

        .link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #5e64ff;
            color: white;
            text-decoration: none;
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            box-shadow: 0 4px 15px rgba(94, 100, 255, 0.2);
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
        }

        .link:hover {
            background: #4a51ff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(94, 100, 255, 0.3);
        }

        .link:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(94, 100, 255, 0.3);
        }

        .link i {
            font-size: 1.1em;
        }

        .status-message {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 12px;
            background: #f8fafc;
            color: #4a5568;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .status-message i {
            color: #5e64ff;
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .wave {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 2rem;
                margin: 1rem;
            }

            .code {
                font-size: 1.5rem;
                padding: 1rem;
            }

            h2 {
                font-size: 1.5rem;
            }
        }

        .code-container {
            position: relative;
            margin: 1.5rem 0;
        }

        .code {
            padding-right: 3rem;
        }

        .copy-button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #5e64ff;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .copy-button:hover {
            background: rgba(94, 100, 255, 0.1);
        }

        .copy-button i {
            font-size: 1.2rem;
        }

        .copy-tooltip {
            position: absolute;
            right: -5px;
            top: -30px;
            background: #2d3748;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .copy-tooltip.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="wave"></div>
    <div class="login-container">
        <div class="logo">
            <img src="./images/smart_compse_fullLogo.png" alt="Smart Compose">
        </div>
        <h2>Welcome to Smart Compose</h2>
        <?php if (isset($_SESSION['user_code'])): ?>
            <p class="instructions">To continue, please enter this code:</p>
            <div class="code-container">
                <div class="code"><?php echo htmlspecialchars($_SESSION['user_code']); ?></div>
                <button class="copy-button" onclick="copyCode()">
                    <i class="fas fa-copy"></i>
                    <span class="copy-tooltip">Copied!</span>
                </button>
            </div>
            <p class="instructions">at Microsoft's device login page</p>
            <button onclick="openMicrosoftLogin()" class="link">
                <i class="fas fa-external-link-alt"></i>
                Open Microsoft Login
            </button>
            <div class="status-message">
                <i class="fas fa-sync-alt"></i>
                Waiting for authentication...
            </div>
            
            <script>
            function copyCode() {
                const code = '<?php echo isset($_SESSION['user_code']) ? $_SESSION['user_code'] : ''; ?>';
                navigator.clipboard.writeText(code).then(() => {
                    const tooltip = document.querySelector('.copy-tooltip');
                    tooltip.classList.add('show');
                    setTimeout(() => {
                        tooltip.classList.remove('show');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                });
            }

            function pollForToken() {
                fetch('index.php?poll=1')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = './home';
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

            function openMicrosoftLogin() {
                const url = '<?php echo htmlspecialchars($_SESSION['verification_uri']); ?>';
                const width = 600;
                const height = 600;
                const left = (window.innerWidth - width) / 2;
                const top = (window.innerHeight - height) / 2;
                
                const popup = window.open(
                    url,
                    'Microsoft Login',
                    `width=${width},
                     height=${height},
                     left=${left},
                     top=${top},
                     toolbar=no,
                     menubar=no,
                     scrollbars=yes,
                     resizable=no,
                     location=no,
                     status=no`
                );

                // Focus on the popup
                if (popup) popup.focus();
            }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>

