<?php
require_once __DIR__ . '/bootstrap.php';

$method = $_SERVER['REQUEST_METHOD'];
$uid    = requireUser();  // All place endpoints need auth
$db     = getDB();

// GET /api/places  — list places
if ($method === 'GET') {
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($_GET['category'])) {
        $where[]  = 'p.category = :cat';
        $params[':cat'] = $_GET['category'];
    }

    if (!empty($_GET['has_offer'])) {
        $where[] = "p.offer IS NOT NULL AND p.offer != ''";
    }

    if (!empty($_GET['featured'])) {
        $where[] = 'p.is_featured = 1';
    }

    if (!empty($_GET['q'])) {
        $where[]  = '(p.name LIKE :q OR p.category LIKE :q OR p.description LIKE :q)';
        $params[':q'] = '%' . $_GET['q'] . '%';
    }

    $whereStr = 'WHERE ' . implode(' AND ', $where);
    $limit    = min((int)($_GET['limit'] ?? 100), 200);

    $sql  = "SELECT p.*, 
               (SELECT COUNT(*) FROM places WHERE is_active=1) AS total,
               (SELECT COUNT(*) FROM places WHERE is_active=1 AND offer IS NOT NULL AND offer != '') AS total_offers,
               (SELECT id FROM places WHERE is_active=1 AND is_featured=1 ORDER BY updated_at DESC LIMIT 1) AS featured_id
             FROM places p
             $whereStr
             ORDER BY p.is_featured DESC, p.updated_at DESC
             LIMIT :lim";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $places = $stmt->fetchAll();

    // Get featured place separately
    $featured = null;
    if (!empty($places)) {
        $featStmt = $db->query("SELECT * FROM places WHERE is_featured=1 AND is_active=1 ORDER BY updated_at DESC LIMIT 1");
        $featured = $featStmt->fetch() ?: null;
    }

    $total       = $places[0]['total']        ?? 0;
    $totalOffers = $places[0]['total_offers'] ?? 0;
    // Remove meta columns from each row
    $places = array_map(function($p) {
        unset($p['total'], $p['total_offers'], $p['featured_id']);
        return $p;
    }, $places);

    respond(200, [
        'places'       => $places,
        'total'        => (int)$total,
        'total_offers' => (int)$totalOffers,
        'featured'     => $featured
    ]);
}

respond(405, ['error' => 'Method not allowed']);
