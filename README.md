# ⚡ PHP Web Terminal - PWT

> A lightweight, browser-based terminal for shared hosting environments 
> where SSH/Terminal access is blocked.

![PHP](https://img.shields.io/badge/PHP-8.x-blue?style=flat-square&logo=php)
![Laravel](https://img.shields.io/badge/Laravel-10.x-red?style=flat-square&logo=laravel)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Hosting](https://img.shields.io/badge/Shared%20Hosting-Compatible-orange?style=flat-square)

---

## 📌 Background

Most affordable shared hosting providers block SSH and Terminal access 
by default. This tool was built to solve that — providing a real terminal 
experience directly through the browser using PHP's execution functions.

> Built & battle-tested on Indonesian shared hosting (cPanel, PHP 8.2, CloudLinux).

---

## ✨ Features

- 🔐 **Password protected** login screen
- 💻 **Real-time AJAX execution** — no page reload
- 📁 **`cd` navigation** with persistent directory across commands
- 📜 **Unlimited scroll history** — scroll up to see all previous output
- ⬆️⬇️ **Arrow key history** navigation (like real terminal)
- 🎨 **Linux-like dark UI** with green prompt
- 🔄 **Smart PHP CLI detection** — uses correct PHP binary, not web PHP
- 📦 **Composer & Git & Laravel Artisan** compatible

---

## 📸 Preview

> *(work in progress)*

---

## 🚀 Quick Start

### 1. Upload
Upload `terminal.php` to your `public_html` via cPanel File Manager or FTP.

### 2. Set Password
Open the file and change on **line 3**:
```php
define('PASS', 'your-secure-password');
```

### 3. Access
```
https://yourdomain.com/terminal.php
```

---

## 📦 Install Composer (No SSH Required)

Since shared hosting blocks SSH, download Composer binary directly:

### Step 1 — Find PHP CLI binary
```bash
which php
# or use full path:
/opt/alt/php82/usr/bin/php --version
```

### Step 2 — Download Composer binary
```bash
curl -o composer.phar https://getcomposer.org/download/latest-stable/composer.phar
```

Or with wget:
```bash
wget -O composer.phar https://getcomposer.org/download/latest-stable/composer.phar
```

> ⚠️ **Do NOT use** `curl ... | php` or `php -r "copy(...)"` on shared hosting  
> — it may execute via web PHP and output HTML instead of binary.

### Step 3 — Verify
```bash
/opt/alt/php82/usr/bin/php composer.phar --version
# Composer version 2.x.x
```

### Step 4 — Create alias (optional but recommended)
```bash
alias php='/opt/alt/php82/usr/bin/php'
```

Now you can use `php composer.phar` normally.

---

## 🏗️ Deploy Laravel on Shared Hosting

Full workflow from zero to running Laravel — no SSH needed.

### 1. Clone / Pull from Git
```bash
git clone https://github.com/username/your-laravel-repo.git .
# or update existing:
git pull origin main
```

### 2. Install Dependencies
```bash
COMPOSER_HOME=/home/username/.composer /opt/alt/php82/usr/bin/php composer.phar install --no-dev --optimize-autoloader
```

> If `fileinfo` extension error, either:
> - Enable via **cPanel → Select PHP Version → Extensions → fileinfo**
> - Or add flag: `--ignore-platform-req=ext-fileinfo`

### 3. Setup Environment
```bash
# Copy env file
cp .env.example .env

# Open and edit database config
nano .env
# or edit via cPanel File Manager
```

Edit these values:
```env
APP_NAME=YourApp
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

### 4. Generate App Key
```bash
/opt/alt/php82/usr/bin/php artisan key:generate
```

### 5. Run Migration
```bash
/opt/alt/php82/usr/bin/php artisan migrate
```

With seeder:
```bash
/opt/alt/php82/usr/bin/php artisan migrate --seed
```

Fresh migration (reset all):
```bash
/opt/alt/php82/usr/bin/php artisan migrate:fresh --seed
```

### 6. Storage & Cache
```bash
# Link storage
/opt/alt/php82/usr/bin/php artisan storage:link

# Clear & optimize all cache
/opt/alt/php82/usr/bin/php artisan optimize:clear
/opt/alt/php82/usr/bin/php artisan optimize
```

### 7. Set Permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

---

## 🔁 Daily Deploy Workflow

After pushing code updates:

```bash
git pull origin main
COMPOSER_HOME=/home/username/.composer /opt/alt/php82/usr/bin/php composer.phar install --no-dev --optimize-autoloader
/opt/alt/php82/usr/bin/php artisan migrate
/opt/alt/php82/usr/bin/php artisan optimize:clear
/opt/alt/php82/usr/bin/php artisan optimize
```

---

## 🟢 Node.js Deploy (Coming Soon)

> Node.js workflow example will be added here, inshaAllah.

---

## ⚙️ Recommended PHP Extensions

Enable these in **cPanel → Select PHP Version → Extensions**:

```
✅ fileinfo     ✅ mbstring     ✅ curl
✅ zip          ✅ gd           ✅ pdo_mysql
✅ opcache      ✅ xml          ✅ openssl
✅ json         ✅ tokenizer    ✅ bcmath
✅ ctype        ✅ intl         ✅ sodium
```

---

## 🛡️ Security

> ⚠️ This file provides command-line access to your server. Handle with care.

Best practices:
- ✅ Use a **strong unique password**
- ✅ **Delete the file** after deployment is done
- ✅ Move to a **hidden path** (e.g. `/private/tools/`)
- ✅ Restrict by IP via `.htaccess`:

```apache
<Files "terminal.php">
  Order Deny,Allow
  Deny from all
  Allow from YOUR.IP.ADDRESS
</Files>
```

- ❌ Never expose on production long-term
- ❌ Never commit with password in plain text

---

## 📂 Project Structure

```
php-web-terminal/
├── terminal.php     # Single-file web terminal (AJAX-based)
├── term.log         # Command history log (auto-created, gitignored)
└── README.md
```

---

## 🌐 Tested On

| Hosting | PHP | Status |
|---------|-----|--------|
| Indonesian Shared Hosting (cPanel) | 8.2 | ✅ Working |
| CloudLinux + cPanel | 8.2 | ✅ Working |

---

## 📄 License

MIT License — Free to use, modify, and distribute.

---

## ⭐ Support

If this helped you, give it a ⭐ on GitHub!
