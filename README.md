# HR Virtual Interview Portal

A production-ready, AI-powered recruitment platform that automates the entire interview lifecycle using Google's Gemini API. This self-hosted application allows HR professionals to create job postings, manage candidate applications, and conduct automated text-based interviews with AI-generated evaluation reports.

## 🌟 Features

- **AI-Powered Job Descriptions**: Generate professional job postings using Gemini AI
- **Automated Interviews**: AI generates tailored questions based on job descriptions
- **Smart Evaluation**: Comprehensive candidate reports with scoring (0-100)
- **Self-Hosted**: Complete control over your data with SQLite database
- **Single-Tenant**: Secure admin-only access for HR teams
- **No Framework Dependencies**: Pure PHP implementation for easy deployment

## 📋 Requirements

- **PHP 7.4 or higher** with the following extensions:
  - `pdo_sqlite` (SQLite database support)
  - `curl` (for API calls)
  - `fileinfo` (for file uploads)
- **Web Server**: Apache, Nginx, or any PHP-compatible server
- **Google Gemini API Key** (free tier available)

## 🚀 Installation

### Step 1: Download and Extract

1. Download or clone this repository
2. Extract the files to your web server's root directory (e.g., `htdocs`, `www`, or `public_html`)

### Step 2: Configure Web Server

**For Apache:**
- Ensure `mod_rewrite` is enabled
- The included `.htaccess` file will handle URL routing

**For Nginx:**
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Step 3: Set Permissions

Ensure the web server has write permissions for:
```bash
chmod 755 HR-portal/
chmod 777 HR-portal/assets/uploads/
```

The database file (`db.sqlite`) will be created automatically with proper permissions.

### Step 4: Initial Setup

1. Open your browser and navigate to:
   ```
   http://localhost/HR-portal/gui/register.php
   ```
   Or replace `localhost` with your domain/IP address.

2. Create your admin account:
   - Enter your email address
   - Create a secure password (minimum 6 characters)
   - Click "Register"

   **Note**: Registration is a one-time process. Once an admin account exists, the registration page will redirect to login.

3. Log in with your credentials

### Step 5: Configure Gemini API

1. Get your free Gemini API key:
   - Visit [Google AI Studio](https://makersuite.google.com/app/apikey)
   - Sign in with your Google account
   - Click "Create API Key"
   - Copy the generated key

2. In the HR Portal:
   - Navigate to **Settings** (gear icon in navigation)
   - Paste your Gemini API key
   - Optionally upload your company logo (PNG, JPG, or GIF)
   - Click "Save Settings"

## 📖 Usage Guide

### Creating Job Postings

1. Navigate to **Jobs** in the admin panel
2. Fill in the "Create New Job" form:
   - **Job Title**: e.g., "Senior Software Engineer"
   - **Brief Description**: e.g., "who will lead our backend development team"
3. Click **"Generate with AI"** to auto-generate a professional job description
4. Review and edit the generated description if needed
5. Click **"Create Job"**

### Managing Applications

1. Click **"Get Link"** next to any job to copy the application URL
2. Share this link with candidates via email, job boards, or social media
3. Candidates will:
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
