<?php
/**
 * GET  pass.php                 (header X-Device: token) → this device's pass
 * POST pass.php {action:"resend"} (header X-Device)     → re-send the confirmation email
 * GET  pass.php?stats=1                                 → public counter for the landing page
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

$pdo = db();

if (isset($_GET['stats'])) {
    json_out(200, [
        'ok'        => true,
        'limit'     => (int) cfg('promo_limit'),
        'remaining' => slots_remaining($pdo),
        'open'      => (bool) cfg('registration_open', true),
    ]);
}

$reg = find_by_device((string) ($_SERVER['HTTP_X_DEVICE'] ?? ''));
if (!$reg) fail(401, 'Pass not found on this device.');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_same_origin();
    $action = (string) (read_json()['action'] ?? '');
    if ($action === 'resend') {
        if ($reg['status'] !== 'pending') json_out(200, ['ok' => true, 'pass' => public_pass($reg)]);
        rate_limit('resend:' . $reg['id'], 3, 900);
        if ($reg['link_sent_at'] && strtotime($reg['link_sent_at'] . ' UTC') > time() - 60) {
            fail(429, 'We just sent it — please allow a minute before asking again.');
        }
        $sent = send_confirm_email($reg, new_link_token((int) $reg['id']));
        json_out(200, ['ok' => true, 'mailSent' => $sent, 'devLink' => dev_link()]);
    }
    fail(400, 'Unknown action.');
}

json_out(200, ['ok' => true, 'remaining' => slots_remaining($pdo), 'pass' => public_pass($reg)]);
