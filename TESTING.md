# Testing Guide - HR Virtual Interview Portal

## 🧪 Complete Testing Checklist

This guide helps you verify that all features of the HR Portal are working correctly.

---

## 📋 Pre-Testing Setup

### 1. System Requirements Verification
Visit: `http://localhost/HR-portal/check.php`

✅ Ensure all checks pass before proceeding

### 2. Get Gemini API Key
1. Go to: https://makersuite.google.com/app/apikey
2. Create and copy your API key
3. Keep it ready for step 4

---

## 🔐 Authentication Testing

### Test 1: Admin Registration
**URL**: `gui/register.php`

Steps:
1. Navigate to registration page
2. Enter email: `admin@test.com`
3. Enter password: `test123456`
4. Confirm password: `test123456`
5. Click "Register"

**Expected**: 
- ✅ Success message appears
- ✅ Redirected to login page
- ✅ Visiting registration again redirects to login

### Test 2: Login
**URL**: `gui/login.php`

Steps:
1. Enter registered email
2. Enter password
3. Click "Login"

**Expected**:
- ✅ Redirected to dashboard
- ✅ Navigation shows admin menu

### Test 3: Logout
**Action**: Click "Logout" in navigation

**Expected**:
- ✅ Redirected to login page
- ✅ Cannot access admin pages without logging in

---

## ⚙️ Settings Testing

### Test 4: Configure API Key
**URL**: `index.php?page=settings`

Steps:
1. Navigate to Settings
2. Paste Gemini API key
3. Click "Save Settings"

**Expected**:
- ✅ Success message appears
- ✅ Green badge shows "API Key Configured"

### Test 5: Upload Logo
**URL**: `index.php?page=settings`

Steps:
1. Choose an image file (PNG/JPG)
2. Click "Save Settings"

**Expected**:
- ✅ Logo appears in preview
- ✅ Logo visible in navigation header

---

## 💼 Job Management Testing

### Test 6: Manual Job Creation
**URL**: `index.php?page=jobs`

Steps:
1. Enter Job Title: "Software Developer"
2. Enter Brief: "who will build web applications"
3. Type full description manually
4. Click "Create Job"

**Expected**:
- ✅ Success message appears
- ✅ Job appears in jobs list
- ✅ Shows 0 candidates

### Test 7: AI Job Description Generation
**URL**: `index.php?page=jobs`

Steps:
1. Enter Job Title: "Marketing Manager"
2. Enter Brief: "who will lead our digital marketing campaigns"
3. Click "Generate with AI"
4. Wait for AI response
5. Review generated description
6. Click "Create Job"

**Expected**:
- ✅ Loading spinner appears
- ✅ Professional job description generated
- ✅ Description populates textarea
- ✅ Can edit before saving
- ✅ Job created successfully

### Test 8: Get Application Link
**URL**: `index.php?page=jobs`

Steps:
1. Click "Get Link" on any job
2. Modal appears with URL
3. Click "Copy" button

**Expected**:
- ✅ Modal shows complete URL
- ✅ URL includes job_id parameter
- ✅ Button shows "Copied!" confirmation

### Test 9: Delete Job
**URL**: `index.php?page=jobs`

Steps:
1. Click "Delete" on a job
2. Confirm deletion

**Expected**:
- ✅ Confirmation dialog appears
- ✅ Job removed from list
- ✅ Success message shown

---

## 📝 Application Testing

### Test 10: Submit Application
**URL**: `public/apply.php?job_id=1`

Steps:
1. Open application link in new browser/incognito
2. Verify job details displayed
3. Enter Name: "John Doe"
4. Enter Email: "john@example.com"
5. (Optional) Upload resume PDF
6. Click "Submit Application"

**Expected**:
- ✅ Job title and description visible
- ✅ Form validation works
- ✅ Success page appears
- ✅ Interview link displayed
- ✅ Can copy interview link

### Test 11: Invalid Email
**URL**: `public/apply.php?job_id=1`

Steps:
1. Enter invalid email: "notanemail"
2. Click submit

**Expected**:
- ✅ Error message appears
- ✅ Form not submitted

---

## 🎤 Interview Testing

### Test 12: Start Interview
**URL**: Use interview link from application

Steps:
1. Open interview link
2. Read welcome message
3. Click "Start Interview"

**Expected**:
- ✅ Welcome screen shows candidate name
- ✅ Instructions visible
- ✅ "Start Interview" button present

### Test 13: Answer Questions
**Continuation of Test 12**

Steps:
1. Read first question
2. Type answer (at least 50 words)
3. Click "Submit Answer"
4. Repeat for all 5 questions

**Expected**:
- ✅ Progress bar updates
- ✅ Question number increments
- ✅ Cannot submit empty answers
- ✅ Success message after each answer
- ✅ Next question loads automatically

### Test 14: Complete Interview
**Continuation of Test 13**

Steps:
1. Answer all 5 questions
2. Submit last answer

**Expected**:
- ✅ Completion screen appears
- ✅ Thank you message displayed
- ✅ Visiting link again shows "Already Completed"

### Test 15: Short Answer Validation
**URL**: During interview

Steps:
1. Type very short answer (< 10 chars)
2. Try to submit

**Expected**:
- ✅ Warning message appears
- ✅ Requires detailed answer

---

## 👥 Candidate Management Testing

### Test 16: View Candidates
**URL**: `index.php?page=candidates&job_id=1`

Steps:
1. Navigate to Jobs
2. Click "View Candidates" on job with applications

**Expected**:
- ✅ Candidate list displayed
- ✅ Shows name, email, status
- ✅ Status shows "Applied" initially
- ✅ Resume link works (if uploaded)

### Test 17: Interview Link Access
**URL**: `index.php?page=candidates&job_id=1`

Steps:
1. Click "Interview Link" on "Applied" candidate
2. Modal appears
3. Click "Copy"

**Expected**:
- ✅ Unique interview link shown
- ✅ Copy button works
- ✅ Link includes token

### Test 18: Check Status Update
**URL**: `index.php?page=candidates&job_id=1`

Steps:
1. After completing interview (Test 14)
2. Refresh candidates page

**Expected**:
- ✅ Status changed to "Interview Completed"
- ✅ "Generate Report" button appears

---

## 📊 Report Generation Testing

### Test 19: Generate AI Report
**URL**: `index.php?page=candidates&job_id=1`

Steps:
1. Find candidate with "Interview Completed" status
2. Click "Generate Report"
3. Wait for processing

**Expected**:
- ✅ Redirects back to candidates page
- ✅ Success message appears
- ✅ Status changes to "Report Ready"
- ✅ "View Report" button appears

### Test 20: View Report
**URL**: `index.php?page=report&candidate_id=X`

Steps:
1. Click "View Report" on candidate
2. Review report content

**Expected**:
- ✅ Candidate information displayed
- ✅ AI score visible (0-100)
- ✅ Detailed evaluation shown
- ✅ Report includes:
  - Overall assessment
  - Strengths
  - Weaknesses
  - Communication skills
  - Recommendation
- ✅ Interview transcript visible
- ✅ All Q&A pairs shown

### Test 21: Print Report
**URL**: `index.php?page=report&candidate_id=X`

Steps:
1. On report page
2. Click "Print Report" or Ctrl+P

**Expected**:
- ✅ Print preview opens
- ✅ Unwanted elements hidden (nav, buttons)
- ✅ Report content properly formatted

---

## 📈 Dashboard Testing

### Test 22: Dashboard Statistics
**URL**: `index.php` or `index.php?page=dashboard`

Steps:
1. Navigate to dashboard
2. Verify all statistics

**Expected**:
- ✅ Total Jobs count accurate
- ✅ Total Candidates count accurate
- ✅ Pending Interviews count accurate
- ✅ Completed Interviews count accurate
- ✅ Recent candidates list shows latest 5

### Test 23: Dashboard Links
**URL**: `index.php?page=dashboard`

Steps:
1. Click on recent candidate row

**Expected**:
- ✅ Navigates to candidates page
- ✅ Correct job selected

---

## 🔄 End-to-End Workflow Test

### Test 24: Complete Hiring Workflow
**Full process from job creation to report**

Steps:
1. **Create Job** (with AI assistance)
2. **Share Link** (copy application URL)
3. **Apply** (as candidate in new browser)
4. **Upload Resume**
5. **Complete Interview** (answer all questions)
6. **Review Application** (as admin)
7. **Generate Report** (AI evaluation)
8. **View Report** (detailed analysis)

**Expected**:
- ✅ All steps complete smoothly
- ✅ Data persists correctly
- ✅ No errors throughout process
- ✅ Status updates correctly
- ✅ AI generates quality content

---

## 🛡️ Security Testing

### Test 25: Unauthorized Access
**URL**: Various admin pages

Steps:
1. Log out
2. Try accessing: `index.php?page=jobs`
3. Try accessing: `index.php?page=candidates&job_id=1`

**Expected**:
- ✅ Redirected to login page
- ✅ Cannot access without authentication

### Test 26: Invalid Interview Token
**URL**: `public/interview.php?token=invalid123`

Steps:
1. Open URL with fake token

**Expected**:
- ✅ Error message shown
- ✅ Cannot proceed with interview

### Test 27: SQL Injection Protection
**URL**: Login or any form

Steps:
1. Try entering: `' OR '1'='1` in email field
2. Submit form

**Expected**:
- ✅ Input sanitized
- ✅ No SQL errors
- ✅ Login fails (if wrong credentials)

### Test 28: XSS Protection
**URL**: Any text input

Steps:
1. Try entering: `<script>alert('XSS')</script>`
2. Submit and view data

**Expected**:
- ✅ Script tags escaped
- ✅ No JavaScript execution
- ✅ Displays as text

---

## ⚠️ Error Handling Testing

### Test 29: Missing API Key
**URL**: `index.php?page=jobs`

Steps:
1. Clear API key in settings
2. Try generating job description with AI

**Expected**:
- ✅ Error message: "API key not configured"
- ✅ User directed to Settings

### Test 30: Network Error Simulation
**URL**: `index.php?page=jobs`

Steps:
1. Disconnect internet
2. Try AI job generation

**Expected**:
- ✅ Error message appears
- ✅ Form remains usable
- ✅ Can save manually

### Test 31: Invalid Job ID
**URL**: `public/apply.php?job_id=9999`

Steps:
1. Access URL with non-existent job ID

**Expected**:
- ✅ Error message: "Job not found"
- ✅ No crash or blank page

---

## 📱 Responsive Testing

### Test 32: Mobile View
**URL**: All pages

Steps:
1. Open DevTools
2. Toggle device toolbar
3. Test iPhone/Android views
4. Navigate all pages

**Expected**:
- ✅ Responsive layout
- ✅ Navigation works (hamburger menu)
- ✅ Forms usable
- ✅ Tables scroll horizontally
- ✅ Buttons accessible

---

## 🌐 Browser Compatibility

### Test 33: Cross-Browser Testing
**Browsers**: Chrome, Firefox, Safari, Edge

Steps:
1. Open portal in each browser
2. Test basic workflow

**Expected**:
- ✅ Works in all modern browsers
- ✅ Consistent appearance
- ✅ All features functional

---

## ✅ Testing Summary Template

Use this checklist:

```
□ Test 1-5: Authentication (5 tests)
□ Test 6-9: Job Management (4 tests)
□ Test 10-11: Applications (2 tests)
□ Test 12-15: Interviews (4 tests)
□ Test 16-18: Candidate Management (3 tests)
□ Test 19-21: Reports (3 tests)
□ Test 22-23: Dashboard (2 tests)
□ Test 24: End-to-End Workflow (1 test)
□ Test 25-28: Security (4 tests)
□ Test 29-31: Error Handling (3 tests)
□ Test 32: Responsive Design (1 test)
□ Test 33: Browser Compatibility (1 test)

Total: 33 Tests
```

---

## 🐛 Bug Reporting Template

If you find issues:

```
**Test #**: [Number]
**Test Name**: [Name]
**Expected**: [What should happen]
**Actual**: [What actually happened]
**Steps to Reproduce**:
1. 
2. 
3. 

**Environment**:
- PHP Version: 
- Browser: 
- OS: 
```

---

## 🎯 Success Criteria

All tests should pass with:
- ✅ No PHP errors
- ✅ No JavaScript console errors
- ✅ All features working as specified
- ✅ Data persisting correctly
- ✅ Secure against common vulnerabilities
- ✅ Responsive on all devices
- ✅ Compatible with modern browsers

---

**Happy Testing! 🚀**
