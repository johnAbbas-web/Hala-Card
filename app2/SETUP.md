# HALA PWA — Complete Setup Guide

## Your File Structure
```
hala-pwa/
├── index.html          ← Splash screen
├── welcome.html        ← Welcome / landing
├── login.html          ← Sign in
├── signup.html         ← Create account
├── home.html           ← Main home screen
├── map.html            ← Full map
├── pass.html           ← Hala Pass
├── profile.html        ← User profile
├── admin.html          ← Admin dashboard
├── manifest.json       ← PWA config
├── service-worker.js   ← Offline caching
├── css/
│   └── styles.css
├── js/
│   ├── firebase-config.js  ← YOU FILL THIS IN
│   ├── auth.js
│   └── register-sw.js
└── assets/
    ├── logo.png            ← YOUR LOGO FILE GOES HERE
    └── icons/
        ├── icon-192.png    ← App icon (192×192)
        └── icon-512.png    ← App icon (512×512)
```

---

## STEP 1 — Create Your Firebase Project (Free)

1. Go to https://console.firebase.google.com
2. Click **"Add project"**
3. Name it: `hala-pwa` → click Continue
4. Disable Google Analytics (not needed for now) → click **Create project**
5. Wait ~30 seconds for it to create

---

## STEP 2 — Enable Firebase Authentication

1. In your Firebase project, click **Authentication** in the left menu
2. Click **Get started**
3. Click **Email/Password** → toggle to **Enabled** → Save
4. Click **Add new provider** → **Google** → toggle Enabled
   - Set project support email → Save
5. (Optional) Click **Anonymous** → Enable → Save (for guest mode)

---

## STEP 3 — Create Firestore Database

1. Click **Firestore Database** in the left menu
2. Click **Create database**
3. Choose **Start in test mode** (we'll add proper rules later)
4. Select region closest to Saudi Arabia: **europe-west** or **me-central1**
5. Click **Done**

### Firestore Security Rules (paste these in after setup):
Go to Firestore → Rules tab → paste:

```
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {

    // Users can read/write their own profile
    match /users/{userId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }

    // All authenticated users can read locations
    match /locations/{locationId} {
      allow read: if request.auth != null;
      // Only write if you're an admin (set this to your admin UID later)
      allow write: if false;
    }
  }
}
```

---

## STEP 4 — Enable Firebase Storage (for images)

1. Click **Storage** in left menu
2. Click **Get started** → **Next** → **Done**
3. Go to Rules tab → change `allow read, write: if false;` to:
```
rules_version = '2';
service firebase.storage {
  match /b/{bucket}/o {
    match /locations/{allPaths=**} {
      allow read: if true;
      allow write: if request.auth != null;
    }
  }
}
```

---

## STEP 5 — Get Your Firebase Config Keys

1. Click the **gear icon** (top left) → **Project settings**
2. Scroll down to **"Your apps"** section
3. Click **</>** (web app icon) → Register app
4. Name it `hala-web` → click **Register app**
5. You'll see a block like this — **copy these values:**

```javascript
const firebaseConfig = {
  apiKey: "AIzaSy...",
  authDomain: "hala-pwa.firebaseapp.com",
  projectId: "hala-pwa",
  storageBucket: "hala-pwa.appspot.com",
  messagingSenderId: "123456789",
  appId: "1:123456789:web:abc123"
};
```

6. Open `js/firebase-config.js` and paste your values in

---

## STEP 6 — Create a Mapbox Account (Free)

1. Go to https://www.mapbox.com
2. Click **Sign up** (free — no credit card needed)
3. Verify your email
4. Go to your **Account page** → copy the **Default public token**
   (starts with `pk.eyJ1...`)
5. In `home.html` and `map.html`, find:
   ```javascript
   const MAPBOX_TOKEN = 'PASTE_YOUR_MAPBOX_TOKEN_HERE';
   ```
   And replace with your actual token.

---

## STEP 7 — Add Your Logo

1. Put your logo PNG file inside the `assets/` folder
2. Rename it to `logo.png` (or update the src in all HTML files to match)
3. Create two square versions for the app icon:
   - `assets/icons/icon-192.png` (192×192 px)
   - `assets/icons/icon-512.png` (512×512 px)
   - These can just be your logo on a dark green (#061506) background

---

## STEP 8 — Set Up Admin Access

1. Open `admin.html`
2. Find this line:
   ```javascript
   const ADMIN_EMAILS = ['admin@hala.sa', 'your@email.com'];
   ```
3. Replace with your actual email address(es)
4. Create that account through the normal signup flow first
5. Access admin panel at: `yoursite.com/admin.html`

---

## STEP 9 — Upload to Hostinger

1. Log in to Hostinger control panel
2. Go to **File Manager** → open your domain's `public_html` folder
3. Upload ALL files and folders maintaining the same structure
4. Make sure SSL is enabled (Hostinger → Security → SSL → Force HTTPS)
5. That's it — your PWA is live!

---

## STEP 10 — Test as a PWA

**On Android:**
- Open Chrome → go to your site URL
- Tap the 3-dot menu → "Add to Home Screen"
- It installs like an app

**On iPhone:**
- Open Safari → go to your site URL
- Tap the Share button → "Add to Home Screen"

---

## Adding Your First Locations (Admin Panel)

1. Go to `yoursite.com/admin.html`
2. Log in with your admin email
3. Click **Add New Location**
4. For Masjid Al-Nabawi: Lat `24.4672`, Lng `39.6112`
5. For Masjid Quba: Lat `24.4397`, Lng `39.6155`
6. Upload a photo, set the category, add a Hala offer description
7. Check "Featured" to show it in the home screen carousel
8. Click **Save Location** — appears on the map immediately!

---

## Troubleshooting

**Map not showing:** Check your Mapbox token is correct
**Firebase errors:** Make sure config keys are pasted exactly
**Auth not working:** Ensure Email/Password provider is enabled in Firebase
**Images not uploading:** Check Firebase Storage rules allow writes

---

Ready to go! The app is fully functional once you:
1. ✅ Paste Firebase config keys
2. ✅ Paste Mapbox token  
3. ✅ Add your logo.png
4. ✅ Upload to Hostinger
