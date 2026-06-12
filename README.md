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

## Testing

PHP syntax check:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { C:\xampp\php\php.exe -l $_.FullName }
```

JavaScript syntax check:

```powershell
node --check public\assets\js\app.js
```
