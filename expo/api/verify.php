<?php
/**
 * Email confirmation / magic access link.
 *
 * GET  ?t=TOKEN  → redirects to the app with the token in the URL fragment. Nothing is
 *                  consumed on GET, so email security scanners that pre-open links
 *                  can't burn the token.
 * POST {token}   → called by the app. Confirms the email (claiming one of the limited
 *                  slots if still pending) and returns a device token for this browser.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $t = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_GET['t'] ?? ''));
    send_security_headers();
    header('Location: ' . cfg('site_url') . '/#confirm=' . $t, true, 302);
    exit;
}

require_method('POST');
require_same_origin();
rate_limit('verify-ip:' . client_ip(), 60, 600);

$token = (string) (read_json()['token'] ?? '');
if ($token === '' || strlen($token) > 100) fail(400, 'This link is not valid.');

$pdo = db();
tx_begin($pdo);
try {
    $q = $pdo->prepare('SELECT * FROM expo_registrations WHERE link_token_hash = ?' . (is_sqlite() ? '' : ' FOR UPDATE'));
    $q->execute([token_hash($token)]);
    $reg = $q->fetch();

    if (!$reg || $reg['link_expires_at'] < now()) {
        tx_rollback($pdo);
        fail(410, 'This link has expired or was already used. Enter your email again and we\'ll send a fresh one.');
    }

    $newlyConfirmed = false;
    if ($reg['status'] === 'pending') {
        $slot = claim_slot($pdo);
        if ($slot !== null) {
            $code = null;
            for ($i = 0; $i < 8 && $code === null; $i++) {
                $try = reservation_code();
                $c = $pdo->prepare('SELECT 1 FROM expo_registrations WHERE reservation_code = ?');
                $c->execute([$try]);
                if (!$c->fetchColumn()) $code = $try;
            }
            $pdo->prepare('UPDATE expo_registrations SET status = \'reserved\', slot_number = ?, reservation_code = ?, verified_at = ?, updated_at = ? WHERE id = ?')
                ->execute([$slot, $code, now(), now(), $reg['id']]);
        } else {
            $pdo->prepare('UPDATE expo_registrations SET status = \'waitlisted\', verified_at = ?, updated_at = ? WHERE id = ?')
                ->execute([now(), now(), $reg['id']]);
        }
        $newlyConfirmed = true;
    }

    // One-time link: burn it.
    $pdo->prepare('UPDATE expo_registrations SET link_token_hash = NULL, link_expires_at = NULL WHERE id = ?')->execute([$reg['id']]);
    tx_commit($pdo);
} catch (Throwable $e) {
    tx_rollback($pdo);
    error_log('[expo] verify failed: ' . $e->getMessage());
    fail(500, 'Something went wrong. Please try the link again.');
}

$reg = $pdo->query('SELECT * FROM expo_registrations WHERE id = ' . (int) $reg['id'])->fetch();
if ($newlyConfirmed) {
    $reg['status'] === 'reserved' ? send_reserved_email($reg) : send_waitlist_email($reg);
}

json_out(200, [
    'ok'        => true,
    'device'    => new_device_token((int) $reg['id']),
    'confirmed' => $newlyConfirmed,
    'remaining' => slots_remaining($pdo),
    'pass'      => public_pass($reg),
]);
