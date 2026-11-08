<?php
/**
 * POST /expo/api/register.php
 * Body (JSON): firstName, email, country?, acceptTerms (true), marketing (bool), website (honeypot, must be empty)
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

require_method('POST');
require_same_origin();

$in = read_json();

// Bots fill every field; humans never see this one.
if (!empty($in['website'])) json_out(200, ['ok' => true, 'state' => 'check_email']);

if (!cfg('registration_open', true)) fail(403, 'Registrations for this offer are now closed.');

rate_limit('reg-ip:' . client_ip(), 40, 600);

$firstName = clean_text($in['firstName'] ?? '', 60);
$email     = strtolower(clean_text($in['email'] ?? '', 254));
$country   = clean_text($in['country'] ?? '', 80);
$terms     = ($in['acceptTerms'] ?? false) === true;
// "abbas" → "Abbas" (leave deliberate capitalisation such as "McKenzie" alone)
if ($firstName !== '' && mb_strtolower($firstName) === $firstName) $firstName = mb_convert_case($firstName, MB_CASE_TITLE, 'UTF-8');
$marketing = ($in['marketing'] ?? false) === true;

$errors = [];
if (mb_strlen($firstName) < 1)                      $errors['firstName'] = 'Please enter your first name.';
elseif (!preg_match('/^[\p{L}\p{M}\' .-]+$/u', $firstName)) $errors['firstName'] = 'Please use letters only.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors['email'] = 'Please enter a valid email address.';
if (!$terms)                                         $errors['acceptTerms'] = 'Please accept the offer terms and privacy notice to continue.';
if ($errors) fail(422, 'Please check the highlighted fields.', ['fields' => $errors]);

$key = email_key($email);
rate_limit('reg-email:' . $key, 6, 3600);

$pdo = db();
$q = $pdo->prepare('SELECT * FROM expo_registrations WHERE email_key = ?');
$q->execute([$key]);
$existing = $q->fetch();

if ($existing) {
    // Never reveal or hand over an existing pass to whoever typed the email —
    // send a link to the inbox owner instead.
    $token = new_link_token((int) $existing['id']);
    if ($existing['status'] === 'pending') {
        send_confirm_email($existing, $token);
        json_out(200, ['ok' => true, 'state' => 'exists_pending', 'emailHint' => mask_email($existing['email']), 'devLink' => dev_link()]);
    }
    send_access_email($existing, $token);
    json_out(200, ['ok' => true, 'state' => 'exists', 'emailHint' => mask_email($existing['email'])]);
}

$ts = now();
try {
    $ins = $pdo->prepare(
        'INSERT INTO expo_registrations
         (first_name, email, email_key, country, status, share_id, terms_version, terms_accepted_at,
          marketing_consent, marketing_consent_at, created_at, updated_at)
         VALUES (?, ?, ?, ?, \'pending\', ?, ?, ?, ?, ?, ?, ?)'
    );
    $ins->execute([
        $firstName, $email, $key, $country !== '' ? $country : null, random_token(9),
        (string) cfg('terms_version'), $ts, $marketing ? 1 : 0, $marketing ? $ts : null, $ts, $ts,
    ]);
} catch (PDOException $e) {
    // Lost a race with an identical submission.
    fail(409, 'This email has already been registered. Check your inbox for your pass link.');
}

$id = (int) $pdo->lastInsertId();
$reg = $pdo->query('SELECT * FROM expo_registrations WHERE id = ' . $id)->fetch();

$device = new_device_token($id);
$link   = new_link_token($id);
$sent   = send_confirm_email($reg, $link);

json_out(201, [
    'ok'        => true,
    'state'     => 'created',
    'device'    => $device,
    'mailSent'  => $sent,
    'devLink'   => dev_link(),
    'remaining' => slots_remaining($pdo),
    'pass'      => public_pass($reg),
]);
