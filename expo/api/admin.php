<?php
/**
 * Staff dashboard: /expo/api/admin.php
 * Password is set as a hash in config.php (admin_password_hash).
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

session_name('hala_expo_admin');
session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']), 'samesite' => 'Strict', 'path' => dirname($_SERVER['SCRIPT_NAME'])]);
session_start();
send_security_headers();

$pdo = db();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf'];
$flash = null;
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost && !hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) { http_response_code(400); exit('Session expired — reload the page.'); }

/* ── Login / logout ── */
if ($isPost && ($_POST['action'] ?? '') === 'login') {
    rate_limit('admin-login:' . client_ip(), 8, 900);
    $hash = (string) cfg('admin_password_hash', '');
    if ($hash !== '' && password_verify((string) ($_POST['password'] ?? ''), $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php'); exit;
    }
    $flash = 'Incorrect password.';
}
if ($isPost && ($_POST['action'] ?? '') === 'logout') { $_SESSION = []; session_destroy(); header('Location: admin.php'); exit; }

$authed = !empty($_SESSION['admin']);

/* ── Authenticated actions ── */
if ($authed && ($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sierah-expo-registrations-' . gmdate('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    $cols = ['id','first_name','email','country','status','slot_number','reservation_code','verified_at','terms_version','terms_accepted_at','marketing_consent','marketing_consent_at','invited_at','redeemed_at','redeemed_ref','created_at'];
    fputcsv($out, $cols);
    foreach ($pdo->query('SELECT ' . implode(',', $cols) . ' FROM expo_registrations ORDER BY id') as $row) {
        // Neutralise spreadsheet formula injection.
        fputcsv($out, array_map(fn($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v, $row));
    }
    exit;
}

$redeemResult = null;
if ($authed && $isPost) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'check' || $action === 'redeem') {
        $redeemResult = redeem_code((string) $_POST['code'], null, 'admin:' . clean_text($_POST['ref'] ?? '', 80), $action === 'redeem');
    } elseif ($action === 'invite') {
        $rows = $pdo->query("SELECT * FROM expo_registrations WHERE status = 'reserved' AND invited_at IS NULL AND redeemed_at IS NULL ORDER BY slot_number LIMIT 50")->fetchAll();
        $sent = 0;
        foreach ($rows as $r) {
            if (send_launch_invite($r)) {
                $pdo->prepare('UPDATE expo_registrations SET invited_at = ?, updated_at = ? WHERE id = ?')->execute([now(), now(), $r['id']]);
                $sent++;
            }
        }
        $flash = "Sent {$sent} launch invitation(s)" . ($sent < count($rows) ? ' — some failed, check the mail log.' : '.');
    } elseif ($action === 'testmail') {
        $to = trim((string) ($_POST['to'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $flash = 'Enter a valid email address for the test.';
        } else {
            $ok = send_mail($to, 'Hala Card test email', email_layout('Test email', '<p>Email sending is working.</p>'), "Email sending is working.");
            $flash = $ok ? 'Test email sent to ' . $to . ' (transport: ' . cfg('mail_transport') . '). Check inbox and spam.'
                         : 'Sending failed. Check the SMTP settings in config.php and the PHP error log.';
        }
    } elseif ($action === 'purge') {
        $old = gmdate('Y-m-d H:i:s', time() - 30 * 86400);
        $ids = $pdo->prepare("SELECT id FROM expo_registrations WHERE status = 'pending' AND created_at < ?");
        $ids->execute([$old]);
        $n = 0;
        foreach ($ids->fetchAll(PDO::FETCH_COLUMN) as $pid) {
            $pdo->prepare('DELETE FROM expo_devices WHERE registration_id = ?')->execute([$pid]);
            $pdo->prepare('DELETE FROM expo_registrations WHERE id = ?')->execute([$pid]);
            $n++;
        }
        $flash = "Deleted {$n} unconfirmed registration(s) older than 30 days.";
    } elseif ($action === 'resend') {
        $q = $pdo->prepare("SELECT * FROM expo_registrations WHERE id = ? AND status = 'pending'");
        $q->execute([(int) $_POST['id']]);
        if ($r = $q->fetch()) { send_confirm_email($r, new_link_token((int) $r['id'])); $flash = 'Confirmation email re-sent to ' . mask_email($r['email']) . '.'; }
    }
}

/* ── Data for the dashboard ── */
$stats = [];
if ($authed) {
    foreach ($pdo->query('SELECT status, COUNT(*) n FROM expo_registrations GROUP BY status') as $r) $stats[$r['status']] = (int) $r['n'];
    $stats['redeemed'] = (int) $pdo->query('SELECT COUNT(*) FROM expo_registrations WHERE redeemed_at IS NOT NULL')->fetchColumn();
    $stats['invited']  = (int) $pdo->query('SELECT COUNT(*) FROM expo_registrations WHERE invited_at IS NOT NULL')->fetchColumn();
    $stats['marketing']= (int) $pdo->query('SELECT COUNT(*) FROM expo_registrations WHERE marketing_consent = 1')->fetchColumn();
    $stats['toInvite'] = (int) $pdo->query("SELECT COUNT(*) FROM expo_registrations WHERE status = 'reserved' AND invited_at IS NULL AND redeemed_at IS NULL")->fetchColumn();

    $search = clean_text($_GET['q'] ?? '', 100);
    $status = in_array($_GET['status'] ?? '', ['pending','reserved','waitlisted'], true) ? $_GET['status'] : '';
    $where = []; $args = [];
    if ($search !== '') { $where[] = '(first_name LIKE ? OR email LIKE ? OR reservation_code LIKE ?)'; $like = '%' . $search . '%'; array_push($args, $like, $like, $like); }
    if ($status !== '') { $where[] = 'status = ?'; $args[] = $status; }
    $q = $pdo->prepare('SELECT * FROM expo_registrations' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC LIMIT 300');
    $q->execute($args);
    $rows = $q->fetchAll();
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Sierah Expo · Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600..700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{--ink:#0B2E1E;--ink2:#2D4A3B;--muted:#55635A;--emerald:#0E7A4F;--gold:#E8B53C;--gold-hi:#F7D774;--sun:#FF7A45;--sun-ink:#B93D0B;--ivory:#FFF8EC;--line:#F0E3C8}
  *{box-sizing:border-box}
  body{margin:0;font:14px/1.5 'Space Grotesk',system-ui,-apple-system,Segoe UI,sans-serif;color:var(--ink);min-height:100vh;
    background:radial-gradient(60% 40% at 0% 0%,rgba(247,215,116,.45),transparent 70%),radial-gradient(50% 40% at 100% 10%,rgba(255,176,138,.4),transparent 70%),radial-gradient(60% 40% at 50% 110%,rgba(158,230,193,.45),transparent 70%),var(--ivory)}
  .wrap{max-width:1200px;margin:0 auto;padding:28px 18px 48px}
  h1{font:700 22px 'Fraunces',Georgia,serif;letter-spacing:0;color:var(--ink);margin:0 0 2px}
  h2{font:700 17px 'Fraunces',Georgia,serif;margin:0 0 12px;color:var(--ink)}
  .brand{font-size:11px;font-weight:700;letter-spacing:.3em;color:var(--sun-ink);text-transform:uppercase}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:20px 0}
  .stat{background:#fff;border-radius:18px;padding:16px;box-shadow:0 12px 26px -18px rgba(11,46,30,.5);position:relative;overflow:hidden}
  .stat::before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:linear-gradient(var(--gold-hi),var(--sun))}
  .stat:nth-child(2)::before{background:linear-gradient(#18A86B,var(--emerald))}
  .stat:nth-child(6)::before{background:linear-gradient(#FF8FA8,#D63A63)}
  .stat b{display:block;font:700 28px 'Fraunces',Georgia,serif;color:var(--ink)} .stat span{color:var(--muted);font-size:12.5px}
  .card{background:#fff;border-radius:20px;padding:18px;margin:14px 0;box-shadow:0 12px 26px -18px rgba(11,46,30,.5)}
  input,select,button{font:inherit;border-radius:12px;border:2px solid #EFE3CC;background:#FFFCF6;color:var(--ink);padding:9px 12px;min-height:44px}
  input:focus,select:focus{outline:none;border-color:var(--gold);box-shadow:0 0 0 4px rgba(232,181,60,.2)}
  button{background:linear-gradient(120deg,var(--gold-hi),var(--gold) 50%,var(--sun));color:#3a2400;border:0;font-weight:700;cursor:pointer;padding:9px 18px;border-radius:999px}
  button:disabled{opacity:.5;cursor:not-allowed}
  button.ghost{background:#fff;color:var(--emerald);border:2px solid rgba(14,122,79,.3)}
  table{width:100%;border-collapse:collapse;font-size:13px} th,td{text-align:left;padding:10px 8px;border-bottom:1px solid var(--line);white-space:nowrap}
  th{color:var(--muted);font-weight:600;font-size:11.5px;letter-spacing:.08em;text-transform:uppercase} tr:hover td{background:#FFFBF2}
  .tbl{overflow:auto} .pill{padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;background:#EEE6D6;color:var(--ink2)}
  .pill.reserved{background:#DDF3E6;color:#0A3D27} .pill.pending{background:#FFF0D6;color:#7A4A00} .pill.waitlisted{background:#E8E4F8;color:#4B2A9A}
  .flash{background:#E6F6EC;color:#0A3D27;padding:12px 16px;border-radius:14px;margin:12px 0;font-weight:500;border-left:5px solid var(--emerald)}
  .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .ok{color:#0E7A4F;font-weight:600} .bad{color:#C0262D;font-weight:600} a{color:var(--emerald);font-weight:600}
</style></head><body><div class="wrap">
<?php if (!$authed): ?>
  <form method="post" class="card" style="max-width:360px;margin:12vh auto">
    <p class="brand">Hala Card</p><h1>Sierah Expo admin</h1><p style="color:var(--muted)">Sign in to manage reservations</p>
    <?php if ($flash): ?><div class="flash" style="background:#FDE8EA;color:#8E1F27;border-left-color:#C0262D"><?= h($flash) ?></div><?php endif; ?>
    <?php if (!cfg('admin_password_hash')): ?><p class="bad">Set admin_password_hash in api/config.php first.</p><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="login">
    <p><input type="password" name="password" placeholder="Password" autocomplete="current-password" required style="width:100%"></p>
    <button style="width:100%">Sign in</button>
  </form>
<?php else: ?>
  <div class="row" style="justify-content:space-between">
    <div><p class="brand">Hala Card · Sierah Expo 2026</p><h1>Reservations dashboard</h1></div>
    <form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button class="ghost" name="action" value="logout">Sign out</button></form>
  </div>
  <?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>

  <div class="grid">
    <div class="stat"><b><?= slots_remaining($pdo) ?></b><span>of <?= (int) cfg('promo_limit') ?> discounts left</span></div>
    <div class="stat"><b><?= $stats['reserved'] ?? 0 ?></b><span>Reserved (confirmed)</span></div>
    <div class="stat"><b><?= $stats['pending'] ?? 0 ?></b><span>Awaiting email confirmation</span></div>
    <div class="stat"><b><?= $stats['waitlisted'] ?? 0 ?></b><span>Launch list (after cap)</span></div>
    <div class="stat"><b><?= $stats['invited'] ?></b><span>Launch invites sent</span></div>
    <div class="stat"><b><?= $stats['redeemed'] ?></b><span>Discounts redeemed</span></div>
    <div class="stat"><b><?= $stats['marketing'] ?></b><span>Marketing opt-ins</span></div>
  </div>

  <div class="card">
    <h2>Redeem a reservation code</h2>
    <form method="post" class="row">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input name="code" placeholder="SX26-XXXXXX" required style="text-transform:uppercase" value="<?= h($_POST['code'] ?? '') ?>">
      <input name="ref" placeholder="Order / note (optional)">
      <button class="ghost" name="action" value="check">Check</button>
      <button name="action" value="redeem" onclick="return confirm('Mark this code as used? This cannot be undone.')">Redeem</button>
    </form>
    <?php if ($redeemResult): $r = $redeemResult['registration']; ?>
      <p class="<?= $redeemResult['ok'] ? 'ok' : 'bad' ?>">
        <?= h(['valid' => '✓ Valid — 50% discount available', 'redeemed' => '✓ Redeemed successfully', 'already_redeemed' => '✕ Already redeemed', 'not_found' => '✕ Code not found or not reserved', 'email_mismatch' => '✕ Email does not match'][$redeemResult['reason']]) ?>
        <?php if ($r): ?> — <?= h($r['first_name']) ?> (<?= h(mask_email($r['email'])) ?>), Explorer #<?= (int) $r['slot_number'] ?><?= $r['redeemed_at'] ? ', used ' . h($r['redeemed_at']) . ' UTC' : '' ?><?php endif; ?>
      </p>
    <?php endif; ?>
  </div>

  <div class="card row" style="justify-content:space-between">
    <div><h2 style="margin:0">Launch invitations</h2><span style="color:var(--muted)"><?= $stats['toInvite'] ?> reserved visitor(s) not yet invited. Sends up to 50 per click. Set purchase_url in config first.</span></div>
    <form method="post" onsubmit="return confirm('Send launch invitation emails to the next 50 reserved visitors?')">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button name="action" value="invite" <?= $stats['toInvite'] ? '' : 'disabled' ?>>Send next batch</button>
    </form>
    <form method="post" onsubmit="return confirm('Permanently delete unconfirmed registrations older than 30 days? This cannot be undone.')">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button class="ghost" name="action" value="purge">Delete stale unconfirmed</button>
    </form>
  </div>

  <div class="card">
    <h2>Email check</h2>
    <form method="post" class="row">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input name="to" type="email" placeholder="you@example.com" required>
      <button class="ghost" name="action" value="testmail">Send test email</button>
      <span style="color:var(--muted)">Transport: <?= h((string) cfg('mail_transport')) ?></span>
    </form>
  </div>

  <div class="card">
    <form class="row" method="get" style="margin-bottom:12px">
      <input name="q" placeholder="Search name, email or code" value="<?= h($search) ?>">
      <select name="status"><option value="">All statuses</option><?php foreach (['reserved','pending','waitlisted'] as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?></select>
      <button class="ghost">Filter</button>
      <a href="?export=csv" style="margin-left:auto">Export all as CSV</a>
    </form>
    <div class="tbl"><table>
      <tr><th>#</th><th>Name</th><th>Email</th><th>Country</th><th>Status</th><th>Code</th><th>Marketing</th><th>Registered (UTC)</th><th>Invited</th><th>Redeemed</th><th></th></tr>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= $r['slot_number'] ? (int) $r['slot_number'] : '—' ?></td>
        <td><?= h($r['first_name']) ?></td><td><?= h($r['email']) ?></td><td><?= h($r['country'] ?? '') ?></td>
        <td><span class="pill <?= h($r['status']) ?>"><?= h($r['status']) ?></span></td>
        <td><?= h($r['reservation_code'] ?? '') ?></td><td><?= $r['marketing_consent'] ? 'Yes' : 'No' ?></td>
        <td><?= h($r['created_at']) ?></td><td><?= $r['invited_at'] ? '✓' : '' ?></td><td><?= $r['redeemed_at'] ? '✓' : '' ?></td>
        <td><?php if ($r['status'] === 'pending'): ?><form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="ghost" name="action" value="resend" style="padding:3px 8px;font-size:12px">Resend</button></form><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="11" style="color:var(--muted)">No registrations yet.</td></tr><?php endif; ?>
    </table></div>
  </div>
<?php endif; ?>
</div></body></html>
