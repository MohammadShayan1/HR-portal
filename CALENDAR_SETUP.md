# Calendar Synchronization Setup Guide

The HR Portal now supports **two-way calendar synchronization** with Google Calendar and Microsoft Outlook. This means:
- Meetings scheduled in HR Portal automatically appear in your Google/Outlook calendar
- Events from your Google/Outlook calendar appear in the HR Portal dashboard
- All events are synced in real-time with proper time zones

## Features

✅ **Bidirectional Sync** - View all calendars in one place  
✅ **Automatic Zoom Integration** - Zoom links included in calendar events  
✅ **Color-Coded Events** - Easily distinguish between different calendars  
✅ **Timezone Support** - All events respect your configured timezone  
✅ **Multi-Calendar View** - See HR Portal + Google + Outlook events together

## Setup Instructions

### 1. Google Calendar Integration

#### Step 1: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click "New Project" and name it (e.g., "HR Portal Calendar")
3. Select your newly created project

#### Step 2: Enable Google Calendar API

1. In the sidebar, go to "APIs & Services" → "Library"
2. Search for "Google Calendar API"
3. Click on it and press "Enable"

#### Step 3: Create OAuth 2.0 Credentials

1. Go to "APIs & Services" → "Credentials"
2. Click "Create Credentials" → "OAuth client ID"
3. Select "Web application"
4. Add Authorized Redirect URI:
   ```
   https://your-domain.com/functions/oauth_callback.php?provider=google
   ```
5. Save and copy the **Client ID** and **Client Secret**

#### Step 4: Configure in oauth_callback.php

Open `functions/oauth_callback.php` and replace:
```php
$client_id = 'YOUR_GOOGLE_CLIENT_ID';
$client_secret = 'YOUR_GOOGLE_CLIENT_SECRET';
```

#### Step 5: Connect in Settings

1. Go to Settings → Calendar Synchronization
2. Click "Connect Google Calendar"
3. Grant permissions
4. Enable "Automatically sync meetings to Google Calendar"

---

### 2. Microsoft Outlook Integration

#### Step 1: Register App in Azure

1. Go to [Azure Portal](https://portal.azure.com/)
2. Navigate to "Azure Active Directory" → "App registrations"
3. Click "New registration"
4. Name: "HR Portal Calendar"
5. Supported account types: "Accounts in any organizational directory and personal Microsoft accounts"
6. Redirect URI: `Web` → `https://your-domain.com/functions/oauth_callback.php?provider=outlook`
7. Click "Register"

#### Step 2: Add API Permissions

1. In your app, go to "API permissions"
2. Click "Add a permission" → "Microsoft Graph"
3. Select "Delegated permissions"
4. Add these permissions:
   - `Calendars.ReadWrite`
   - `offline_access`
5. Click "Grant admin consent"

#### Step 3: Create Client Secret

1. Go to "Certificates & secrets"
2. Click "New client secret"
3. Add description and expiry
4. Copy the **Value** (this is your Client Secret)

#### Step 4: Get Application ID

1. Go to "Overview"
2. Copy the **Application (client) ID**

#### Step 5: Configure in oauth_callback.php

Open `functions/oauth_callback.php` and replace:
```php
$client_id = 'YOUR_MICROSOFT_CLIENT_ID';
$client_secret = 'YOUR_MICROSOFT_CLIENT_SECRET';
```

#### Step 6: Connect in Settings

1. Go to Settings → Calendar Synchronization
2. Click "Connect Outlook Calendar"
3. Sign in with Microsoft account
4. Grant permissions
5. Enable "Automatically sync meetings to Outlook Calendar"

---

## Usage

### Scheduling Meetings

When you schedule a meeting with a candidate (score ≥ 60):
1. Fill in the meeting form
2. Meeting is automatically created in:
   - HR Portal database
   - Zoom (meeting link generated)
   - Google Calendar (if enabled)
   - Outlook Calendar (if enabled)

### Viewing Calendar

The dashboard shows a unified calendar with:
- **Blue events** - HR Portal meetings (with Zoom links)
- **Green events** (📅) - Google Calendar events
- **Dark blue events** (📧) - Outlook Calendar events

Click any event to see details and join links.

### Timezone Configuration

Set your timezone in Settings → Calendar Synchronization to ensure all events display correctly across calendars.

---

## Event Details

### HR Portal Meetings Include:
- Candidate name and email
- Job position
- Meeting status (scheduled/completed/cancelled)
- Zoom join link (for attendees)
- Zoom start link (for hosts)

### External Calendar Events Include:
- Event title
- Date and time
- Description and location
- Link to view in source calendar

---

## Security Notes

⚠️ **Important Security Considerations:**

1. **OAuth Tokens** - Access tokens are stored in your database. Ensure your database is properly secured.
2. **HTTPS Required** - Calendar APIs require HTTPS. Make sure your site uses SSL/TLS.
3. **Token Refresh** - Implement token refresh logic for long-term use (tokens expire).
4. **Scopes** - Only request necessary calendar permissions.

---

## Troubleshooting

### "Connection Failed" Error
- Verify Client ID and Client Secret are correct
- Check redirect URI matches exactly
- Ensure API is enabled in Google Cloud Console / Azure

### Events Not Syncing
- Check that sync toggle is enabled in Settings
- Test connection using "Test Connection" button
- Verify timezone is set correctly

### Token Expired
- Disconnect and reconnect the calendar
- Implement token refresh in production

---

## API Rate Limits

- **Google Calendar**: 1,000,000 requests/day (default)
- **Microsoft Graph**: 10,000 requests/10 minutes

For high-traffic installations, consider implementing caching and webhook subscriptions for real-time updates.

---

## Support

For issues or questions:
1. Check console logs for detailed error messages
2. Verify OAuth credentials are correct
3. Test API connections using the built-in test buttons

---

## Future Enhancements

Potential features for future versions:
- [ ] Token auto-refresh mechanism
- [ ] Webhook subscriptions for real-time sync
- [ ] Support for multiple Google/Outlook accounts
- [ ] Calendar-specific color customization
- [ ] Event creation from external calendars
- [ ] Meeting reminders via email/SMS
