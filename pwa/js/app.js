// ── Hala PWA v2 — App Helpers ─────────────────────────────

// Toast notification
function showToast(msg, isError = false) {
  let t = document.getElementById('toast');
  if (!t) { t = document.createElement('div'); t.id='toast'; t.className='toast'; document.body.appendChild(t); }
  t.textContent = msg;
  t.className = 'toast' + (isError ? ' error' : '');
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}

// Loading overlay
function setLoading(on) {
  const el = document.getElementById('loadingOverlay');
  if (el) el.classList.toggle('show', on);
}

// Get Firebase ID token then call PHP API
async function apiCall(endpoint, method = 'GET', body = null) {
  const user = auth.currentUser;
  if (!user) throw new Error('Not authenticated');
  const token = await user.getIdToken();

  const opts = {
    method,
    headers: { 'Authorization': 'Bearer ' + token }
  };

  if (body instanceof FormData) {
    opts.body = body;
  } else if (body) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  }

  const res = await fetch(API_BASE + endpoint, opts);
  if (!res.ok) {
    const err = await res.json().catch(() => ({ error: 'Server error' }));
    throw new Error(err.error || 'Request failed');
  }
  return res.json();
}

// Redirect to login if not authenticated
function requireAuth() {
  return new Promise(resolve => {
    auth.onAuthStateChanged(user => {
      if (!user) { window.location.href = 'login.html'; return; }
      resolve(user);
    });
  });
}

// Haversine distance in km
function distKm(lat1, lng1, lat2, lng2) {
  const R = 6371, dLat = (lat2-lat1)*Math.PI/180, dLng = (lng2-lng1)*Math.PI/180;
  const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function formatDist(km) {
  return km < 1 ? Math.round(km*1000)+'m away' : km.toFixed(1)+'km away';
}

// Register service worker
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js').catch(() => {});
  });
}

// ── Language toggle ───────────────────────────────────────
function getLang() { return localStorage.getItem('hala_lang') || 'en'; }
function toggleLang() {
  const next = getLang() === 'en' ? 'ar' : 'en';
  localStorage.setItem('hala_lang', next);
  location.reload();
}