<?php
// ── Hala API — Bootstrap ────────────────────────────────────
// Shared by all API files. Sets up DB, CORS, and auth.
// ─────────────────────────────────────────────────────────────
setlocale(LC_ALL, 'C');


header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');  // Restrict to your domain in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// ── Database + admin credentials ──────────────────────────
// Kept in api/secrets.php, which is NOT committed to git. See secrets.sample.php.
require __DIR__ . '/secrets.php';

// ── Firebase project ID ───────────────────────────────────
// Must match the projectId in your firebase-config.js
define('FIREBASE_PROJECT_ID', 'hala-pwa');

// ── Upload directory (relative to document root) ──────────
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('UPLOAD_URL', 'https://pwa.halacard.co/uploads/');

// ── DB connection ─────────────────────────────────────────
function getDB() {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        respond(500, ['error' => 'Database connection failed']);
    }
    return $pdo;
}

// ── Response helper ───────────────────────────────────────
function respond($code, $data) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ── Verify Firebase ID Token ──────────────────────────────
// Fetches Google's public keys and validates the JWT.
function verifyFirebaseToken($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    $header  = json_decode(base64url_decode($parts[0]), true);
    $payload = json_decode(base64url_decode($parts[1]), true);

    // Basic claims check
    if ($payload['aud']  !== FIREBASE_PROJECT_ID)           return null;
    if ($payload['iss']  !== 'https://securetoken.google.com/' . FIREBASE_PROJECT_ID) return null;
    if ($payload['exp']  < time())                           return null;
    if (empty($payload['uid']) && empty($payload['sub']))   return null;

    // Verify signature against Google's public keys
    $kid = $header['kid'] ?? '';
    $keys = getGooglePublicKeys();
    if (!isset($keys[$kid])) return null;

    $pubKey = openssl_pkey_get_public($keys[$kid]);
    $data   = $parts[0] . '.' . $parts[1];
    $sig    = base64url_decode($parts[2]);
    $ok     = openssl_verify($data, $sig, $pubKey, OPENSSL_ALGO_SHA256);
    if ($ok !== 1) return null;

    return $payload;
}

function getGooglePublicKeys() {
    $cache_file = sys_get_temp_dir() . '/google_public_keys.json';
    // Cache for 1 hour
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 3600) {
        return json_decode(file_get_contents($cache_file), true);
    }
    $url  = 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com';
    $data = file_get_contents($url);
    if ($data) file_put_contents($cache_file, $data);
    return json_decode($data, true) ?: [];
}

function base64url_decode($str) {
    return base64_decode(strtr($str, '-_', '+/') . str_repeat('=', (4 - strlen($str) % 4) % 4));
}

// ── Require authenticated user ────────────────────────────
function requireUser() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        respond(401, ['error' => 'Unauthorised']);
    }
    $token   = substr($auth, 7);
    $payload = verifyFirebaseToken($token);
    if (!$payload) respond(401, ['error' => 'Invalid or expired token']);
    return $payload['uid'] ?? $payload['sub'];
}

// ── Admin auth (separate simple password) ────────────────
function requireAdmin() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    // Admin uses a different scheme: "Admin base64(user:pass)"
    if (!$auth || !str_starts_with($auth, 'Admin ')) {
        respond(401, ['error' => 'Admin auth required']);
    }
    $decoded = base64_decode(substr($auth, 6));
    [$user, $pass] = explode(':', $decoded, 2);
    // Set your admin credentials here
    if ($user !== ADMIN_USER || !hash_equals(ADMIN_PASS, (string) $pass)) {
        respond(403, ['error' => 'Forbidden']);
    }
}
