# Hala PWA v2 — Complete Setup Guide

## File Structure
```
hala-v2/
├── index.html          ← Splash screen
├── login.html          ← Login + Signup (combined, email only)
├── home.html           ← Dashboard
├── map.html            ← Full map
├── card.html           ← Hala Pass
├── profile.html        ← Profile
├── manifest.json       ← PWA config
├── service-worker.js   ← Offline support
├── .htaccess           ← URL routing + HTTPS redirect
├── css/
│   └── styles.css
├── js/
│   ├── firebase-config.js  ← YOU FILL IN YOUR KEYS
│   └── app.js
├── api/
│   ├── bootstrap.php       ← DB config + auth — YOU FILL IN
│   ├── schema.sql          ← Run once in MySQL
│   ├── places.php
│   ├── favourites.php
│   ├── users.php
│   └── admin.php
├── admin/
│   └── index.html          ← Separate admin panel
├── assets/
│   ├── logo.png            ← YOUR LOGO HERE
│   └── icons/
│       ├── icon-192.png
│       └── icon-512.png
└── uploads/                ← Images go here (auto-created)
```

---

## STEP 1 — Firebase Setup (Authentication only)

Firebase is ONLY used for login/signup. No Firestore, no Firebase Storage.

1. Go to https://console.firebase.google.com
2. Click **Add project** → name it `hala-pwa` → Create
3. Click **Authentication** → Get started
4. Click **Email/Password** → Enable → Save
5. Click the **gear icon** → **Project settings**
6. Scroll to **Your apps** → click **</>** → Register app → name it `hala-web`
7. Copy the config block — paste values into `js/firebase-config.js`

```javascript
// js/firebase-config.js — fill this in:
const firebaseConfig = {
  apiKey:            "AIzaSy...",
  authDomain:        "hala-pwa.firebaseapp.com",
  projectId:         "hala-pwa",
  storageBucket:     "hala-pwa.appspot.com",
  messagingSenderId: "123456789",
  appId:             "1:123456789:web:abc123"
};
// Also update:
const API_BASE = "https://yourdomain.com/api";
```

---

## STEP 2 — Hostinger MySQL Database

1. Log in to Hostinger → **Hosting** → **Manage**
2. Click **Databases** → **MySQL Databases**
3. Create a new database — note the database name, username, password
4. Click **phpMyAdmin** → select your database → click **SQL** tab
5. Paste the entire contents of `api/schema.sql` → click **Go**
6. You'll see all tables created (places, users, favourites, categories)

Now fill in `api/bootstrap.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('FIREBASE_PROJECT_ID', 'hala-pwa');  // must match Firebase projectId
```

---

## STEP 3 — Set Admin Password

In `api/bootstrap.php`, find this line and set a strong password:
```php
if ($user !== 'hala_admin' || $pass !== 'PASTE_STRONG_ADMIN_PASSWORD') {
```

Then in `api/admin.php`, match it:
```php
if ($u === 'hala_admin' && $p === 'PASTE_STRONG_ADMIN_PASSWORD') {
```

And in `admin/index.html`:
```javascript
const API = 'https://yourdomain.com/api/admin';
```

---

## STEP 4 — Mapbox Token

1. Go to https://www.mapbox.com → Sign up (free)
2. Verify email → go to Account → copy **Default public token** (starts with `pk.eyJ1...`)
3. In `map.html` find:
```javascript
const MAPBOX_TOKEN = 'PASTE_YOUR_MAPBOX_TOKEN_HERE';
```
Replace with your token.

---

## STEP 5 — Add Your Logo

1. Copy your logo PNG into `assets/logo.png`
2. Create two icon versions for PWA installation:
   - `assets/icons/icon-192.png` — 192×192px, logo on #061506 background
   - `assets/icons/icon-512.png` — 512×512px, same
   - Free tool: https://realfavicongenerator.net

---

## STEP 6 — Upload to Hostinger

1. Log in to Hostinger → **File Manager**
2. Navigate to `public_html` (or your domain's root folder)
3. Upload ALL files maintaining the exact folder structure
4. Make sure these folders exist after upload:
   - `public_html/api/`
   - `public_html/admin/`
   - `public_html/uploads/` (create if missing, must be writable)
5. Set `uploads/` folder permissions to **755**:
   - Right-click folder → Permissions → 755

**Enable SSL (required for PWA):**
- Hostinger → Security → SSL → Force HTTPS → Enable

---

## STEP 7 — Test the App

1. Visit `https://yourdomain.com` → splash → login
2. Create an account → you're in
3. Visit `https://yourdomain.com/admin/` → log in with `hala_admin` / your password
4. Add your first location → it appears on the map immediately

---

## STEP 8 — Adding Locations (Admin Panel)

In the admin panel at `yoursite.com/admin/`:

| Field | Notes |
|---|---|
| Name | e.g. Masjid Al-Nabawi |
| Category | Pick from dropdown |
| Coordinates | Right-click on Google Maps → copy Lat, Lng |
| Offer | e.g. "10% off all beverages" |
| Offer expiry | Pick a date |
| Featured | Tick to show as the hero deal on Home screen |
| Image | Upload JPG/PNG, max 5MB |

**Key Madinah coordinates:**
- Masjid Al-Nabawi: `24.4672, 39.6112`
- Masjid Quba: `24.4397, 39.6155`
- Al-Baqi Cemetery: `24.4672, 39.6142`

---

## STEP 9 — Install as PWA (test on phone)

**Android:**
- Open Chrome → visit your URL
- Tap ⋮ menu → "Add to Home screen" → Install
- Opens fullscreen like a native app

**iPhone:**
- Open Safari → visit your URL
- Tap Share button → "Add to Home Screen" → Add

---

## STEP 10 — Wrap for App Stores (later)

When ready for Play Store / App Store:
1. Go to https://www.pwabuilder.com
2. Enter your URL → it analyses your PWA
3. Click **Package for stores** → download Android APK or iOS package
4. Submit to Google Play / Apple App Store

---

## Troubleshooting

| Problem | Fix |
|---|---|
| Map not showing | Check Mapbox token in map.html |
| Login not working | Check Firebase config keys match exactly |
| API 401 errors | Firebase projectId in bootstrap.php must match Firebase console |
| Images not uploading | Check uploads/ folder permissions are 755 |
| Admin can't login | Password in bootstrap.php and admin.php must match |
| PHP errors | Check Hostinger PHP version is 8.0+ |

---

## Security Notes for Production

1. In `.htaccess`, replace `*` in CORS header with your actual domain
2. Change default admin password to something very strong (20+ chars)
3. In Hostinger, enable **ModSecurity** for extra protection
4. The PHP Firebase JWT verification fetches Google's public keys — this works automatically
