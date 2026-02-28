# 🚀 PWT - PHP Web Terminal

> A lightweight, browser-based terminal for shared hosting environments where SSH/Terminal access is blocked.

![PHP](https://img.shields.io/badge/PHP-8.x-blue?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Hosting](https://img.shields.io/badge/Shared%20Hosting-Compatible-orange?style=flat-square)

---

## 📌 Background

Most affordable shared hosting providers in Indonesia (and worldwide) 
block SSH and Terminal access by default. This tool was built to solve 
that problem — providing a real terminal experience directly through 
the browser using PHP's execution functions.

---

## ✨ Features

- 🔐 **Password protected** login screen
- 💻 **Real-time command execution** via AJAX (no page reload)
- 📁 **`cd` navigation** with persistent directory across commands
- 📜 **Unlimited command history** — scroll up to see previous output
- ⬆️⬇️ **Arrow key history** navigation (like real terminal)
- 🎨 **Linux-like UI** (dark theme, green prompt)
- 🧠 **Auto-scroll** to latest output
- 🔄 **Fallback execution** — tries `proc_open`, `shell_exec`, `system`
- 📦 **Composer & Git** compatible (if available on server)

---

## 📸 Preview

> *(Work in progress)*

---

## 🚀 Quick Start

### 1. Upload
Upload `terminal.php` to your hosting's `public_html` folder via cPanel 
File Manager or FTP.

### 2. Set Password
Open the file and change the password on **line 3**:
```php
define('PASS', 'your-secure-password');
```

### 3. Access
Open in browser:
```
https://yourdomain.com/terminal.php
```

### 4. Login & Use
Login with your password. Start running commands:
```bash
ls -la
git status
git pull origin main
composer install
pwd
find /home/username -name "*project*"
```

---

## 🔧 Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 5.6+ (tested on 8.2) |
| Hosting | Shared/Cloud/VPS cPanel |
| Functions | `shell_exec` or `proc_open` or `system` (any one) |

---

## ⚙️ Supported Commands

Since this runs as the web server user (`nobody` or `syathiby`), 
you can run:
```bash
# Navigation
ls, ls -la, pwd, cd /path/to/dir, find

# Git
git status, git pull origin main, git log --oneline

# Composer
composer install, composer update, composer dump-autoload

# PHP
php artisan migrate, php -v, php -m

# Files
cat file.php, chmod 755 folder/, mkdir newfolder
```

> ⚠️ Commands like `sudo`, `apt`, `yum` won't work on shared hosting.

---

## 🛡️ Security

> **IMPORTANT:** This file gives command-line access to your server.

Best practices:
- ✅ Use a **strong, unique password**
- ✅ **Delete or rename** the file after use
- ✅ Move to a **hidden directory** (e.g., `/assets/tools/`)
- ✅ Whitelist your IP via `.htaccess`:

```apache
# .htaccess (same folder as terminal.php)
<Files "terminal.php">
  Order Deny,Allow
  Deny from all
  Allow from YOUR.IP.ADDRESS.HERE
</Files>
```

- ❌ Never commit with password exposed
- ❌ Never leave accessible on production server long-term

---

## 📂 Project Structure

```
php-web-terminal/
├── terminal.php        # Main terminal file (single file app)
├── term.log            # Command history log (auto-created)
└── README.md
```

---

## 🌐 Use Cases

- Run `composer install` on shared hosting without SSH
- Execute `git pull` for quick deployments
- Debug file permissions and directory structure
- Navigate addon domain folders in cPanel
- Run PHP Artisan commands for Laravel on shared hosting

---

## 🧑‍💻 Built with 💚

> Built as a real-world solution for managing multiple hosting accounts 
> without SSH access — tested on Indonesian hosting providers.

---

## 📄 License

MIT License — Free to use, modify, and distribute.

---

## ⭐ Support

If this helped you, give a ⭐ star on GitHub!  
It motivates me to build more open-source tools.
```
