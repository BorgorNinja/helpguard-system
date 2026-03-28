<?php
// ─── Gmail SMTP Settings ──────────────────────────────────────────────────────
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);                          // STARTTLS port

// ── Your Gmail address ──
define('MAIL_USERNAME',  'YOUR_EMAIL_HERE');       // Gmail account to use

// ── Gmail App Password (16 chars, no spaces) ──
define('MAIL_PASSWORD',  'YOUR_APP_PASSWORD_HERE');           // make your own app password here for security

// ── Sender name & address shown to recipients ──
define('MAIL_FROM',      'helpguard-no-reply@gmail.com');       // No need to change
define('MAIL_FROM_NAME', 'HelpGuard');

define('APP_URL', 'http://localhost');        // Make sure this matches the install path
