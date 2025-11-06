# Deploying to cPanel with Git

## Quick Setup Instructions

### 1. Create Git Repository in cPanel

1. Log into your **cPanel**
2. Go to **Git™ Version Control**
3. Click **Create**
4. Fill in the details:
   - **Clone URL**: Leave empty (we'll push from local)
   - **Repository Path**: `hr-portal` (or your preferred directory)
   - **Repository Name**: `HR-Portal`
5. Click **Create**

### 2. Get Your cPanel Git Remote URL

After creating the repository in cPanel, you'll see something like:
```
ssh://username@yourdomain.com:2222/home/username/repositories/hr-portal
```

### 3. Add cPanel as Remote and Push

In your local terminal (in the HR-portal directory):

```bash
# Add cPanel as remote
git remote add cpanel ssh://username@yourdomain.com:2222/home/username/repositories/hr-portal

# Push to cPanel
git push cpanel master
```

### 4. Deploy to Public Directory in cPanel

1. In cPanel Git™ Version Control, find your repository
2. Click **Manage**
3. Set **Deployment Path** to where you want the files (e.g., `/public_html/hr-portal`)
4. Click **Update** to deploy
5. Click **Pull or Deploy** → **Deploy HEAD Commit**

### 5. Post-Deployment Setup on cPanel

After deploying, SSH into your server or use cPanel File Manager terminal:

```bash
cd /public_html/hr-portal  # or your deployment path

# Run the database migration
php migrate.php

# Set proper permissions
chmod 755 functions/
chmod 755 assets/uploads/
chmod 777 assets/uploads/resumes/
chmod 666 db.sqlite  # Will be created after first use
```

### 6. Configure for Production

1. Update `.htaccess` if needed for your domain structure
2. Set your Gemini API key in Settings after logging in
3. Test the application at `yourdomain.com/hr-portal` (or your configured path)

---

## Automatic Deployment

Once set up, every time you make changes:

```bash
git add .
git commit -m "Your commit message"
git push cpanel master
```

Then in cPanel Git™ Version Control → **Pull or Deploy** → **Deploy HEAD Commit**

---

## Optional: Auto-Deploy on Push

To automatically deploy when you push, you can set up a post-receive hook:

1. SSH into your cPanel server
2. Navigate to: `~/repositories/hr-portal/.git/hooks/`
3. Create `post-receive` file:

```bash
#!/bin/bash
GIT_WORK_TREE=/home/username/public_html/hr-portal git checkout -f
```

4. Make it executable: `chmod +x post-receive`

Now deployments happen automatically on push! 🎉

---

## Troubleshooting

**If you get permission errors:**
```bash
chmod -R 755 /home/username/repositories/
```

**If database doesn't work:**
```bash
# Ensure SQLite extension is enabled in cPanel PHP Settings
# Create db.sqlite manually and set permissions:
touch db.sqlite
chmod 666 db.sqlite
```

**If you need to reset:**
```bash
# Delete local Git history and start fresh
rm -rf .git
git init
git add .
git commit -m "Fresh start"
git remote add cpanel ssh://username@yourdomain.com:2222/home/username/repositories/hr-portal
git push -f cpanel master
```
