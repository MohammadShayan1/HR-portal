# Email Logs Viewer Guide

## Overview
The Email Logs page provides a comprehensive view of all email communications sent from the HR Portal system, including meeting invitations sent to candidates.

## Access
Navigate to **Email Logs** from the main navigation menu in your dashboard.

**URL:** `http://localhost/HR-portal/index.php?page=email_logs`

## Features

### 📊 Statistics Dashboard
At the top of the page, you'll see three key metrics:

1. **Total Emails**: Total number of emails sent from your account
2. **Successfully Sent**: Count of emails delivered successfully
3. **Failed to Send**: Count of emails that failed to deliver

The success rate percentage is displayed at the bottom of the page.

### 🔍 Filtering & Search

#### Status Filter
- **All Status**: Shows all emails (sent and failed)
- **Sent Only**: Shows only successfully delivered emails
- **Failed Only**: Shows only failed email attempts

#### Search Function
Search across:
- Candidate names
- Email addresses
- Email subjects

Simply type your query and click "Search" to filter results.

### 📧 Email Log Details

Each log entry displays:
- **Date & Time**: When the email was sent
- **Candidate**: Candidate name and job title
- **Recipient**: Email address
- **Subject**: Email subject line
- **Status**: Sent (green) or Failed (red)
- **View Details**: Button to see full information

### 🔎 Detailed View

Click the "View" button on any log entry to see:
- Candidate ID
- Full candidate name
- Recipient email address
- Complete subject line
- Detailed status (including error messages for failed emails)
- Exact timestamp
- User agent (browser/device used)
- IP address of sender

### 📄 Pagination
If you have more than 50 email logs, they will be paginated:
- Navigation controls appear at the bottom
- Shows your current position (e.g., "Showing 50 of 150 email logs")
- Click page numbers or Previous/Next to navigate

## Understanding Email Status

### ✅ Sent Status
- Email was successfully delivered by the mail server
- Shows green badge with checkmark icon

### ❌ Failed Status
- Email delivery failed
- Shows red badge with X icon
- View details to see the specific error message

Common failure reasons:
- Invalid email address
- Mail server configuration issues
- Network connectivity problems
- Email address doesn't exist

## Email Tracking System

All emails sent through the system are automatically logged with:
- **Timestamp**: Exact time of sending
- **Recipient tracking**: Who received the email
- **Status tracking**: Success or failure
- **Error logging**: Detailed error messages for failed sends
- **Audit trail**: User agent and IP address for security

## Use Cases

### 1. Verify Email Delivery
Check if meeting invitations were successfully sent to candidates:
1. Go to Email Logs
2. Search for candidate name or email
3. Verify "Sent" status with green badge

### 2. Troubleshoot Failed Emails
Identify and fix email delivery issues:
1. Filter by "Failed Only"
2. Click "View" on failed entries
3. Check error message in details modal
4. Fix the issue (e.g., correct email address)
5. Resend from Candidates page

### 3. Audit Trail
Review email communication history:
1. Use date range by scrolling through pages
2. Export information if needed (feature coming soon)
3. Verify compliance with communication logs

### 4. Monitor Success Rate
Track email deliverability:
1. Check statistics at top of page
2. Monitor success rate percentage
3. Investigate if rate drops below 90%

## Integration with Candidate Management

Email logs are connected to the candidate management system:
- Each log links to a specific candidate
- Shows job title for context
- When you accept a candidate and send a meeting invitation, it's logged here
- Failed emails can be resent from the Candidates page

## Tips

### Best Practices
- **Check regularly**: Review email logs weekly to catch any issues
- **Monitor success rate**: Aim for 95%+ delivery rate
- **Fix failed emails**: Address failed deliveries promptly
- **Verify important emails**: Always check that meeting invitations were sent successfully

### Troubleshooting
If emails are failing:
1. Check the error message in details view
2. Verify email addresses are correct in candidate records
3. Ensure your server's mail configuration is correct
4. Check if recipient's email provider is blocking your domain
5. Consider using SMTP configuration instead of PHP mail()

### Performance
- Logs are paginated (50 per page) for fast loading
- Search and filters work on server-side for efficiency
- Old logs are retained indefinitely for compliance

## Future Enhancements

Planned features:
- ✅ Professional email headers (Implemented)
- ✅ Detailed status tracking (Implemented)
- ✅ Search and filtering (Implemented)
- 📅 Export to CSV/Excel
- 📅 Email resend functionality from logs page
- 📅 Email templates management
- 📅 Scheduled email reports
- 📅 Email delivery analytics and charts

## Technical Details

### Database Schema
The `email_logs` table stores:
```sql
- id: Unique identifier
- candidate_id: Link to candidate
- recipient: Email address
- subject: Email subject line
- status: 'sent' or 'failed: error message'
- sent_at: Timestamp
- user_agent: Browser/device info
- ip_address: Sender's IP
```

### Privacy & Security
- Only your own email logs are visible (user isolation)
- IP addresses and user agents logged for security audit
- Email content is not stored in logs (only subject line)
- Access requires authentication

## Support

If you encounter any issues with email logs:
1. Check that emails are being sent from Candidates page
2. Verify database migration ran successfully
3. Ensure proper permissions on database file
4. Check PHP error logs for any issues

---

**Last Updated:** November 8, 2025
**Version:** 1.0
