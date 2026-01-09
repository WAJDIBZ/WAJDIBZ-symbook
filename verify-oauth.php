<?php
// Simple script to verify OAuth2 credentials by making a direct API call

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// OAuth credentials from environment (recommended: put them in .env.local)
$clientId = getenv('OAUTH_CLIENT_ID') ?: '';
$clientSecret = getenv('OAUTH_CLIENT_SECRET') ?: '';
$refreshToken = getenv('OAUTH_REFRESH_TOKEN') ?: '';
$tokenUrl = getenv('OAUTH_TOKEN_URL') ?: 'https://oauth2.googleapis.com/token';

if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
    fwrite(STDERR, "ERROR: Missing OAuth env vars. Set OAUTH_CLIENT_ID, OAUTH_CLIENT_SECRET, OAUTH_REFRESH_TOKEN.\n");
    exit(1);
}

echo "Verifying OAuth credentials...\n";

// Build the token request
$postData = http_build_query([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'refresh_token' => $refreshToken,
    'grant_type' => 'refresh_token',
    'scope' => 'https://mail.google.com/'
]);

// Create context for the request
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

// Make the token request
echo "Requesting access token...\n";
$result = file_get_contents($tokenUrl, false, $context);

if ($result === false) {
    echo "ERROR: Unable to get access token\n";
    exit(1);
}

// Decode the response
$response = json_decode($result, true);

echo "Response from token endpoint:\n";
print_r($response);

if (isset($response['access_token'])) {
    echo "\nSUCCESS: Access token obtained: " . substr($response['access_token'], 0, 10) . "...[truncated]\n";

    // Now try to use the token to access Gmail API
    echo "\nTesting Gmail API access...\n";

    $gmailApiUrl = 'https://gmail.googleapis.com/gmail/v1/users/me/profile';
    $authContext = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Authorization: Bearer ' . $response['access_token']
        ]
    ]);

    $profileResult = file_get_contents($gmailApiUrl, false, $authContext);

    if ($profileResult === false) {
        echo "ERROR: Unable to access Gmail API with the token\n";
    } else {
        $profile = json_decode($profileResult, true);
        echo "Gmail profile retrieved:\n";
        print_r($profile);
        echo "\nVerification complete! OAuth2 credentials are working correctly.\n";
    }
} else {
    echo "\nERROR: No access token in response\n";
}
