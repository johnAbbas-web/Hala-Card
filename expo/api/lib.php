<?php
/**
 * Sierah Expo 2026 — shared helpers (DB, security, email).
 */
declare(strict_types=1);

if (!is_file(__DIR__ . '/config.php')) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Server not configured (missing api/config.php).']);
    exit;
}

$GLOBALS['EXPO_CFG'] = require __DIR__ . '/config.php';
date_default_timezone_set('UTC');

function cfg(string $key, $default = null) {
    return $GLOBALS['EXPO_CFG'][$key] ?? $default;
}

function now(): string { return gmdate('Y-m-d H:i:s'); }

/* ───────────────────────── HTTP helpers ───────────────────────── */

function send_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header('Cache-Control: no-store');
}

function json_out(int $code, array $data): void {
    send_security_headers();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(int $code, string $message, array $extra = []): void {
    json_out($code, ['ok' => false, 'error' => $message] + $extra);
}

function require_method(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) fail(405, 'Method not allowed.');
}

/** Reject cross-site POSTs (basic CSRF defence for a cookie-less JSON API). */
function require_same_origin(): void {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin === '') return; // same-origin fetches from old browsers may omit it
    $host = parse_url($origin, PHP_URL_HOST);
    if (!$host || strcasecmp($host, $_SERVER['HTTP_HOST'] ?? '') !== 0) {
        $siteHost = parse_url((string) cfg('site_url'), PHP_URL_HOST);
        if (!$siteHost || strcasecmp($host, $siteHost) !== 0) fail(403, 'Forbidden.');
    }
}

function read_json(): array {
    $raw = file_get_contents('php://input', false, null, 0, 16384);
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

function h(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function client_ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }

/* ───────────────────────── Tokens ───────────────────────── */

function random_token(int $bytes = 24): string {
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

function token_hash(string $token): string { return hash('sha256', $token); }

function sign(string $purpose, string $value): string {
    return substr(hash_hmac('sha256', $purpose . ':' . $value, (string) cfg('app_secret')), 0, 32);
}

/** Unambiguous code, e.g. SX26-7KQ4MP */
function reservation_code(): string {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $out = '';
    for ($i = 0; $i < 6; $i++) $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return 'SX26-' . $out;
}

/* ───────────────────────── Validation ───────────────────────── */

function clean_text($value, int $max): string {
    $value = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', (string) $value) ?? '');
    $value = preg_replace('/\s+/u', ' ', $value) ?? '';
    return mb_substr($value, 0, $max);
}

/**
 * Canonical form of an email used to stop the same inbox claiming twice:
 * lower-case, strip "+tag", and ignore dots for Gmail.
 */
function email_key(string $email): string {
    $email = strtolower(trim($email));
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $local = explode('+', $local, 2)[0];
    if ($domain === 'googlemail.com') $domain = 'gmail.com';
    if ($domain === 'gmail.com') $local = str_replace('.', '', $local);
    return $local . '@' . $domain;
}

/* ───────────────────────── Database ───────────────────────── */

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    try {
        $pdo = new PDO((string) cfg('db_dsn'), (string) cfg('db_user', ''), (string) cfg('db_pass', ''), [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('[expo] DB connect failed: ' . $e->getMessage());
        fail(500, 'Service temporarily unavailable. Please try again shortly.');
    }
    if (is_sqlite()) {
        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA busy_timeout=5000');
    }
    migrate($pdo);
    return $pdo;
}

function is_sqlite(): bool { return str_starts_with((string) cfg('db_dsn'), 'sqlite:'); }

function migrate(PDO $pdo): void {
    $flag = __DIR__ . '/../data/.migrated-v1';
    if (is_file($flag)) return;
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if (is_sqlite()) {
        $sql = str_replace(
            ['INT AUTO_INCREMENT PRIMARY KEY', ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4', 'INSERT IGNORE'],
            ['INTEGER PRIMARY KEY AUTOINCREMENT', '', 'INSERT OR IGNORE'],
            $sql
        );
    }
    foreach (array_filter(array_map('trim', explode(';', preg_replace('/^--.*$/m', '', $sql)))) as $stmt) {
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            if (!str_starts_with($stmt, 'CREATE INDEX')) throw $e; // index already exists
        }
    }
    @file_put_contents($flag, now());
}

/** Atomically take one of the limited slots. Returns slot number or null when sold out. */
function claim_slot(PDO $pdo): ?int {
    $limit = (int) cfg('promo_limit', 500);
    $upd = $pdo->prepare('UPDATE expo_counter SET taken = taken + 1 WHERE id = 1 AND taken < ?');
    $upd->execute([$limit]);
    if ($upd->rowCount() !== 1) return null;
    return (int) $pdo->query('SELECT taken FROM expo_counter WHERE id = 1')->fetchColumn();
}

function slots_remaining(PDO $pdo): int {
    $taken = (int) $pdo->query('SELECT taken FROM expo_counter WHERE id = 1')->fetchColumn();
    return max(0, (int) cfg('promo_limit', 500) - $taken);
}

/** Begin a write transaction (IMMEDIATE on SQLite so the counter update is serialised). */
function tx_begin(PDO $pdo): void {
    if (is_sqlite()) $pdo->exec('BEGIN IMMEDIATE'); else $pdo->beginTransaction();
}
function tx_commit(PDO $pdo): void {
    if (is_sqlite()) $pdo->exec('COMMIT'); else $pdo->commit();
}
function tx_rollback(PDO $pdo): void {
    try { if (is_sqlite()) $pdo->exec('ROLLBACK'); elseif ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $e) {}
}

/* ───────────────────────── Rate limiting ───────────────────────── */

/**
 * Allow at most $max hits per $windowSeconds for $bucket. Expo visitors share
 * venue Wi-Fi, so per-IP limits are deliberately generous; per-email limits are strict.
 */
function rate_limit(string $bucket, int $max, int $windowSeconds): void {
    $pdo = db();
    $key = substr(hash('sha256', cfg('app_secret') . '|' . $bucket), 0, 40);
    $since = gmdate('Y-m-d H:i:s', time() - $windowSeconds);
    $q = $pdo->prepare('SELECT COUNT(*) FROM expo_rate WHERE bucket = ? AND created_at > ?');
    $q->execute([$key, $since]);
    if ((int) $q->fetchColumn() >= $max) {
        fail(429, 'Too many attempts. Please wait a few minutes and try again.');
    }
    $pdo->prepare('INSERT INTO expo_rate (bucket, created_at) VALUES (?, ?)')->execute([$key, now()]);
    if (random_int(1, 50) === 1) {
        $pdo->prepare('DELETE FROM expo_rate WHERE created_at < ?')->execute([gmdate('Y-m-d H:i:s', time() - 86400)]);
    }
}

/* ───────────────────────── Registrations ───────────────────────── */

function find_by_device(string $deviceToken): ?array {
    if ($deviceToken === '' || strlen($deviceToken) > 100) return null;
    $q = db()->prepare(
        'SELECT r.* FROM expo_devices d JOIN expo_registrations r ON r.id = d.registration_id
         WHERE d.token_hash = ? AND r.status <> \'deleted\''
    );
    $q->execute([token_hash($deviceToken)]);
    return $q->fetch() ?: null;
}

function new_device_token(int $registrationId): string {
    $token = random_token(24);
    db()->prepare('INSERT INTO expo_devices (registration_id, token_hash, created_at) VALUES (?, ?, ?)')
        ->execute([$registrationId, token_hash($token), now()]);
    return $token;
}

/** Issue a fresh one-time access/confirmation link token and store its hash. */
function new_link_token(int $registrationId): string {
    $token = random_token(24);
    $expires = gmdate('Y-m-d H:i:s', time() + 3600 * (int) cfg('verify_hours', 48));
    db()->prepare('UPDATE expo_registrations SET link_token_hash = ?, link_expires_at = ?, link_sent_at = ?, updated_at = ? WHERE id = ?')
        ->execute([token_hash($token), $expires, now(), now(), $registrationId]);
    return $token;
}

function public_pass(array $r): array {
    return [
        'firstName'      => $r['first_name'],
        'limit'          => (int) cfg('promo_limit', 500),
        'status'         => $r['status'],      // pending | reserved | waitlisted
        'code'           => $r['status'] === 'reserved' ? $r['reservation_code'] : null,
        'slot'           => $r['status'] === 'reserved' ? (int) $r['slot_number'] : null,
        'emailHint'      => mask_email($r['email']),
        'shareUrl'       => cfg('site_url') . '/api/share.php?s=' . rawurlencode($r['share_id']),
        'redeemed'       => !empty($r['redeemed_at']),
        'marketing'      => (bool) $r['marketing_consent'],
        'manageUrl'      => manage_url($r),
        'testMode'       => is_test_mail(),
    ];
}

function mask_email(string $email): string {
    [$l, $d] = array_pad(explode('@', $email, 2), 2, '');
    return mb_substr($l, 0, 1) . str_repeat('•', max(2, min(6, mb_strlen($l) - 1))) . '@' . $d;
}

function manage_url(array $r): string {
    return cfg('site_url') . '/api/manage.php?id=' . (int) $r['id'] . '&sig=' . sign('manage', (string) $r['id']);
}

/* ───────────────────────── Redemption ───────────────────────── */

/**
 * Check or redeem a reservation code. Redemption is atomic, so a code can only ever be used once.
 * Returns ['ok'=>bool, 'reason'=>string, 'registration'=>?array]
 */
function redeem_code(string $code, ?string $email, string $ref, bool $commit): array {
    $code = strtoupper(trim($code));
    $pdo = db();
    $q = $pdo->prepare('SELECT * FROM expo_registrations WHERE reservation_code = ?');
    $q->execute([$code]);
    $reg = $q->fetch();
    if (!$reg || $reg['status'] !== 'reserved') return ['ok' => false, 'reason' => 'not_found', 'registration' => null];
    if ($email !== null && $email !== '' && email_key($email) !== $reg['email_key']) {
        return ['ok' => false, 'reason' => 'email_mismatch', 'registration' => null];
    }
    if ($reg['redeemed_at']) return ['ok' => false, 'reason' => 'already_redeemed', 'registration' => $reg];
    if (!$commit) return ['ok' => true, 'reason' => 'valid', 'registration' => $reg];

    $u = $pdo->prepare('UPDATE expo_registrations SET redeemed_at = ?, redeemed_ref = ?, updated_at = ? WHERE id = ? AND redeemed_at IS NULL');
    $u->execute([now(), mb_substr($ref, 0, 100), now(), $reg['id']]);
    if ($u->rowCount() !== 1) return ['ok' => false, 'reason' => 'already_redeemed', 'registration' => $reg];
    $reg['redeemed_at'] = now();
    return ['ok' => true, 'reason' => 'redeemed', 'registration' => $reg];
}

/* ───────────────────────── Email ───────────────────────── */

/** True when emails are only written to data/mail.log (local testing). */
function is_test_mail(): bool { return cfg('mail_transport') === 'log'; }

/** In test mode, the last confirmation link generated in this request (shown on the page instead of emailed). */
function dev_link(): ?string { return is_test_mail() ? ($GLOBALS['EXPO_DEV_LINK'] ?? null) : null; }

function send_mail(string $to, string $subject, string $html, string $text, ?array $reg = null): bool {
    $fromName = (string) cfg('mail_from_name', 'Hala Card');
    $from = (string) cfg('mail_from');
    $boundary = 'b' . bin2hex(random_bytes(8));
    $headers = [
        'MIME-Version: 1.0',
        'From: ' . mb_encode_mimeheader($fromName, 'UTF-8') . " <{$from}>",
        'Reply-To: ' . cfg('mail_reply_to', $from),
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
    ];
    if ($reg) {
        $headers[] = 'List-Unsubscribe: <' . manage_url($reg) . '>';
    }
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($text))
          . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($html))
          . "--{$boundary}--";
    $encSubject = mb_encode_mimeheader($subject, 'UTF-8');

    if (cfg('mail_transport') === 'log') {
        $log = __DIR__ . '/../data/mail.log';
        file_put_contents($log, "==== " . now() . " To: {$to}\nSubject: {$subject}\n\n{$text}\n\n", FILE_APPEND | LOCK_EX);
        return true;
    }
    if (cfg('mail_transport') === 'smtp') {
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $full = array_merge($headers, [
            'To: <' . $to . '>',
            'Subject: ' . $encSubject,
            'Date: ' . date('r'),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $domain . '>',
        ]);
        try {
            smtp_send($from, $to, implode("\r\n", $full) . "\r\n\r\n" . $body);
            return true;
        } catch (Throwable $e) {
            error_log('[expo] SMTP failed for ' . mask_email($to) . ': ' . $e->getMessage());
            return false;
        }
    }
    $ok = @mail($to, $encSubject, $body, implode("\r\n", $headers), '-f' . $from);
    if (!$ok) error_log('[expo] mail() failed for ' . mask_email($to));
    return $ok;
}

/**
 * Minimal authenticated SMTP client (SSL on 465 or STARTTLS on 587).
 * Works with Hostinger (smtp.hostinger.com) and Gmail (smtp.gmail.com, app password).
 */
function smtp_send(string $from, string $to, string $message): void {
    $host   = (string) cfg('smtp_host');
    $port   = (int) cfg('smtp_port', 465);
    $secure = (string) cfg('smtp_secure', $port === 465 ? 'ssl' : 'tls');
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $host]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new RuntimeException("connect {$remote}: {$errstr} ({$errno})");
    stream_set_timeout($fp, 20);

    $read = function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $out;
    };
    $cmd = function (?string $line, array $expect) use ($fp, $read): string {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $resp = $read();
        if (!in_array((int) substr($resp, 0, 3), $expect, true)) {
            $shown = $line && str_starts_with($line, 'AUTH') ? 'AUTH' : (string) $line;
            throw new RuntimeException('SMTP ' . ($shown ?: 'greeting') . ' → ' . trim($resp));
        }
        return $resp;
    };

    try {
        $ehlo = 'EHLO ' . (parse_url((string) cfg('site_url'), PHP_URL_HOST) ?: 'localhost');
        $cmd(null, [220]);
        $cmd($ehlo, [250]);
        if ($secure === 'tls') {
            $cmd('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('STARTTLS failed');
            $cmd($ehlo, [250]);
        }
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode((string) cfg('smtp_user')), [334]);
        $cmd(base64_encode((string) cfg('smtp_pass')), [235]);
        $cmd('MAIL FROM:<' . $from . '>', [250]);
        $cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $cmd('DATA', [354]);
        $data = preg_replace('/^\./m', '..', str_replace(["\r\n", "\n"], ["\n", "\r\n"], $message));
        $cmd($data . "\r\n.", [250]);
        $cmd('QUIT', [221]);
    } finally {
        fclose($fp);
    }
}

/** Wrap content in the branded dark-green + gold email layout. */
function email_layout(string $title, string $bodyHtml, ?array $reg = null): string {
    $site = h((string) cfg('site_url'));
    $footer = $reg
        ? '<a href="' . h(manage_url($reg)) . '" style="color:#a8874a">Manage email preferences or delete my data</a> · '
        : '';
    return '<!doctype html><html><body style="margin:0;background:#06200f;font-family:Helvetica,Arial,sans-serif;color:#ECE6DA">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#06200f;padding:32px 12px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#0A2B1A;border:1px solid #3d5a40;border-radius:18px;overflow:hidden">'
        . '<tr><td style="padding:28px 28px 8px;text-align:center;letter-spacing:6px;font-size:13px;color:#DDC275;font-weight:bold">HALA CARD</td></tr>'
        . '<tr><td style="padding:4px 28px 0;text-align:center;font-size:11px;letter-spacing:3px;color:#a8874a">SIERAH EXPO 2026 · EXCLUSIVE EXPLORER EDITION</td></tr>'
        . '<tr><td style="padding:24px 28px 8px;text-align:center;font-family:Georgia,serif;font-size:26px;color:#F1DDA0">' . h($title) . '</td></tr>'
        . '<tr><td style="padding:8px 28px 28px;font-size:15px;line-height:1.6;color:#ECE6DA">' . $bodyHtml . '</td></tr>'
        . '</table>'
        . '<p style="max-width:520px;font-size:11px;line-height:1.6;color:#8a9a8c;margin:18px auto 0;text-align:center">'
        . $footer . '<a href="' . $site . '/privacy.html" style="color:#a8874a">Privacy</a> · <a href="' . $site . '/terms.html" style="color:#a8874a">Offer terms</a><br>'
        . 'Hala Card Company · Madinah, Kingdom of Saudi Arabia</p>'
        . '</td></tr></table></body></html>';
}

function email_button(string $url, string $label): string {
    return '<p style="text-align:center;margin:26px 0"><a href="' . h($url) . '" style="display:inline-block;background:#DDC275;color:#0A2B1A;'
        . 'text-decoration:none;font-weight:bold;letter-spacing:2px;font-size:13px;padding:15px 28px;border-radius:999px">' . h($label) . '</a></p>';
}

function send_confirm_email(array $reg, string $linkToken): bool {
    $url = cfg('site_url') . '/api/verify.php?t=' . rawurlencode($linkToken);
    if (is_test_mail()) $GLOBALS['EXPO_DEV_LINK'] = $url;
    $name = $reg['first_name'];
    $hours = (int) cfg('verify_hours', 48);
    $html = email_layout('Confirm your exclusive pass',
        '<p>Hi ' . h($name) . ',</p><p>Thank you for visiting Hala Card at Sierah Expo. Tap below to confirm your email and secure your '
        . '<strong style="color:#F1DDA0">exclusive Hala Card gift</strong>. Only the first ' . (int) cfg('promo_limit') . ' confirmed visitors receive it.</p>'
        . email_button($url, 'CONFIRM MY PASS')
        . '<p style="font-size:13px;color:#b9b3a6">This link works for ' . $hours . ' hours and can be used once. If you did not request this, you can ignore this email.</p>', $reg);
    $text = "Hi {$name},\n\nConfirm your email to reserve your exclusive Hala Card gift:\n{$url}\n\nThis link works for {$hours} hours.\nIf you did not request this, ignore this email.\n\nHala Card";
    return send_mail($reg['email'], 'Confirm your Hala Card Explorer pass', $html, $text, $reg);
}

function send_access_email(array $reg, string $linkToken): bool {
    $url = cfg('site_url') . '/api/verify.php?t=' . rawurlencode($linkToken);
    $html = email_layout('Your pass link',
        '<p>Hi ' . h($reg['first_name']) . ',</p><p>Someone (hopefully you) asked to open your Hala Card Explorer pass. Tap below to open it on this device.</p>'
        . email_button($url, 'OPEN MY PASS')
        . '<p style="font-size:13px;color:#b9b3a6">If this wasn\'t you, ignore this email — your reservation is safe.</p>', $reg);
    $text = "Hi {$reg['first_name']},\n\nOpen your Hala Card Explorer pass:\n{$url}\n\nIf this wasn't you, ignore this email.";
    return send_mail($reg['email'], 'Your Hala Card Explorer pass', $html, $text, $reg);
}

function send_reserved_email(array $reg): bool {
    $html = email_layout('Your reservation is confirmed',
        '<p>Hi ' . h($reg['first_name']) . ',</p>'
        . '<p>Your Hala Card Explorer Edition pass is reserved. When Hala Card launches in December, we\'ll email you an invitation to claim your <strong style="color:#F1DDA0">free gift</strong> and enjoy Hala Card benefits at up to 10 places across Madinah.</p>'
        . '<table role="presentation" width="100%" style="margin:22px 0;border:1px solid #DDC275;border-radius:14px"><tr><td style="padding:18px;text-align:center">'
        . '<div style="font-size:11px;letter-spacing:3px;color:#a8874a">RESERVATION CODE</div>'
        . '<div style="font-size:24px;letter-spacing:4px;color:#F1DDA0;font-weight:bold;margin-top:6px">' . h($reg['reservation_code']) . '</div>'
        . '<div style="font-size:12px;color:#b9b3a6;margin-top:6px">Explorer #' . (int) $reg['slot_number'] . ' of ' . (int) cfg('promo_limit') . '</div>'
        . '</td></tr></table>'
        . '<p style="font-size:13px;color:#b9b3a6">This is a reservation, not an activated pass. Your gift can be claimed after launch, subject to the offer terms.</p>'
        . email_button(cfg('site_url') . '/#discover', 'EXPLORE MADINAH'), $reg);
    $text = "Hi {$reg['first_name']},\n\nYour Hala Card Explorer Edition reservation is confirmed.\nReservation code: {$reg['reservation_code']} (Explorer #{$reg['slot_number']})\n\nWe'll email you when Hala Card launches in December with an invitation to claim your free gift, subject to the offer terms: " . cfg('site_url') . "/terms.html";
    return send_mail($reg['email'], 'Reserved: your Hala Card gift', $html, $text, $reg);
}

function send_waitlist_email(array $reg): bool {
    $html = email_layout('You\'re on the list',
        '<p>Hi ' . h($reg['first_name']) . ',</p><p>Thank you for confirming your email. All ' . (int) cfg('promo_limit')
        . ' Sierah Expo gifts have now been claimed, but you\'re on our launch list and we\'ll let you know when Hala Card goes live in December.</p>', $reg);
    $text = "Hi {$reg['first_name']},\n\nThanks for confirming. All Sierah Expo gifts have been claimed, but we'll tell you when Hala Card launches in December.";
    return send_mail($reg['email'], 'You\'re on the Hala Card launch list', $html, $text, $reg);
}

function send_launch_invite(array $reg): bool {
    $url = cfg('purchase_url') . (str_contains((string) cfg('purchase_url'), '?') ? '&' : '?') . 'code=' . rawurlencode($reg['reservation_code']);
    $html = email_layout('Hala Card is live',
        '<p>Hi ' . h($reg['first_name']) . ',</p><p>The wait is over. As one of the first ' . (int) cfg('promo_limit')
        . ' Sierah Expo explorers, you can now claim your reserved <strong style="color:#F1DDA0">free gift</strong> and start enjoying Hala Card benefits across Madinah.</p>'
        . '<p style="text-align:center;font-size:22px;letter-spacing:4px;color:#F1DDA0;font-weight:bold">' . h($reg['reservation_code']) . '</p>'
        . email_button($url, 'CLAIM MY GIFT')
        . '<p style="font-size:13px;color:#b9b3a6">One use per person. Subject to the offer terms.</p>', $reg);
    $text = "Hi {$reg['first_name']},\n\nHala Card is live. Use your reservation code {$reg['reservation_code']} to claim your gift:\n{$url}\n\nSubject to the offer terms: " . cfg('site_url') . "/terms.html";
    return send_mail($reg['email'], 'Your Hala Card gift is ready', $html, $text, $reg);
}
