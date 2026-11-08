<?php
require_once __DIR__ . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'];

// POST /api/users/register
if ($method === 'POST') {
    $uid  = requireUser();
    $body = json_decode(file_get_contents('php://input'), true);
    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO users (firebase_uid, name, email)
         VALUES (:uid, :name, :email)
         ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), updated_at=NOW()"
    );
    $stmt->execute([':uid' => $uid, ':name' => $body['name'] ?? '', ':email' => $body['email'] ?? '']);
    respond(200, ['success' => true]);
}

respond(405, ['error' => 'Method not allowed']);
