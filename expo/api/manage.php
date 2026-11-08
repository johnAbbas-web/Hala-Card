<?php
/**
 * Linked from every email: /expo/api/manage.php?id=ID&sig=HMAC
 * Lets the visitor stop marketing emails or cancel the reservation and delete their data.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

$id  = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$sig = (string) ($_GET['sig'] ?? $_POST['sig'] ?? '');
$valid = $id > 0 && hash_equals(sign('manage', (string) $id), $sig);

$pdo = db();
$reg = null;
if ($valid) {
    $q = $pdo->prepare('SELECT * FROM expo_registrations WHERE id = ?');
    $q->execute([$id]);
    $reg = $q->fetch() ?: null;
}

$message = null;
if ($reg && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    // RFC 8058 one-click unsubscribe posts "List-Unsubscribe=One-Click".
    if ($action === 'unsubscribe' || isset($_POST['List-Unsubscribe'])) {
        $pdo->prepare('UPDATE expo_registrations SET marketing_consent = 0, marketing_consent_at = NULL, updated_at = ? WHERE id = ?')->execute([now(), $id]);
        $message = 'You won\'t receive marketing emails from us. We\'ll still email you about your reserved gift when Hala Card launches.';
        $reg['marketing_consent'] = 0;
    } elseif ($action === 'subscribe') {
        $pdo->prepare('UPDATE expo_registrations SET marketing_consent = 1, marketing_consent_at = ?, updated_at = ? WHERE id = ?')->execute([now(), now(), $id]);
        $message = 'Thank you — you\'ll receive Hala Card news and offers.';
        $reg['marketing_consent'] = 1;
    } elseif ($action === 'delete' && ($_POST['confirm'] ?? '') === 'yes') {
        // Slots are not returned to the pool, so the "first 500" order stays fair and auditable.
        $pdo->prepare('DELETE FROM expo_devices WHERE registration_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM expo_registrations WHERE id = ?')->execute([$id]);
        $reg = null;
        $message = 'Your reservation has been cancelled and your details deleted.';
    }
}

send_security_headers();
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<title>Manage my Hala Card reservation</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/expo.css?v=23">
</head>
<body>
<div class="aurora" aria-hidden="true"><i class="a1"></i><i class="a2"></i><i class="a3"></i></div>
<header class="lg-hero">
  <img src="../../assets1/discover-quba.webp" alt="" class="lg-hero-img">
  <div class="lg-hero-top">
    <a class="icon-btn" href="../" aria-label="Back to Hala Card"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg></a>
    <span class="logo logo-dark logo-sm logo-pill"><img src="../img/hala-mark.png" alt=""><span>Card</span></span><span class="sr-only">Hala Card</span>
  </div>
</header>
<main class="legal manage">
  <div class="lg-head">
    <p class="eyebrow">Sierah Expo 2026 · Your details</p>
    <?php if ($valid && $reg): ?>
      <div class="mg-hello">
        <span class="avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($reg['first_name'], 0, 1))) ?></span>
        <div><h1 class="display sm">Hi, <span class="hl"><?= h($reg['first_name']) ?></span></h1><p class="muted"><?= h(mask_email($reg['email'])) ?></p></div>
      </div>
    <?php else: ?>
      <h1 class="display sm">Manage my <span class="hl">reservation</span></h1>
    <?php endif; ?>
  </div>

  <?php if ($message): ?>
    <div class="mg-toast" role="status">
      <span class="perk-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12l5 5L20 7"/></svg></span>
      <p><?= h($message) ?></p>
    </div>
  <?php endif; ?>

  <?php if (!$valid): ?>
    <div class="lg-card"><div class="lg-body"><h2>This link isn't valid</h2><p>Please use the link from your most recent Hala Card email, or write to <a href="mailto:info@halacard.co">info@halacard.co</a> and we'll help.</p></div></div>
  <?php elseif ($reg): ?>
    <?php $st = $reg['status']; ?>
    <section class="mg-status st-<?= h($st) ?>" aria-label="Reservation status">
      <div class="mg-status-top"><span class="pass-brand">HALA CARD</span><span class="pass-event">EXPLORER</span></div>
      <p class="mg-status-label"><?= h(['pending' => 'Awaiting email confirmation', 'reserved' => 'Gift reserved', 'waitlisted' => 'On the launch list'][$st] ?? $st) ?></p>
      <?php if ($st === 'reserved'): ?>
        <p class="mg-code"><span>Code</span> <?= h($reg['reservation_code']) ?> <span>· Explorer #<?= (int) $reg['slot_number'] ?></span></p>
      <?php elseif ($st === 'pending'): ?>
        <p class="mg-code"><span>Check your inbox for the confirmation link</span></p>
      <?php endif; ?>
    </section>

    <form method="post" class="lg-card mg-row">
      <input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="sig" value="<?= h($sig) ?>">
      <span class="perk-ic mg-ic-gold" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg></span>
      <div class="lg-body">
        <h2>News &amp; offers</h2>
        <p><?= $reg['marketing_consent'] ? 'You receive Hala Card news, partner offers and Madinah inspiration.' : 'You only receive emails about your reservation.' ?></p>
      </div>
      <button class="switch <?= $reg['marketing_consent'] ? 'on' : '' ?>" name="action" value="<?= $reg['marketing_consent'] ? 'unsubscribe' : 'subscribe' ?>"
        role="switch" aria-checked="<?= $reg['marketing_consent'] ? 'true' : 'false' ?>" aria-label="Hala Card news and offers emails"><i></i></button>
    </form>

    <form method="post" class="lg-card mg-danger" onsubmit="return confirm('Cancel your reservation and delete your details? This cannot be undone, and your gift will be lost.')">
      <input type="hidden" name="id" value="<?= (int) $id ?>"><input type="hidden" name="sig" value="<?= h($sig) ?>">
      <input type="hidden" name="confirm" value="yes">
      <div class="lg-body">
        <h2>Cancel &amp; delete my data</h2>
        <p>Removes your reservation and every detail we hold for this offer. Your reserved gift can't be restored afterwards.</p>
        <button class="btn btn-danger" name="action" value="delete">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14"/></svg>Cancel reservation &amp; delete</button>
      </div>
    </form>
  <?php else: ?>
    <div class="lg-card"><div class="lg-body"><h2>All done</h2><p>We've removed your details. If you change your mind, you're always welcome back — subject to availability.</p>
      <a class="btn btn-gold" href="../">Back to Hala Card</a></div></div>
  <?php endif; ?>

  <p class="fineprint"><a href="../privacy.html">Privacy notice</a> · <a href="../terms.html">Offer terms</a> · <a href="mailto:info@halacard.co">info@halacard.co</a></p>
</main>
</body>
</html>
