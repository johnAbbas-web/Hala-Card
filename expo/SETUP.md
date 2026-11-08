# Sierah Expo 2026 — QR landing experience

| URL | What it is |
|---|---|
| `/expo/` | Mobile landing page: Welcome → Registration → animated pass → Discover Madinah |
| `/expo/qr.html` | Big-screen display with the large QR code and live "passes remaining" counter (open full-screen with F11) |
| `/expo/api/admin.php` | Staff dashboard: stats, search, CSV export, redeem codes, send launch invitations |
| `/expo/terms.html`, `/expo/privacy.html` | Offer terms and the expo privacy notice |

## Go-live steps (Hostinger)

1. Upload the `expo/` folder into `public_html/`.
2. Copy `expo/api/config.sample.php` to `expo/api/config.php` and fill in:
   - `site_url` (for example `https://halacard.co/expo`)
   - MySQL `db_dsn`, `db_user`, `db_pass` (you can reuse the existing Hala database; tables are prefixed `expo_`)
   - `app_secret`: a long random string
   - `mail_from`: must be a real mailbox on your domain (create it in Hostinger → Emails)
   - `admin_password_hash`: run `php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"`
   - `purchase_url`, plus `redeem_api_key` if your checkout will call the API
3. Open `/expo/` once. The tables are created automatically, or you can run `api/schema.sql` in phpMyAdmin.
4. Register yourself, confirm the email, and check the pass.
5. Open `/expo/qr.html` on the expo screen.

## How it works

- **Duplicates:** one reservation per inbox. Matching ignores case, `+tags`, and dots in Gmail addresses. If someone re-registers, the secure link goes to the inbox owner. The pass is never shown to whoever typed the email.
- **500 cap:** a place is claimed only when the visitor **confirms their email**, using one atomic database update, so the cap can't be exceeded. Visitors who confirm after the cap join the launch list.
- **Email links:** opening a link does not use it up (it only redirects). The page then confirms it. This stops email security scanners from burning links.
- **Redemption:** each reserved visitor gets a unique code (`SX26-XXXXXX`). Codes can be redeemed once, either in admin or by `POST api/redeem.php` with the header `X-Api-Key`, with body `{code, email, action: "check" | "redeem", ref}`.
- **After the event:** in admin, "Send next batch" emails launch invitations to 50 reserved visitors per click.
- **Privacy controls:** every email links to `api/manage.php`. From there visitors can stop marketing (one-click unsubscribe is supported) or cancel and delete their data.

Edit the experience cards in `js/experiences.js`.
