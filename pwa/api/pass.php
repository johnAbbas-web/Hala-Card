<?php
require_once __DIR__ . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'];
$uid    = requireUser();
$db     = getDB();

// GET — check if pass is activated
if ($method === 'GET') {
    $stmt = $db->prepare("SELECT pass_activated FROM users WHERE firebase_uid = :uid");
    $stmt->execute([':uid' => $uid]);
    $row = $stmt->fetch();
    respond(200, ['activated' => $row ? (bool)$row['pass_activated'] : false]);
}

// POST — activate pass
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $code = strtoupper(trim($body['code'] ?? ''));

    $valid = ['FIRST1000'];
    if (!in_array($code, $valid)) {
        respond(400, ['error' => 'Invalid promo code']);
    }

    // Upsert user row and mark activated
    $stmt = $db->prepare(
        "INSERT INTO users (firebase_uid, pass_activated, pass_activated_at)
         VALUES (:uid, 1, NOW())
         ON DUPLICATE KEY UPDATE
           pass_activated = 1,
           pass_activated_at = NOW()"
    );
    $stmt->execute([':uid' => $uid]);
    respond(200, ['success' => true, 'activated' => true]);
}

respond(405, ['error' => 'Method not allowed']);