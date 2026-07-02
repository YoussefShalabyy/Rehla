# 🚀 Rehla (VistaStay) - Pre-Deployment Checklist

This checklist outlines the critical steps required to prepare the Rehla Backend API for a production environment. 
**DO NOT DEPLOY** without completing every single item on this list.

---

## 1. 🔐 Environment & Security (`.env`)
- [ ] **`APP_ENV`**: Set to `production`.
- [ ] **`APP_DEBUG`**: Set to `false` (CRITICAL: Never leave this true in production).
- [ ] **`APP_URL`**: Set to the exact production domain (e.g., `https://api.vistastay.com`).
- [ ] **`APP_KEY`**: Ensure a strong app key is set (run `php artisan key:generate` if empty).
- [ ] **Database Credentials**: Update DB host, username, and password to production values.
- [ ] **`CORS_ALLOWED_ORIGINS`**: Set strictly to the frontend domains (e.g., `https://vistastay.com,https://admin.vistastay.com`). Do not leave as `*`.

## 2. 💳 Third-Party API Keys
- [ ] **Paymob**: Add production `PAYMOB_API_KEY`, `PAYMOB_INTEGRATION_ID`, `PAYMOB_IFRAME_ID`, and `PAYMOB_HMAC_SECRET`.
- [ ] **Cloudinary**: Add production `CLOUDINARY_URL`.
- [ ] **Expo Push Notifications**: Ensure `EXPO_ACCESS_TOKEN` is set if using a secure Expo push setup.

## 3. 🚀 Performance Optimization Commands
Run the following Artisan commands on the production server to cache configurations, routes, and views. This significantly speeds up the framework.
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
*(Note: Whenever you update the `.env` file or routes in the future, you must run these commands again).*

## 4. 🗄️ Database & Migrations
- [ ] Backup the production database if doing a subsequent deployment.
- [ ] Run migrations safely: 
  ```bash
  php artisan migrate --force
  ```
  *(`--force` is required in production environments to bypass the warning prompt).*
- [ ] **DO NOT** run `--seed` unless you have explicit production seeders (e.g., `PlatformSettingsSeeder`). **NEVER** run `DemoDataSeeder` in production.

## 5. ⚙️ Server Configuration (Nginx / Apache)
- [ ] **Document Root**: Ensure the web server points strictly to the `/public` directory of the Laravel app.
- [ ] **SSL/HTTPS**: Install an SSL certificate (e.g., via Let's Encrypt). The `SecurityHeadersMiddleware` will force HTTPS internally.
- [ ] **File Permissions**:
  Ensure the web server user (e.g., `www-data` or `nginx`) has write permissions to `storage` and `bootstrap/cache`:
  ```bash
  chmod -R 775 storage bootstrap/cache
  chown -R www-data:www-data storage bootstrap/cache
  ```

## 6. 🕒 Queues & Background Jobs (Future-Proofing)
*(If queues are used for emails or long-running tasks in the future)*
- [ ] Set up **Supervisor** (or similar process manager) to keep `php artisan queue:work` running continuously.
- [ ] Configure the queue driver in `.env` (e.g., `QUEUE_CONNECTION=redis` or `database`).

## 7. 🛡️ Final Sanity Checks
- [ ] Send a test request to `GET /api/v1/health` and verify a `200 OK` response.
- [ ] Perform a test registration and login flow via Postman to verify database connectivity and token generation.
- [ ] Monitor the `storage/logs/laravel.log` file after the first few requests to catch any hidden permissions or configuration errors.

---
**Done?** 🎉 You are now ready to handle real users and real money!
