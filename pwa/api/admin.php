<?php
require_once __DIR__ . '/bootstrap.php';
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// Login
if ($method === 'POST' && ($_GET['action'] ?? '') === 'login') {
    $body = json_decode(file_get_contents('php://input'), true);
    $u = $body['username'] ?? '';
    $p = $body['password'] ?? '';
    if ($u === ADMIN_USER && hash_equals(ADMIN_PASS, (string) $p)) {
        respond(200, ['token' => base64_encode($u . ':' . $p)]);
    }
    respond(401, ['error' => 'Invalid credentials']);
}

requireAdmin();

switch ($method) {

    // ── GET all places ────────────────────────────────────
    case 'GET':
        $stmt = $db->prepare("SELECT * FROM places ORDER BY updated_at DESC");
        $stmt->execute();
        respond(200, ['places' => $stmt->fetchAll()]);
        break;

    // ── POST create ───────────────────────────────────────
    case 'POST':
        $data = parseInput();
        $stmt = $db->prepare(
            "INSERT INTO places
               (name, description, category, address, lat, lng,
                directions_url, image_url, images, offer, offer_expiry,
                is_featured, is_active, created_at)
             VALUES
               (:name,:desc,:cat,:addr,:lat,:lng,
                :dir,:img,:imgs,:offer,:expiry,
                :feat,:active,NOW())"
        );
        $params = buildParams($data);
        $params[':img'] = $data['primary_image'] ?? null;
        $stmt->execute($params);
        respond(201, ['id' => $db->lastInsertId(), 'success' => true]);
        break;

    // ── PUT update ────────────────────────────────────────
    case 'PUT':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) respond(400, ['error' => 'id required']);

        $data = parseInput();

        // Fetch current row so we never blank existing values
        $cur = $db->prepare("SELECT * FROM places WHERE id = :id");
        $cur->execute([':id' => $id]);
        $current = $cur->fetch();
        if (!$current) respond(404, ['error' => 'Place not found']);

        // Merge: use submitted value if not empty, otherwise keep current DB value
        $name     = trim($data['name']        ?? '') ?: $current['name'];
        $desc     = $data['desc_raw']          ?? $current['description'];
        $cat      = trim($data['category']     ?? '') ?: $current['category'];
        $addr     = $data['addr_raw']          ?? $current['address'];
        $lat = $data['lat_raw'] !== '' ? (float)str_replace(',', '.', $data['lat_raw']) : $current['lat'];
        $lng = $data['lng_raw'] !== '' ? (float)str_replace(',', '.', $data['lng_raw']) : $current['lng'];
        $dir      = $data['dir_raw']           ?? $current['directions_url'];
        $offer    = $data['offer_raw']         ?? $current['offer'];
        $expiry   = !empty($data['expiry_raw']) ? $data['expiry_raw']      : $current['offer_expiry'];
        $featured = isset($data['is_featured']) ? (int)$data['is_featured'] : (int)$current['is_featured'];
        $active   = isset($data['is_active'])   ? (int)$data['is_active']   : (int)$current['is_active'];

        // Images: use merged result or keep current
        $primaryImg = $data['primary_image'] ?? $current['image_url'];
        $extraImgs  = $data['extra_imgs'] ?? null;
        if ($extraImgs === null) {
            // No image update — keep existing extra images
            $extraImgsJson = $current['images'];
        } else {
            $extraImgsJson = !empty($extraImgs) ? json_encode(array_values($extraImgs)) : null;
        }

        $stmt = $db->prepare(
            "UPDATE places SET
               name           = :name,
               description    = :desc,
               category       = :cat,
               address        = :addr,
               lat            = :lat,
               lng            = :lng,
               directions_url = :dir,
               image_url      = :img,
               images         = :imgs,
               offer          = :offer,
               offer_expiry   = :expiry,
               is_featured    = :feat,
               is_active      = :active,
               updated_at     = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            ':name'   => $name,
            ':desc'   => $desc,
            ':cat'    => $cat,
            ':addr'   => $addr,
            ':lat'    => $lat,
            ':lng'    => $lng,
            ':dir'    => $dir,
            ':img'    => $primaryImg,
            ':imgs'   => $extraImgsJson,
            ':offer'  => $offer,
            ':expiry' => $expiry ?: null,
            ':feat'   => $featured,
            ':active' => $active,
            ':id'     => $id,
        ]);
        respond(200, ['success' => true]);
        break;

    // ── DELETE ────────────────────────────────────────────
    case 'DELETE':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) respond(400, ['error' => 'id required']);
        $db->prepare("DELETE FROM places WHERE id = :id")->execute([':id' => $id]);
        respond(200, ['success' => true]);
        break;

    default:
        respond(405, ['error' => 'Method not allowed']);
}

// ── Parse multipart or JSON input ─────────────────────────
function parseInput() {
    $uploadedUrls = [];

    if (!empty($_FILES['images'])) {
        $data  = $_POST;
        $files = $_FILES['images'];

        if (!is_array($files['name'])) {
            $files = array_map(fn($v) => [$v], $files);
        }

        foreach ($files['name'] as $i => $name) {
            if (empty($name) || $files['error'][$i] !== UPLOAD_ERR_OK) continue;
            $url = handleUpload([
                'name'     => $name,
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ]);
            if ($url) $uploadedUrls[] = $url;
        }
    } else {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    }

    // Existing images the admin chose to keep
    $existing = [];
    if (!empty($data['existing_images'])) {
        $decoded = json_decode($data['existing_images'], true);
        if (is_array($decoded)) $existing = $decoded;
    }

    // All images = kept existing + new uploads
    $allImages = array_values(array_unique(array_merge($existing, $uploadedUrls)));

    // Store raw values so PUT handler can decide whether to use them
    $result = [
        'name'          => $data['name']            ?? '',
        'category'      => $data['category']        ?? '',
        // Raw values — empty string means "not provided", null means "provided as empty"
        'desc_raw'      => $data['description']     ?? null,
        'addr_raw'      => $data['address']         ?? null,
        'lat_raw'       => $data['lat']             ?? '',
        'lng_raw'       => $data['lng']             ?? '',
        'dir_raw'       => $data['directions_url']  ?? null,
        'offer_raw'     => $data['offer']           ?? null,
        'expiry_raw'    => $data['offer_expiry']    ?? '',
        'is_featured'   => $data['is_featured']     ?? null,
        'is_active'     => $data['is_active']       ?? null,
        'primary_image' => $allImages[0]            ?? null,
        'extra_imgs'    => !empty($allImages)       ? array_slice($allImages, 1) : null,
        'images_updated'=> !empty($allImages),
    ];

    // If no images at all were sent (no existing, no new), mark as not updated
    if (empty($data['existing_images']) && empty($uploadedUrls)) {
        $result['primary_image'] = null;
        $result['extra_imgs']    = null;
        $result['images_updated']= false;
    }

    return $result;
}

function handleUpload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $filename = uniqid('place_') . '.' . $ext;
    move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename);
    return UPLOAD_URL . $filename;
}

function buildParams($d) {
    return [
        ':name'   => trim($d['name']       ?? ''),
        ':desc'   => $d['desc_raw']        ?? '',
        ':cat'    => trim($d['category']   ?? ''),
        ':addr'   => $d['addr_raw']        ?? '',
        ':lat'    => !empty($d['lat_raw']) ? (float)str_replace(',', '.', $d['lat_raw']) : null,
        ':lng'    => !empty($d['lng_raw']) ? (float)str_replace(',', '.', $d['lng_raw']) : null,
        ':dir'    => $d['dir_raw']         ?? '',
        ':imgs'   => !empty($d['extra_imgs']) ? json_encode(array_values($d['extra_imgs'])) : null,
        ':offer'  => $d['offer_raw']       ?? '',
        ':expiry' => !empty($d['expiry_raw']) ? $d['expiry_raw'] : null,
        ':feat'   => (int)($d['is_featured'] ?? 0),
        ':active' => (int)($d['is_active']   ?? 1),
    ];
}
