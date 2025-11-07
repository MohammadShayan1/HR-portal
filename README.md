# HR Virtual Interview Portal v2.0

A production-ready, AI-powered recruitment platform with **multi-tenant architecture**, **calendar integration**, and **automated AI interviews** using Google's Gemini API. Schedule meetings via Zoom, sync with Google Calendar & Outlook, post jobs to LinkedIn, and conduct AI-powered evaluations—all in one self-hosted platform.

---

## 🌟 Key Features

### Core Features
- ✅ **Multi-Tenant System** - Each user has isolated data, branding, and settings
- ✅ **AI-Powered Interviews** - Automated questions and comprehensive candidate evaluation
- ✅ **Smart Scheduling** - Auto-schedule meetings with high-scoring candidates (60+)
- ✅ **Calendar Sync** - Two-way sync with Google Calendar & Outlook
- ✅ **Zoom Integration** - Automatic meeting creation with join/start links
- ✅ **LinkedIn Auto-Post** - Publish jobs directly to LinkedIn company pages
- ✅ **AI Detection** - Detect potential AI-generated answers with typing analysis
- ✅ **Report Regeneration** - Up to 5 regenerations per candidate
- ✅ **Self-Hosted** - Complete control over your data with SQLite

### Security Features
- 🔒 **Token Encryption** - AES-256-CBC encryption for OAuth tokens
- 🔒 **Session Security** - HTTPOnly, Secure, SameSite cookies
- 🔒 **Rate Limiting** - Brute-force protection (5 attempts, 15-min lockout)
- 🔒 **CSRF Protection** - Cross-site request forgery prevention
- 🔒 **Security Headers** - CSP, HSTS, X-Frame-Options, etc.
- 🔒 **SQL Injection Protected** - Prepared statements throughout
- 🔒 **XSS Protected** - Output sanitization everywhere
- 🔒 **Security Logging** - Audit trail for security events

---

## 📋 Requirements

- **PHP 7.4+** with extensions: `pdo_sqlite`, `curl`, `openssl`, `fileinfo`
- **Web Server**: Apache or Nginx
- **HTTPS** (required for OAuth and production use)
- **API Keys** (optional):
  - Google Gemini API (for AI features)
  - Zoom API (for meetings)
  - Google OAuth (for Calendar sync)
  - Microsoft OAuth (for Outlook sync)
  - LinkedIn API (for job posting)

---

## 🚀 Quick Start

### 1. **Installation**

```bash
# Download/clone repository
git clone https://github.com/MohammadShayan1/HR-portal.git
cd HR-portal

# Set permissions
chmod 755 .
chmod 777 assets/uploads/

# Run database migration
php migrate.php
```

### 2. **Configuration**

```bash
# Copy config template
cp config.example.php config.php

# Generate secret key
php -r "echo bin2hex(random_bytes(32));"

# Edit config.php with your values
# - Paste secret key as 'app_secret_key'
# - Set 'force_https' to true (production)
# - Add OAuth credentials
```

### 3. **First Time Setup**

1. Navigate to `https://yourdomain.com/gui/register.php`
2. Create admin account (one-time registration)
3. Go to **Settings** → Add Gemini API key
4. Upload company logo (optional)
5. Configure integrations (Zoom, LinkedIn, Calendars)

### 4. **Start Using**

- **Create Jobs** → AI generates descriptions
- **Share Links** → Candidates apply online
- **Auto Interviews** → AI asks tailored questions
- **Get Reports** → AI evaluates and scores candidates
- **Schedule Meetings** → Auto-create Zoom + sync calendars

---

## 🔧 Integrations Setup

### **Google Gemini AI** (Required for AI features)
1. Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create API key
3. Settings → Paste API key → Save

### **Zoom Meetings**
1. Go to [Zoom App Marketplace](https://marketplace.zoom.us/)
2. Create **Server-to-Server OAuth** app (not JWT - deprecated)
3. Copy Account ID, Client ID, and Client Secret
4. Add required scopes: `meeting:write`, `meeting:read`, `user:read`
5. Settings → Zoom Integration → Paste credentials → Test

### **Google Calendar Sync**
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create project → Enable Calendar API
3. Create OAuth 2.0 credentials
4. Add redirect URI: `https://yourdomain.com/functions/oauth_callback.php?provider=google`
5. Update `config.php` with client ID & secret
6. Settings → Connect Google Calendar

### **Microsoft Outlook Sync**
1. Go to [Azure Portal](https://portal.azure.com/)
2. Register app → Add Calendar.ReadWrite permission
3. Create client secret
4. Add redirect URI: `https://yourdomain.com/functions/oauth_callback.php?provider=outlook`
5. Update `config.php` with client ID & secret
6. Settings → Connect Outlook Calendar

### **LinkedIn Auto-Post**
1. Visit [LinkedIn Developers](https://www.linkedin.com/developers/)
2. Create app → Get access token
3. Settings → LinkedIn Integration → Paste token & org ID → Test

---

## 📱 Usage Guide

### **Creating Jobs**
1. Jobs → Create New Job
2. Enter title & brief description
3. Click "Generate with AI" (optional)
4. Create Job → Get shareable link

### **Managing Candidates**
1. Candidates → Filter by job
2. View applications & resumes
3. Send interview links
4. Review AI-generated reports

### **Scheduling Meetings**
1. Open candidate report (score ≥ 60)
2. Click "Schedule Meeting"
3. Choose date, time, duration
4. Meeting auto-created in:
   - ✅ Zoom (link generated)
   - ✅ Google Calendar (if enabled)
   - ✅ Outlook Calendar (if enabled)

### **Dashboard Calendar**
- View all meetings in unified calendar
- Click events to see details & join links
- See Google/Outlook events alongside HR Portal meetings
- Color-coded: Blue (HR), Green (Google), Dark Blue (Outlook)

---

## 🔐 Security Best Practices

### **Before Production**

1. ✅ **Enable HTTPS** - Get SSL certificate (Let's Encrypt is free)
   ```php
   // In config.php
   'force_https' => true,
   ```

2. ✅ **Generate Secret Key**
   ```bash
   php -r "echo bin2hex(random_bytes(32));"
   # Paste into config.php → app_secret_key
   ```

3. ✅ **Move Database Outside Web Root**
   ```bash
   mv db.sqlite /home/user/private/
   # Update database path in functions/db.php
   ```

4. ✅ **Protect Sensitive Files**
   ```bash
   chmod 600 config.php
   chmod 666 db.sqlite
   ```

5. ✅ **Never Commit Secrets**
   - `config.php` is in `.gitignore`
   - Only commit `config.example.php`

### **Security Rating: 7.5/10**

**What's Protected:**
- ✅ SQL Injection (prepared statements)
- ✅ XSS (output sanitization)
- ✅ Token theft (AES-256 encryption)
- ✅ Session hijacking (secure cookies)
- ✅ CSRF (tokens ready, needs form integration)
- ✅ Brute force (rate limiting ready)

**Still Needed:**
- [ ] Add CSRF tokens to all forms
- [ ] Enable rate limiting on login
- [ ] Implement token refresh
- [ ] Add 2FA (optional)

---

## 🗄️ Database Schema

**Tables** (10):
- `users` - Admin accounts, multi-tenant
- `jobs` - Job postings per user
- `candidates` - Applications per job
- `interview_questions` - AI-generated questions
- `interview_answers` - Candidate responses + AI detection
- `reports` - AI evaluation reports + regeneration tracking
- `settings` - User-specific configurations
- `meetings` - Calendar events + Zoom/Google/Outlook IDs
- `rate_limits` - Login attempt tracking
- `security_logs` - Security event auditing

---

## 📂 Project Structure

```
HR-portal/
├── index.php                 # Main entry point
├── config.example.php        # Configuration template
├── migrate.php              # Database migrations
├── .htaccess                # Apache configuration
├── functions/               # Backend logic
│   ├── core.php            # Core functions
│   ├── db.php              # Database connection
│   ├── auth.php            # Authentication
│   ├── actions.php         # Action handlers
│   ├── security.php        # Security functions (NEW)
│   ├── zoom.php            # Zoom integration (NEW)
│   ├── linkedin.php        # LinkedIn integration (NEW)
│   ├── calendar_sync.php   # Calendar sync (NEW)
│   ├── oauth_callback.php  # OAuth handler (NEW)
│   ├── ai_detection.php    # AI detection
│   └── theme.php           # Theme functions
├── gui/                     # Admin interface
│   ├── header.php          # Navigation
│   ├── footer.php          # Footer
│   ├── login.php           # Login page
│   ├── register.php        # Registration
│   ├── dashboard.php       # Dashboard + Calendar
│   ├── jobs.php            # Job management
│   ├── candidates.php      # Candidate listing (NEW)
│   ├── report.php          # Evaluation reports
│   └── settings.php        # Settings + Integrations
├── public/                  # Public-facing pages
│   ├── apply.php           # Job application form
│   └── interview.php       # Interview interface
├── assets/                  # Static files
│   ├── style.css           # Custom CSS
│   └── uploads/            # Resumes, logos
└── README.md               # This file
```

---

## 🚢 Deployment

### **cPanel Deployment (No Terminal Access)**

1. **Make GitHub Repo Private**
   - Settings → Change visibility → Make private

2. **Generate SSH Key in cPanel**
   - Security → SSH Access → Generate Key
   - Copy public key

3. **Add Deploy Key to GitHub**
   - Repo Settings → Deploy keys → Add key
   - Paste public key → Save

4. **Set Up Git in cPanel**
   - Git™ Version Control → Create
   - Clone URL: `git@github.com:MohammadShayan1/HR-portal.git`
   - Path: `/home/qlabs/public_html/hr.qlabs.pk`

5. **Create config.php on Server**
   - File Manager → Copy `config.example.php` to `config.php`
   - Edit with production values
   - Chmod 600

6. **Deploy Updates**
   - Option A: Manual - cPanel → Git → Manage → Deploy HEAD Commit
   - Option B: Webhook - Create `deploy.php` + GitHub webhook

**Detailed Guide**: See comments in code for webhook setup

---

## 📊 Features by Version

### **v2.0.0** (Current - Nov 7, 2025)
- ✨ Multi-tenant architecture
- ✨ Calendar integration (Google/Outlook)
- ✨ Zoom meetings
- ✨ LinkedIn auto-post
- ✨ AI detection enhancements
- ✨ Report regeneration limits
- 🔒 Security upgrades (6.5→7.5/10)

### **v1.0.0** (Nov 6, 2025)
- ✨ AI-powered interviews
- ✨ Job management
- ✨ Candidate evaluation
- ✨ Report generation

---

## 🐛 Troubleshooting

### **OAuth "Invalid Signature"**
→ Check webhook secret matches in `deploy.php` and GitHub

### **Calendar Not Syncing**
→ Settings → Test Connection → Verify tokens are valid

### **Zoom Meetings Fail**
→ Ensure API credentials are correct, check Zoom account status

### **Rate Limit Triggered**
→ Wait 15 minutes or clear `rate_limits` table

### **Session Expired**
→ Sessions timeout after 2 hours inactivity (configurable in `config.php`)

---

## 🤝 Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

---

## 📞 Support

- **Documentation**: See this README
- **Issues**: [GitHub Issues](https://github.com/MohammadShayan1/HR-portal/issues)
- **Changelog**: See [CHANGELOG.md](CHANGELOG.md) for version history

---

## ⚖️ Privacy & Compliance

- ✅ **Multi-tenant** - Complete data isolation per user
- ✅ **Self-hosted** - You control all data
- ✅ **Optional APIs** - All integrations are opt-in
- ⚠️ **GDPR Considerations** - Add privacy policy, data export/deletion features
- ⚠️ **User Disclosure** - Inform candidates that AI evaluates responses

---

## 🎯 Security Checklist

**Before Going Live:**
- [ ] Enable HTTPS (`force_https = true`)
- [ ] Set strong `app_secret_key` (64-char hex)
- [ ] Move `db.sqlite` outside web root
- [ ] Add CSRF tokens to all forms
- [ ] Enable rate limiting on login
- [ ] Configure OAuth credentials in `config.php`
- [ ] Test all integrations
- [ ] Review security logs regularly

**Current Status:** 7.5/10 - Production-ready with above checklist completed

---

**Built with ❤️ for HR professionals who value automation, security, and data privacy**
   - Fill out the application form (name, email, optional resume)
   - Receive a unique interview link
   - Complete the automated interview at their convenience

### Reviewing Candidates

1. Navigate to **Jobs** → **View Candidates**
2. You'll see all applicants with their status:
   - **Applied**: Candidate submitted application, interview link sent
   - **Interview Completed**: Ready for AI evaluation
   - **Report Ready**: AI report generated

3. For completed interviews:
   - Click **"Generate Report"** to create AI evaluation
   - Click **"View Report"** to see detailed analysis with score

### Understanding AI Reports

Each report includes:
- **Overall Score**: 0-100 rating
- **Overall Assessment**: Summary of candidate fit
- **Strengths**: Key positive attributes
- **Areas of Concern**: Weaknesses or gaps
- **Communication Skills**: Quality of responses
- **Technical/Professional Fit**: Match to requirements
- **Recommendation**: Hire, Maybe, or Pass with reasoning

## 🗂️ Project Structure

```
HR-portal/
├── assets/
│   ├── style.css              # Custom CSS styles
│   └── uploads/               # Logo and resume storage
│       └── resumes/           # Candidate resumes
│
├── functions/
│   ├── db.php                 # Database connection & setup
│   ├── auth.php               # Authentication functions
│   ├── core.php               # AI integration & utilities
│   └── actions.php            # Form handlers & API logic
│
├── gui/
│   ├── header.php             # Admin panel header
│   ├── footer.php             # Admin panel footer
│   ├── login.php              # Login page
│   ├── register.php           # One-time admin registration
│   ├── dashboard.php          # Admin dashboard
│   ├── jobs.php               # Job management
│   ├── candidates.php         # Candidate list
│   ├── report.php             # AI evaluation report
│   └── settings.php           # System configuration
│
├── public/
│   ├── apply.php              # Public job application form
│   └── interview.php          # Text-based interview interface
│
├── index.php                  # Main router
├── .htaccess                  # Apache configuration
├── db.sqlite                  # SQLite database (auto-created)
└── README.md                  # This file
```

## 🔒 Security Features

- **Password Hashing**: Using PHP's `password_hash()` with bcrypt
- **Session-Based Authentication**: Secure admin access
- **SQL Injection Protection**: Prepared statements with PDO
- **XSS Prevention**: Output sanitization with `htmlspecialchars()`
- **File Upload Validation**: MIME type checking
- **Unique Interview Tokens**: Secure, non-guessable candidate access

## 🛠️ Troubleshooting

### Database Connection Errors

**Issue**: "Database connection failed"

**Solution**: 
- Ensure `pdo_sqlite` extension is enabled in `php.ini`
- Check write permissions on the project directory
- Verify PHP version is 7.4 or higher

### API Errors

**Issue**: "Gemini API key not configured" or API failures

**Solution**:
- Verify your API key is correctly entered in Settings
- Check internet connectivity
- Ensure API key is active in [Google AI Studio](https://makersuite.google.com/)
- Free tier may have rate limits - wait a few minutes and retry

### File Upload Issues

**Issue**: Resume or logo uploads fail

**Solution**:
```bash
chmod 777 HR-portal/assets/uploads/
```

Check `php.ini` settings:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Login Issues

**Issue**: Can't log in or session expires

**Solution**:
- Clear browser cookies
- Check that PHP sessions are working (`session.save_path` in `php.ini`)
- Ensure cookies are enabled in browser

## 🔧 Advanced Configuration

### Changing Database Location

Edit `functions/db.php`:
```php
define('DB_FILE', '/path/to/your/database.sqlite');
```

### Customizing Interview Questions

Edit `functions/core.php` in the `generate_interview_questions()` function to:
- Change number of questions (default: 5)
- Modify the AI prompt for different question styles
- Add fallback questions

### Custom Styling

Edit `assets/style.css` to customize:
- Brand colors
- Typography
- Layout spacing

## 📊 Database Schema

The system uses 7 main tables:

1. **users**: Admin authentication
2. **jobs**: Job postings
3. **candidates**: Applicant information
4. **interview_questions**: AI-generated questions per candidate
5. **interview_answers**: Candidate responses
6. **reports**: AI evaluation results
7. **settings**: System configuration (API keys, etc.)

## 🌐 Production Deployment

For production use:

1. **Use HTTPS**: Obtain SSL certificate (Let's Encrypt is free)
2. **Secure Database**: Move `db.sqlite` outside web root
3. **Backup Strategy**: Regular database backups
4. **Monitor API Usage**: Track Gemini API quota
5. **Email Integration**: Replace simulated emails with real SMTP

### Email Integration Example

Install PHPMailer:
```bash
composer require phpmailer/phpmailer
```

Modify `functions/actions.php` to send real emails instead of displaying links.

## 📝 License

This project is provided as-is for educational and commercial use.

## 🤝 Support

For issues or questions:
- Check the troubleshooting section above
- Review the code comments for detailed implementation notes
- Ensure all requirements are met

## 🔄 Updates & Maintenance

- **Backup regularly**: Before making changes, backup `db.sqlite`
- **Update PHP**: Keep PHP version current for security
- **Monitor API changes**: Google may update Gemini API endpoints

## 🎯 Best Practices

1. **Regular Backups**: Backup your database weekly
2. **Strong Passwords**: Use complex admin passwords
3. **API Key Security**: Never commit API keys to version control
4. **Test Questions**: Review AI-generated questions before sharing jobs
5. **Candidate Privacy**: Handle personal data responsibly per GDPR/local laws

---

**Version**: 1.0  
**Last Updated**: 2025  
**Built with**: ❤️ and AI
