// ============================================================
//  HALA PWA — Firebase Configuration
//  Replace the values below with your own Firebase project keys.
//  You get these from: Firebase Console → Project Settings → Your Apps
// ============================================================

const firebaseConfig = {
  apiKey:            "AIzaSyBSdrVVN7DWArKlAqKkVqN2FDlLtFINh4Q",
  authDomain:        "hala-pwa.firebaseapp.com",
  projectId:         "hala-pwa",
  storageBucket:     "hala-pwa.firebasestorage.app",
  messagingSenderId: "35452501000",
  appId:             "1:354525010004:web:f6dc3e0220ca81c78d75c1"
};


// Initialise Firebase
firebase.initializeApp(firebaseConfig);

// Export shortcuts used across the app
const auth = firebase.auth();
const db   = firebase.firestore();
const storage = firebase.storage();
