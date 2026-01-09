<?php
// Test direct SMTP connection with XOAUTH2 authentication

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// OAuth credentials from environment (recommended: put them in .env.local)
$clientId = getenv('OAUTH_CLIENT_ID') ?: '';
$clientSecret = getenv('OAUTH_CLIENT_SECRET') ?: '';
$refreshToken = getenv('OAUTH_REFRESH_TOKEN') ?: '';
$tokenUrl = getenv('OAUTH_TOKEN_URL') ?: 'https://oauth2.googleapis.com/token';
$emailAddress = getenv('OAUTH_EMAIL') ?: '';

if ($clientId === '' || $clientSecret === '' || $refreshToken === '' || $emailAddress === '') {
    fwrite(
        STDERR,
        "ERROR: Missing OAuth env vars. Set OAUTH_CLIENT_ID, OAUTH_CLIENT_SECRET, OAUTH_REFRESH_TOKEN, OAUTH_EMAIL.\n"
    );
    exit(1);
}

echo "SMTP Auth Test\n";
echo "==============\n\n";

// First get an OAuth2 access token
echo "1. Getting OAuth2 access token...\n";

$postData = http_build_query([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'refresh_token' => $refreshToken,
    'grant_type' => 'refresh_token',
    'scope' => 'https://mail.google.com/'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

$result = file_get_contents($tokenUrl, false, $context);
if ($result === false) {
    echo "ERROR: Unable to get access token\n";
    exit(1);
}

$response = json_decode($result, true);
if (!isset($response['access_token'])) {
    echo "ERROR: No access token in response\n";
    exit(1);
}

$accessToken = $response['access_token'];
echo "Access token obtained: " . substr($accessToken, 0, 10) . "...\n\n";

// Now try SMTP connection
echo "2. Testing SMTP connection...\n";

// Define SMTP parameters
$smtpServer = 'smtp.gmail.com';
$smtpPort = 465;
$timeout = 30;

echo "Connecting to $smtpServer:$smtpPort...\n";

// Open connection
$socket = fsockopen('ssl://' . $smtpServer, $smtpPort, $errno, $errstr, $timeout);

if (!$socket) {
    echo "ERROR: Could not connect ($errno): $errstr\n";
    exit(1);
}

echo "Connected successfully!\n\n";

// Read greeting
$response = fgets($socket, 515);
echo "SERVER: $response";

// Send EHLO
echo "CLIENT: EHLO example.com\n";
fputs($socket, "EHLO example.com\r\n");
$response = '';
do {
    $line = fgets($socket, 515);
    $response .= $line;
    echo "SERVER: $line";
} while (strpos($line, ' ') === 0);

// Check if AUTH XOAUTH2 is supported
echo "\nChecking for XOAUTH2 support...\n";
if (strpos($response, 'AUTH') !== false && strpos($response, 'XOAUTH2') !== false) {
    echo "XOAUTH2 is supported!\n";

    // Create the XOAUTH2 token
    $authString = base64_encode("user=$emailAddress\x01auth=Bearer $accessToken\x01\x01");

    // Send AUTH XOAUTH2 command
    echo "\nCLIENT: AUTH XOAUTH2 [base64-encoded-token]\n";
    fputs($socket, "AUTH XOAUTH2 $authString\r\n");

    $response = fgets($socket, 515);
    echo "SERVER: $response\n";

    if (strpos($response, '235') === 0) {
        echo "\nSUCCESS: Authentication successful!\n";
    } else if (strpos($response, '334') === 0) {
        echo "XOAUTH2 challenge received, sending empty response...\n";
        fputs($socket, "\r\n");
        $response = fgets($socket, 515);
        echo "SERVER: $response\n";

        if (strpos($response, '235') === 0) {
            echo "\nSUCCESS: Authentication successful after challenge response!\n";
        } else {
            echo "\nERROR: Authentication failed after challenge response.\n";
        }
    } else {
        echo "\nERROR: Authentication failed.\n";
    }
} else {
    echo "XOAUTH2 is NOT supported by this server.\n";
}

// Close connection
echo "\nClosing connection...\n";
fputs($socket, "QUIT\r\n");
fclose($socket);
echo "Done.\n";
