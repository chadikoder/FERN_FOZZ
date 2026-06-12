# Furn Fawz

Bakery ordering website for Furn Fawz, built with vanilla PHP, MySQL, HTML, CSS, and JavaScript.

Repository: `chadikoder/FERN_FOZZ`

## What It Does

Furn Fawz lets customers browse the bakery menu, add items to a cart, place an order, and send the saved order to the owner through WhatsApp. The owner has a protected admin area for managing products, categories, and order status.

## Features

- Public menu with category filters and search
- Product cards with missing-image fallbacks
- Session cart with add, update, remove, and clear actions
- Checkout form with server-side validation
- Orders saved to MySQL with order line items
- WhatsApp handoff after checkout
- Owner-only admin login
- Admin dashboard with order/product stats
- Order status management
- Product and category management
- Admin product image upload
- Repeatable database seed file
- CSRF protection for forms and API actions

## Tech Stack

- PHP 8+
- MySQL / MariaDB
- PDO
- HTML
- CSS
- JavaScript
- XAMPP-friendly local setup

## Project Structure

```text
admin/      Owner dashboard, orders, products, categories, login
api/        Cart and order endpoints
config/     App and database configuration
database/   Schema and seed data
includes/   Shared PHP helpers and templates
public/     Customer pages, CSS, JS, uploads
```

## Local Setup

1. Start Apache/PHP and MySQL from XAMPP.

2. Create the database:

   ```sql
   CREATE DATABASE furn_fawz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Import the schema and seed files:

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -uroot furn_fawz < database\schema.sql
   C:\xampp\mysql\bin\mysql.exe -uroot furn_fawz < database\seed.sql
   ```

4. Check the database credentials:

   ```php
   config/database.php
   ```

5. Start the local PHP server from the project root:

   ```powershell
   C:\xampp\php\php.exe -S 127.0.0.1:8000 -t .
   ```

6. Open the customer site:

   ```text
   http://127.0.0.1:8000/public/index.php
   ```

## Admin Access

Admin login:

```text
http://127.0.0.1:8000/admin/login.php
```

Demo owner account from `database/seed.sql`:

```text
Email: admin@furnfawz.com
Password: admin123
```

Change this demo password before real use.

## Product Images

The admin products page supports image upload. Uploaded files are saved in:

```text
public/uploads/products/
```

The database stores paths like:

```text
uploads/products/cheese-manakish.jpg
```

If an image file is missing, the public menu shows a clean fallback instead of a broken image.

## WhatsApp Orders

The WhatsApp destination number is configured in:

```text
config/app.php
database settings table: owner_phone
```

Use international format without `+`, for example:

```text
971501234567
```

## Security Notes

- Admin pages require login.
- Passwords are stored as hashes.
- Login regenerates the session ID.
- Forms and cart/order endpoints use CSRF tokens.
- Uploaded product images are validated by extension and image metadata.
- Demo credentials should be replaced before deployment.

## Useful URLs

```text
Public menu:  http://127.0.0.1:8000/public/index.php
Cart:         http://127.0.0.1:8000/public/cart.php
Checkout:     http://127.0.0.1:8000/public/checkout.php
Admin:        http://127.0.0.1:8000/admin/login.php
```

## Static Front-End Preview On GitHub Pages

GitHub Pages cannot run the PHP/MySQL app, but it can show a static front-end preview for design inspection.

This repo includes:

```text
docs/index.html
```

To enable the preview link:

1. Open the repository on GitHub.
2. Go to `Settings`.
3. Open `Pages`.
4. Under `Build and deployment`, choose `Deploy from a branch`.
5. Branch: `main`.
6. Folder: `/docs`.
7. Save.

After GitHub builds it, the preview link should be:

```text
https://chadikoder.github.io/FERN_FOZZ/
```

Use that link only for visual inspection. Real ordering needs PHP/MySQL hosting.

## Testing

PHP syntax check:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

JavaScript syntax check:

```powershell
node --check public\assets\js\app.js
```

## Real Customer Deployment

GitHub Pages cannot run this project because the app needs PHP sessions, MySQL, and server-side order saving. Use PHP hosting, cPanel hosting, or a VPS.

Production checklist:

1. Upload or clone this repository on the server.

2. Create a production MySQL database.

3. Import:

   ```text
   database/schema.sql
   database/seed.sql
   ```

4. Configure real server values using hosting environment variables when possible:

   ```text
   APP_NAME
   APP_BASE_URL
   OWNER_PHONE
   DB_HOST
   DB_NAME
   DB_USER
   DB_PASS
   DB_CHARSET
   ```

   See `.env.example` for names and example values. The `.env` file is ignored by git.

5. If your hosting does not support environment variables, update:

   ```text
   config/app.php
   config/database.php
   ```

6. Point the domain to the project root. The included `.htaccess` redirects `/` to `/public/index.php` and blocks direct access to private folders.

7. Confirm these URLs:

   ```text
   https://yourdomain.com
   https://yourdomain.com/admin/login.php
   ```

8. Change the demo admin password before customers use the site:

   ```bash
   php scripts/reset_admin_password.php admin@furnfawz.com YourNewStrongPassword
   ```

9. Update the WhatsApp owner number in the database `settings.owner_phone` or through the configured `OWNER_PHONE`.

10. Add real product images through the admin Products page.

Before real customers use it:

- Replace `admin123`.
- Use HTTPS.
- Use real DB credentials.
- Use the real WhatsApp number.
- Test one full order from menu to WhatsApp.
