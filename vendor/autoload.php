<?php

spl_autoload_register(function ($class) {
    // Microsoft Graph SDK
    if (strpos($class, 'Microsoft\\Graph\\') === 0) {
        $path = __DIR__ . '/microsoft-graph-sdk/src/';
        $file = str_replace('\\', '/', substr($class, 16)) . '.php';
        $fullPath = $path . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }
    
    // Guzzle
    if (strpos($class, 'GuzzleHttp\\') === 0) {
        $path = __DIR__ . '/guzzle/src/';
        $file = str_replace('\\', '/', substr($class, 11)) . '.php';
        $fullPath = $path . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }
    
    // OAuth2 Client
    if (strpos($class, 'League\\OAuth2\\') === 0) {
        $path = __DIR__ . '/oauth2-client/src/';
        $file = str_replace('\\', '/', substr($class, 15)) . '.php';
        $fullPath = $path . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }

    // Parsedown
    if ($class === 'Parsedown') {
        $path = __DIR__ . '/parsedown/'; // Adjust the path to where Parsedown.php is located
        $file = 'Parsedown.php';
        $fullPath = $path . $file;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }

});
