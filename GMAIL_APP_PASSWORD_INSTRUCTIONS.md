# Setting up Gmail App Password for Symfony Mailer

Since Google has increased security on Gmail accounts, using regular passwords for application access is no longer supported. Instead, you need to generate an App Password - a special 16-character password that allows less secure apps or devices to access your Google Account.

## Step 1: Enable 2-Step Verification

1. Go to your [Google Account](https://myaccount.google.com/)
2. Select "Security"
3. Under "Signing in to Google," select "2-Step Verification"
4. Follow the steps to turn on 2-Step Verification

## Step 2: Create an App Password

1. Go to your [Google Account](https://myaccount.google.com/)
2. Select "Security"
3. Under "Signing in to Google," select "App passwords" (you'll only see this option if 2-Step Verification is enabled)
4. At the bottom, select "Select app" and choose "Mail"
5. Select "Select device" and choose "Other (Custom name)"
6. Enter "Symfony Mailer" or another name to help you remember
7. Select "Generate"
8. The app password is the 16-character code that appears on your screen
9. Copy this password (you won't be able to see it again)

## Step 3: Configure your .env.local file

Open your `.env.local` file and update your MAILER_DSN to:

```
MAILER_DSN=gmail://YOUR_GMAIL_ADDRESS:YOUR_APP_PASSWORD@default
```

Example:

```
MAILER_DSN=gmail://wajdibz8@gmail.com:abcdefghijklmnop@default
```

Note: Replace `YOUR_APP_PASSWORD` with the 16-character app password Google generated for you (without spaces).

## Step 4: Test your configuration

Run the following command to test if your email configuration works:

```
php bin/console app:send-simple-email
```

If successful, you should see a success message and the email should arrive at the recipient's inbox.
