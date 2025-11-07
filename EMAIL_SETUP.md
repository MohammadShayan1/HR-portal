# Email Configuration Fix for HR Portal

## Problem
Your WAMP server's PHP mail() function is not configured, so emails are failing silently.

## Quick Solution Options:

### Option 1: Configure Gmail SMTP (Recommended for Development)

1. **Edit php.ini file:**
   - Location: `C:\wamp64\bin\php\php8.x.x\php.ini` (find your PHP version)
   - Find these lines and update them:
   ```ini
   [mail function]
   SMTP = smtp.gmail.com
   smtp_port = 587
   sendmail_from = your-email@gmail.com
   ```

2. **Enable "Less secure app access" or use App Password:**
   - Go to Google Account settings
   - Security > 2-Step Verification > App passwords
   - Generate an app password for "Mail"

3. **Restart WAMP** after changing php.ini

### Option 2: Use a Development Mail Server (Best for Testing)

**MailHog** (Recommended):
- Download: https://github.com/mailhog/MailHog/releases
- Run: `MailHog.exe`
- Configure php.ini:
  ```ini
  SMTP = localhost
  smtp_port = 1025
  sendmail_from = test@hrportal.local
  ```
- View emails at: http://localhost:8025

### Option 3: Install PHPMailer (Most Reliable)

```bash
# In your project directory
composer require phpmailer/phpmailer
```

## Current Status

✅ **Email logging is now working** - Failed emails will appear in Email Logs page
✅ **Better error messages** - You'll see why emails fail
❌ **Emails won't send** - Until SMTP is configured

## Test After Configuration

Run this command to test:
```bash
php test_email_config.php
```

Or try sending an email from the HR Portal interface - it will now show in logs even if it fails!
