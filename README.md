# SSC Election Management System

A web app for running **Student Supreme Council (SSC)** elections at a school or university. Students log in, vote for candidates in each position, and admins manage the election and view results.

## What it does

**For voters (public side)**
- Log in to the voting page.
- See the positions and candidates (with partylists and photos).
- Cast one vote per position, then see a success page.
- Login is protected by a CAPTCHA (Cloudflare Turnstile) and rate limits.

**For admins (admin panel)**
- Log in with admin accounts and manage user accounts.
- Create and edit **positions**, **candidates** (with image upload) and **partylists**.
- Turn voting and login on or off, and switch on **maintenance mode**.
- View live **results** and export them as CSV.
- Look up individual votes and check the **audit logs** and **security events**.

## Tech stack

- **PHP 8+** with a small custom MVC structure (controllers, models, views, services, middleware).
- **MySQL / MariaDB** database (schema in `thrufxlf_ssc.sql`).
- **Composer** for packages; `vlucas/phpdotenv` loads settings from `.env`.
- **PHPMailer** for sending emails (SMTP, with SendGrid settings also supported).
- Plain HTML, CSS and JavaScript in `public/`.

## Project layout

```
app/           Controllers, Models, Services, Middleware, Views
config/        App, database, routes and asset settings
public/        Web root: the browser-facing files and assets
storage/       Logs, sessions, rate limits and exports (not committed)
PHPMailer/     Mail library
pentest/       Security testing notes and scripts
thrufxlf_ssc.sql  Database structure and data
bootstrap.php  Startup: loads settings, autoloader and database
```

## Getting started

1. **Requirements:** PHP 8.0 or newer, MySQL/MariaDB, Composer. On Windows, Laragon works well.
2. **Install packages:**
   ```
   composer install
   ```
3. **Create your settings file:** copy `.env.example` to `.env` and fill in your database details, session key, Turnstile keys and SMTP details. Never commit `.env`.
4. **Create the database:** import `thrufxlf_ssc.sql` into your MySQL database.
5. **Open the app** in your browser through the `public/` folder (for example `http://localhost/sscmvc/public`).

Generate a session key with PHP:
```
base64_encode(random_bytes(32))
```

## Security notes

- Secrets live only in `.env`, which is listed in `.gitignore`.
- Forms use CSRF tokens, login has rate limiting and lockout, and sessions expire.
- Admin pages require an admin login and a permission check.
- Security events and audit logs are recorded for review.

## License

Not yet specified. Add a license file if the project will be shared publicly.
