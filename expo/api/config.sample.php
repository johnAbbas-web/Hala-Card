<?php
/**
 * Sierah Expo 2026 — configuration.
 *
 * Copy this file to config.php and fill in the values. config.php is blocked
 * from public access by expo/.htaccess, but keep it out of any shared folders.
 */
return [
    // Public URL of the expo folder, no trailing slash.
    'site_url' => 'https://halacard.co/expo',

    // Database. On Hostinger use MySQL:
    //   'db_dsn' => 'mysql:host=localhost;dbname=u386108958_hala_app;charset=utf8mb4',
    // For local testing you can use SQLite instead:
    //   'db_dsn' => 'sqlite:' . __DIR__ . '/../data/expo.sqlite',
    'db_dsn'  => 'mysql:host=localhost;dbname=YOUR_DB_NAME;charset=utf8mb4',
    'db_user' => 'YOUR_DB_USER',
    'db_pass' => 'YOUR_DB_PASSWORD',

    // Long random string (64+ chars). Used to sign unsubscribe links and hash IPs.
    // Generate one with:  php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',

    // Promotion rules
    'promo_limit'       => 500,          // first N verified registrants
    'registration_open' => true,         // set false to close sign-ups
    'verify_hours'      => 48,           // how long a confirmation link stays valid
    'terms_version'     => '2026-11-08', // bump whenever terms.html changes

    // Email. 'smtp' (recommended) sends through your mailbox's SMTP server.
    // 'mail' uses PHP mail(). 'log' writes emails to data/mail.log instead of sending (local testing).
    'mail_transport' => 'smtp',
    // SMTP settings (used when mail_transport is 'smtp').
    //   Hostinger: smtp.hostinger.com, port 465, ssl, user = full mailbox address, pass = mailbox password
    //   Gmail:     smtp.gmail.com, port 465, ssl, user = your Gmail, pass = a Google App Password (not your normal password)
    'smtp_host'   => 'smtp.hostinger.com',
    'smtp_port'   => 465,
    'smtp_secure' => 'ssl',
    'smtp_user'   => '',
    'smtp_pass'   => '',
    'mail_from'      => 'hello@halacard.co',
    'mail_from_name' => 'Hala Card',
    'mail_reply_to'  => 'info@halacard.co',

    // Where launch invitations send people to buy. The reservation code is appended as ?code=XXXX.
    'purchase_url' => 'https://halacard.co/',

    // Admin dashboard (expo/api/admin.php). Store a HASH, not the password:
    //   php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
    'admin_password_hash' => '',

    // Key for the purchase system to call redeem.php (header X-Api-Key). Empty disables the API.
    'redeem_api_key' => '',
];
