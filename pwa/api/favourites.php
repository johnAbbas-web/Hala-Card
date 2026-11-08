<?php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$uid    = requireUser();
$db     = getDB();

// GET — list user's favourites with place data
if ($method === 'GET') {
    $stmt = $db->prepare(
        "SELECT p.* FROM favourites f
         JOIN places p ON f.place_id = p.id
         WHERE f.firebase_uid = :uid AND p.is_active = 1
         ORDER BY f.created_at DESC"
    );
    $stmt->execute([':uid' => $uid]);
    respond(200, ['favourites' => $stmt->fetchAll()]);
}

// POST /api/favourites/toggle — add or remove
if ($method === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true);
    $place_id = (int)($body['place_id'] ?? 0);
    if (!$place_id) respond(400, ['error' => 'place_id required']);

    // Check if exists
    $chk = $db->prepare("SELECT id FROM favourites WHERE firebase_uid=:uid AND place_id=:pid");
    $chk->execute([':uid' => $uid, ':pid' => $place_id]);

    if ($chk->fetch()) {
        // Remove
        $del = $db->prepare("DELETE FROM favourites WHERE firebase_uid=:uid AND place_id=:pid");
        $del->execute([':uid' => $uid, ':pid' => $place_id]);
        respond(200, ['action' => 'removed', 'place_id' => $place_id]);
    } else {
        // Add — ensure user exists
        $upsert = $db->prepare("INSERT IGNORE INTO users (firebase_uid) VALUES (:uid)");
        $upsert->execute([':uid' => $uid]);
        $ins = $db->prepare("INSERT INTO favourites (firebase_uid, place_id) VALUES (:uid, :pid)");
        $ins->execute([':uid' => $uid, ':pid' => $place_id]);
        respond(200, ['action' => 'added', 'place_id' => $place_id]);
    }
}

respond(405, ['error' => 'Method not allowed']);
