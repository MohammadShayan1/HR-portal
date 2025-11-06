# 🚀 Quick Start Guide - HR Virtual Interview Portal

## ⚡ Fast Setup (5 Minutes)

### 1️⃣ First Access
Navigate to: `http://localhost/HR-portal/gui/register.php`

Create your admin account:
- Email: your-email@example.com
- Password: (minimum 6 characters)

### 2️⃣ Get Your Free Gemini API Key
1. Visit: https://makersuite.google.com/app/apikey
2. Sign in with Google
3. Click "Create API Key"
4. Copy the key (starts with "AIza...")

### 3️⃣ Configure the Portal
1. Log in to the admin panel
2. Go to **Settings** (gear icon)
3. Paste your Gemini API Key
4. (Optional) Upload your company logo
5. Click "Save Settings"

### 4️⃣ Create Your First Job
1. Go to **Jobs** section
2. Enter:
   - Job Title: "Software Developer"
   - Brief: "who will build web applications"
3. Click **"Generate with AI"** 
4. Review the AI-generated job description
5. Click **"Create Job"**

### 5️⃣ Share & Test
1. Click **"Get Link"** next to your job
2. Copy the application URL
3. Open in a new browser/incognito window to test
4. Fill out the application form
5. Complete the automated interview
6. Return to admin panel to generate AI report

---

## 📋 Common Tasks

### Share a Job Posting
```
Jobs → Get Link → Copy → Share via email/LinkedIn/job boards
```

### View Candidates
```
Jobs → View Candidates → See all applicants and their status
```

### Generate Interview Report
```
Candidates → Find "Interview Completed" status → Generate Report
```

### View Detailed Report
```
Candidates → Find "Report Ready" status → View Report
```

---

## 🔑 Access URLs

- **Admin Panel**: `http://localhost/HR-portal/`
- **Login**: `http://localhost/HR-portal/gui/login.php`
- **Settings**: `http://localhost/HR-portal/index.php?page=settings`
- **Application Form**: `http://localhost/HR-portal/public/apply.php?job_id=1`

---

## 💡 Tips for Best Results

### Creating Job Posts
- Use descriptive job titles
- Provide specific requirements in the brief
- Let AI generate the full description, then customize

### Interview Process
- Each candidate gets 5 AI-generated questions
- Questions are tailored to the job description
- Candidates can take the interview at their own pace

### AI Reports
- Wait until interview status shows "Interview Completed"
- Reports include 0-100 score and detailed analysis
- Use reports to shortlist candidates for real interviews

---

## 🎯 Workflow Overview

```
1. Create Job → 2. Share Link → 3. Candidate Applies → 
4. Candidate Interviews → 5. Generate Report → 6. Review & Hire
```

---

## 🆘 Quick Troubleshooting

**Problem**: "API key not configured"  
**Solution**: Go to Settings and add your Gemini API key

**Problem**: Can't see AI-generated description  
**Solution**: Check internet connection and API key validity

**Problem**: Can't access admin panel  
**Solution**: Make sure you're logged in at `gui/login.php`

**Problem**: Candidates can't access interview  
**Solution**: Verify the interview link includes the token parameter

---

## 📞 Need Help?

Check the full README.md for:
- Detailed installation instructions
- Security best practices
- Advanced configuration
- Production deployment guide

---

**That's it! You're ready to start hiring with AI! 🎉**
