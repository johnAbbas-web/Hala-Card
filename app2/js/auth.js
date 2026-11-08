// ============================================================
//  HALA PWA — Auth Helpers
//  Shared functions used across login, signup, and profile pages
// ============================================================

/**
 * Creates or updates a user profile document in Firestore.
 * Called after Google sign-in to ensure the user doc exists.
 */
async function ensureUserProfile(user) {
  const ref = db.collection('users').doc(user.uid);
  const snap = await ref.get();

  if (!snap.exists) {
    await ref.set({
      uid:            user.uid,
      name:           user.displayName || 'Hala Guest',
      email:          user.email || '',
      photoURL:       user.photoURL || '',
      favourites:     [],
      halaPassActive: true,
      createdAt:      firebase.firestore.FieldValue.serverTimestamp()
    });
  }
}

/**
 * Signs the current user out and redirects to welcome screen.
 */
async function signOutUser() {
  await auth.signOut();
  window.location.href = 'welcome.html';
}

/**
 * Guard — call at the top of any protected page.
 * Redirects to welcome if not authenticated.
 * Returns the user object if authenticated.
 */
function requireAuth(allowGuest = false) {
  return new Promise((resolve) => {
    auth.onAuthStateChanged(user => {
      if (!user) {
        window.location.href = 'welcome.html';
        return;
      }
      if (!allowGuest && user.isAnonymous) {
        window.location.href = 'welcome.html';
        return;
      }
      resolve(user);
    });
  });
}

/**
 * Toggle a location in the user's favourites array.
 */
async function toggleFavourite(userId, locationId) {
  const ref  = db.collection('users').doc(userId);
  const snap = await ref.get();
  const favs = snap.data().favourites || [];

  if (favs.includes(locationId)) {
    await ref.update({ favourites: firebase.firestore.FieldValue.arrayRemove(locationId) });
    return false; // removed
  } else {
    await ref.update({ favourites: firebase.firestore.FieldValue.arrayUnion(locationId) });
    return true;  // added
  }
}
