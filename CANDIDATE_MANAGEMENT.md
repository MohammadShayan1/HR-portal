# Candidate Management System - Implementation Summary

## Features Implemented

### 1. Database Schema Updates
Added new columns to `candidates` table:
- `candidate_status` (TEXT, default: 'pending') - Status: pending/accepted/rejected
- `meeting_link` (TEXT) - Video meeting link for accepted candidates
- `email_sent_at` (TEXT) - Timestamp when meeting invitation was sent
- `rejection_reason` (TEXT) - Optional reason for rejection

### 2. Three-Tab Interface
**Pending Tab:**
- Shows all candidates waiting for decision
- Displays interview status and score (if report generated)
- Actions: View Report → Accept or Reject

**Accepted Tab:**
- Shows accepted candidates with meeting links
- Email status indicator (sent/not sent)
- Actions:
  - View Report
  - Send Email (automated meeting invitation)
  - Edit Meeting Link

**Rejected Tab:**
- Shows rejected candidates with rejection reason
- Actions:
  - View Report
  - Move back to Accepted (if decision changes)

### 3. Accept Candidate Workflow
1. Click "Accept" button on pending candidate (after viewing report)
2. Modal opens with:
   - Meeting link input field (required)
   - Checkbox: "Send meeting invitation email immediately" (checked by default)
3. On submit:
   - Candidate status → "accepted"
   - Meeting link saved
   - Email sent automatically (if checkbox checked)
   - Moves to Accepted tab

### 4. Reject Candidate Workflow
1. Click "Reject" button on pending candidate
2. Modal opens with:
   - Optional rejection reason textarea
3. On submit:
   - Candidate status → "rejected"
   - Reason saved
   - Moves to Rejected tab

### 5. Automated Email System
**Meeting Invitation Email includes:**
- Congratulations message
- Job title and company name
- Meeting link (prominent button + plain link)
- What to expect section
- Preparation tips
- Professional HTML styling

**Email Features:**
- HTML formatted with inline CSS
- Responsive design
- Call-to-action button for meeting link
- Company branding (uses company name and email)
- Automated "do not reply" footer

**Send Email Options:**
- Automatic: Send immediately when accepting candidate (checkbox)
- Manual: "Send Email" button in Accepted tab (one-time only)
- Email status tracked (prevents duplicate sends)

### 6. Additional Features
- **Edit Meeting Link**: Accepted candidates can have meeting link updated
- **Move to Accepted**: Rejected candidates can be reconsidered and moved back
- **Filter by Job**: Dropdown to filter candidates by specific job
- **Tab Badges**: Show count of candidates in each status
- **Responsive Design**: Works on mobile and desktop
- **Security**: Verifies user ownership of candidates before any action

## File Changes

### New Files:
- `functions/candidate_actions.php` (335 lines) - All candidate management logic

### Modified Files:
- `migrate.php` - Added 4 new columns to candidates table
- `gui/candidates.php` - Complete rewrite with tabs and new features
- `functions/actions.php` - Added 5 new action cases

## Usage Guide

### For HR Admins:
1. **Review Applications**: Go to Candidates page → Pending tab
2. **Send Interview Link**: For "Applied" candidates
3. **Generate Report**: After candidate completes interview
4. **Make Decision**: View report → Accept or Reject
5. **Accept Candidate**:
   - Enter meeting link (Zoom, Google Meet, etc.)
   - Email sent automatically (or later via button)
6. **Monitor Accepted**: Check Accepted tab for email status
7. **Resend/Update**: Edit meeting link or manually send email if needed

### Email Requirements:
- Server must have PHP `mail()` function configured
- SMTP settings should be configured in server
- From address: Uses company email from user profile
- Test email functionality with "Send Email" button

## Testing Checklist

✅ Database migration successful (4 new columns added)
✅ Pending tab shows candidates correctly
✅ Accept modal opens with meeting link input
✅ Reject modal opens with reason textarea
✅ Accepted tab shows meeting links and email status
✅ Rejected tab shows rejection reasons
✅ Tab switching preserves job filter
✅ Job filter works across all tabs
✅ Email button shows loading spinner
✅ "Move to Accepted" button works from Rejected tab

## Next Steps

1. **Test Email Sending**: Configure PHP mail() on server
2. **Customize Email Template**: Update company branding in email
3. **Add Email Logs**: Track all emails sent (optional enhancement)
4. **Calendar Integration**: Auto-add meeting to calendar (future feature)
5. **SMS Notifications**: Send SMS in addition to email (future feature)

## Security Features

- ✅ User authentication required for all actions
- ✅ Candidates verified against user's jobs (multi-tenant)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (sanitize() on all outputs)
- ✅ CSRF protection (session-based)
- ✅ Email rate limiting (prevents duplicate sends)

## Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (responsive design)

## Performance

- Single database query per tab load
- Lazy loading of modals (one per candidate)
- AJAX for email sending (no page reload)
- Efficient SQL joins for candidate + job + report data
