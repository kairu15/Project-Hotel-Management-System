# Git Guide: Uploading PHP Files to GitHub

A comprehensive step-by-step guide for uploading PHP files to GitHub and pushing updated files using Git.

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [Uploading PHP Files for the First Time](#uploading-php-files-for-the-first-time)
4. [Pushing Updated Files](#pushing-updated-files)
5. [Common Git Commands Reference](#common-git-commands-reference)
6. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before you begin, ensure you have the following:

- **Git installed** on your computer ([Download Git](https://git-scm.com/downloads))
- **A GitHub account** ([Sign up](https://github.com/join))
- **PHP files** ready to upload
- **Basic command line knowledge**

### Verify Git Installation

Open your terminal/command prompt and run:

```bash
git --version
```

You should see output like: `git version 2.40.0`

---

## Initial Setup

### Step 1: Configure Git Identity

Set your name and email (used for commits):

```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

### Step 2: Generate SSH Key (Recommended)

For secure authentication with GitHub:

```bash
ssh-keygen -t ed25519 -C "your.email@example.com"
```

Press Enter to accept default file location, then add the key to GitHub:

```bash
cat ~/.ssh/id_ed25519.pub
```

Copy the output and paste it into GitHub → Settings → SSH and GPG keys → New SSH key.

---

## Uploading PHP Files for the First Time

### Step 1: Create a New Repository on GitHub

1. Log in to [GitHub](https://github.com)
2. Click the **+** icon → **New repository**
3. Enter a **Repository name** (e.g., `my-php-project`)
4. Add an optional **Description**
5. Choose **Public** or **Private**
6. **DO NOT** initialize with README (we'll do this locally)
7. Click **Create repository**

### Step 2: Initialize Local Git Repository

Navigate to your PHP project folder:

```bash
cd /path/to/your/php-project
```

Initialize Git:

```bash
git init
```

### Step 3: Add PHP Files to Git

Add all PHP files (and other project files):

```bash
git add .
```

Or add specific files:

```bash
git add index.php
git add config.php
```

### Step 4: Create Initial Commit

```bash
git commit -m "Initial commit: Add PHP project files"
```

### Step 5: Connect to GitHub Repository

Copy the repository URL from GitHub, then:

**For HTTPS:**
```bash
git remote add origin https://github.com/username/repository-name.git
```

**For SSH:**
```bash
git remote add origin git@github.com:username/repository-name.git
```

### Step 6: Push to GitHub

```bash
git branch -M main
git push -u origin main
```

Your PHP files are now on GitHub!

---

## Pushing Updated Files

After making changes to your PHP files, follow these steps:

### Step 1: Check File Status

See which files have been modified:

```bash
git status
```

### Step 2: Stage Changes

**Option A: Stage all modified files**
```bash
git add .
```

**Option B: Stage specific files**
```bash
git add filename.php
```

**Option C: Stage only modified files (not new files)**
```bash
git add -u
```

### Step 3: Review Changes (Optional but Recommended)

```bash
git diff --cached
```

### Step 4: Commit Changes

```bash
git commit -m "Description of changes made"
```

**Good commit message examples:**
- `"Fix login validation bug"
- `"Add user registration feature"
- `"Update database connection config"
- `"Refactor booking system code"

### Step 5: Push to GitHub

```bash
git push origin main
```

Or simply:

```bash
git push
```

---

## Common Git Commands Reference

| Command | Description |
|---------|-------------|
| `git status` | Check which files are modified/staged |
| `git add .` | Stage all changes |
| `git add filename.php` | Stage specific file |
| `git commit -m "message"` | Commit staged changes |
| `git push` | Push commits to GitHub |
| `git pull` | Download latest changes from GitHub |
| `git log` | View commit history |
| `git log --oneline` | View compact commit history |
| `git diff` | Show unstaged changes |
| `git diff --cached` | Show staged changes |
| `git rm --cached filename` | Unstage a file |
| `git checkout -- filename` | Discard local changes |
| `git clone URL` | Copy a repository from GitHub |

---

## Troubleshooting

### Issue: "fatal: not a git repository"

**Solution:** Run `git init` in your project folder first.

### Issue: "Permission denied (publickey)"

**Solution:** 
1. Check SSH key: `cat ~/.ssh/id_ed25519.pub`
2. Ensure it's added to GitHub Settings → SSH keys
3. Test connection: `ssh -T git@github.com`

### Issue: "Updates were rejected because the remote contains work"

**Solution:** Pull latest changes first, then push:

```bash
git pull origin main
git push origin main
```

### Issue: Merge conflicts

**Solution:** Edit conflicted files to resolve conflicts (look for `<<<<<<<`, `=======`, `>>>>>>>` markers), then:

```bash
git add .
git commit -m "Resolve merge conflicts"
git push
```

### Issue: Wrong commit message

**Solution:** Amend the last commit:

```bash
git commit --amend -m "New commit message"
git push --force-with-lease
```

---

## Best Practices

1. **Commit frequently** - Make small, logical commits
2. **Write clear commit messages** - Describe what changed and why
3. **Pull before pushing** - Always sync with remote first
4. **Use .gitignore** - Exclude sensitive files (passwords, API keys)
5. **Create branches** - Use feature branches for major changes
6. **Review changes** - Check `git diff` before committing

---

## Quick Reference: Complete Workflow

```bash
# First time setup
git init
git add .
git commit -m "Initial commit"
git remote add origin git@github.com:username/repo.git
git branch -M main
git push -u origin main

# Daily workflow
git status                    # Check changes
git add .                     # Stage changes
git commit -m "Message"       # Commit
git push                      # Push to GitHub
```

---

## Need Help?

- [Git Documentation](https://git-scm.com/doc)
- [GitHub Docs](https://docs.github.com)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)

---

*Created for PHP developers getting started with Git and GitHub.*
