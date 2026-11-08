// ── Hala PWA v2 — Firebase Config ──────────────────────────
// Replace every "PASTE_..." value with your Firebase project keys.
// Firebase Console → Project Settings → Your Apps → Web app config
// ─────────────────────────────────────────────────────────────

const firebaseConfig = {
  apiKey:            "AIzaSyBSdrVVN7DWArKlAqKkVqN2FDlLtFINh4Q",
  authDomain:        "hala-pwa.firebaseapp.com",
  projectId:         "hala-pwa",
  storageBucket:     "hala-pwa.firebasestorage.app",
  messagingSenderId: "35452501000",
  appId:             "1:354525010004:web:f6dc3e0220ca81c78d75c1"
};

firebase.initializeApp(firebaseConfig);
const auth = firebase.auth();

// ── API base URL ───────────────────────────────────────────
// Set this to your Hostinger domain where the PHP API lives.
// Example: "https://yourdomain.com/api"
const API_BASE = "https://pwa.halacard.co/api";
