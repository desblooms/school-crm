# Hostinger Deployment Guide for School CRM

## Step 1: Prepare Files for Upload

### Update Configuration
1. Edit `config/config.php`:
   ```php
   define('DB_HOST', 'localhost'); // Hostinger MySQL host
   define('DB_NAME', 'your_database_name'); // From Hostinger cPanel
   define('DB_USER', 'your_database_user'); // From Hostinger cPanel  
   define('DB_PASS', 'your_database_password'); // From Hostinger cPanel
   define('BASE_URL', 'https://yourdomain.com/'); // Your actual domain
   ```

2. Set environment to production:
   ```php
   define('ENVIRONMENT', 'production');
   ```

### Remove Debug Files
Delete these files before uploading (security risk):
- `debug*.php`
- `test*.php`
- `fix*.php`
- `repair*.php`
- `check*.php`
- `diagnose*.php`
- `comprehensive-fix.php`
- `apply-migrations.php`

## Step 2: Database Setup

### In Hostinger cPanel:
1. **MySQL Databases** → Create new database
2. **MySQL Users** → Create new user with password
3. **Add user to database** with ALL PRIVILEGES
4. Note down: Database name, username, password

### Import Database:
1. Use **phpMyAdmin** in Hostinger cPanel
2. Import `database/school_crm.sql` if available
3. Or run `install.php` after upload to create tables

## Step 3: File Upload

### Using File Manager:
1. Go to **File Manager** in Hostinger cPanel
2. Navigate to `public_html` folder
3. Upload all files EXCEPT debug/test files
4. Extract if uploaded as ZIP

### File Permissions:
Set these folders to **755** permissions:
- `uploads/`
- `invoices/`
- `logs/`
- `backups/`

## Step 4: Deployment Check

1. Visit: `https://yourdomain.com/hostinger-deploy.php`
2. This will check:
   - PHP version compatibility
   - Required extensions
   - File permissions
   - Database connection
   - Configuration loading

3. **Fix any red errors** before proceeding

## Step 5: Install/Setup

### If fresh installation:
Visit: `https://yourdomain.com/install.php`

### If migrating existing data:
1. Import database via phpMyAdmin
2. Visit: `https://yourdomain.com/` to test

## Step 6: Security Cleanup

1. **Delete** `hostinger-deploy.php` after successful check
2. **Delete** `install.php` after installation
3. Verify `.htaccess` is working (blocks access to config files)

## Common 500 Error Fixes

### 1. PHP Version Issue
- Check PHP version in Hostinger cPanel
- Must be PHP 7.4 or higher
- Update if needed in cPanel → PHP Configuration

### 2. File Permissions
```bash
# Set correct permissions
chmod 755 uploads/ invoices/ logs/ backups/
chmod 644 *.php
chmod 644 .htaccess
```

### 3. Database Connection
- Verify database credentials in `config/config.php`
- Ensure database user has ALL PRIVILEGES
- Check database name format (usually `u123456_dbname`)

### 4. Missing Extensions
Add to `.htaccess` if needed:
```apache
php_value extension pdo_mysql
php_value extension mysqli
php_value extension curl
php_value extension json
php_value extension mbstring
```

### 5. Memory/Execution Limits
Add to `.htaccess`:
```apache
php_value memory_limit 256M
php_value max_execution_time 300
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

### 6. Error Logs
Check error logs in:
- Hostinger cPanel → Error Logs
- Your site's `logs/php_errors.log`

### 7. SSL/HTTPS Issues
If using HTTPS, uncomment in `.htaccess`:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Environment Variables (Optional)

Create `.env` file in root:
```env
APP_ENV=production
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password
BASE_URL=https://yourdomain.com/
SECURITY_KEY=your_random_secure_key_here
```

## Final Checklist

- [ ] Database created and configured
- [ ] All files uploaded to `public_html`
- [ ] File permissions set (755 for directories, 644 for files)
- [ ] `hostinger-deploy.php` shows all green checkmarks
- [ ] `install.php` completed successfully
- [ ] Debug files deleted
- [ ] `.htaccess` protecting sensitive files
- [ ] Site loads without errors
- [ ] Login functionality works

## Support

If issues persist:
1. Check Hostinger error logs
2. Check `logs/php_errors.log`
3. Verify database connection via phpMyAdmin
4. Contact Hostinger support for server-specific issues