# OAuth2 Email Authentication

This system automatically refreshes OAuth2 access tokens before sending emails, eliminating the need to manually generate tokens.

## Setup Instructions

### 1. Create OAuth2 Credentials

#### For Google/Gmail:

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a project or select an existing one
3. Navigate to "APIs & Services" > "Credentials"
4. Click "Create Credentials" > "OAuth client ID"
5. Select "Web application" as the application type
6. Add authorized redirect URIs (e.g., `http://localhost:8000/oauth2callback`)
7. Copy the Client ID and Client Secret

### 2. Obtain a Refresh Token

For Gmail:

1. Use this OAuth2 playground: https://developers.google.com/oauthplayground/
2. Click the gear icon in the top right and check "Use your own OAuth credentials"
3. Enter your Client ID and Client Secret from step 1
4. Select "Gmail API v1" and authorize the scopes: `https://mail.google.com/`
5. Click "Exchange authorization code for tokens"
6. Copy the refresh token

### 3. Configure Environment Variables

1. Copy `.env.oauth.example` to `.env.local` (or add to your existing `.env.local`)
2. Update the values with your credentials:

```
OAUTH_CLIENT_ID=your_client_id
OAUTH_CLIENT_SECRET=your_client_secret
OAUTH_REFRESH_TOKEN=your_refresh_token
MAILER_DSN=smtp+oauth2://your_email@gmail.com@smtp.gmail.com:587
```

Replace `your_email` with your Gmail username (without @gmail.com).

### 4. Test the Configuration

There are multiple ways to test your OAuth2 configuration:

#### Using the Command Line

Run the test command to verify OAuth2 authentication works:

```bash
php bin/console app:send-oauth-email --token-info
```

#### Using the Web Demo

Visit the OAuth2 email demo page to test sending emails through the web interface:

```
http://your-app-url/demo/send-oauth-email
```

## How It Works

1. The `OAuthTokenManager` service handles token management and refreshing
2. Before each email is sent, the system checks if the token needs refreshing
3. If sending fails due to an expired token, it automatically invalidates the cache and tries again with a new token
4. Tokens are cached to avoid unnecessary API calls

### Components

Our OAuth2 implementation consists of several components:

- **OAuthTokenManager**: Manages token retrieval, caching, and refreshing
- **OAuth2SmtpTransport**: Custom SMTP transport with OAuth2 support
- **XOAuth2Authenticator**: Implements XOAUTH2 SMTP authentication protocol
- **MailerService**: Enhanced service that uses OAuth2 for all email sending

### Commands

We provide two utility commands:

1. **app:generate-oauth-token**: Helps you generate OAuth2 tokens

   ```bash
   php bin/console app:generate-oauth-token --provider=google
   ```

2. **app:send-oauth-email**: Tests the OAuth2 email sending
   ```bash
   php bin/console app:send-oauth-email --token-info
   ```

## Usage in Your Code

Simply inject the `MailerService` into your classes and use the provided methods:

```php
// This will automatically use OAuth2 if configured
$mailerService->sendEmailVerification($user);
```

The service automatically:

1. Checks if a valid token exists in cache
2. Refreshes the token if needed
3. Attempts to send the email with OAuth2 authentication
4. If authentication fails, invalidates the token and tries again with a fresh token

All of this happens transparently without any manual intervention needed.
