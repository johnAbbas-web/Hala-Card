<?php
/**
 * Public share preview: /expo/api/share.php?s=SHARE_ID
 * Shows the visitor's first name on a pass preview with link-preview (Open Graph) tags.
 * Never shows the reservation code, email or country.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';

$s = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($_GET['s'] ?? ''));
$name = null;
if ($s !== '') {
    $q = db()->prepare('SELECT first_name, status FROM expo_registrations WHERE share_id = ?');
    $q->execute([$s]);
    $row = $q->fetch();
    if ($row && in_array($row['status'], ['reserved', 'pending'], true)) $name = $row['first_name'];
}

$site  = (string) cfg('site_url');
$remaining = slots_remaining(db());
$title = $name ? "{$name} reserved a Hala Card Explorer pass" : 'Hala Card · Sierah Expo 2026';
$desc  = 'An exclusive Sierah Expo invitation: the first ' . (int) cfg('promo_limit') . ' visitors receive a free Hala Card gift. Discover Madinah.';
$img   = (string) cfg('share_image', preg_replace('#/expo$#', '', $site) . '/assets1/pass-mockup.webp');

send_security_headers();
header('Content-Type: text/html; charset=utf-8');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($title) ?></title>
<meta name="description" content="<?= h($desc) ?>">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#0A2B1A">
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($title) ?>">
<meta property="og:description" content="<?= h($desc) ?>">
<meta property="og:image" content="<?= h($img) ?>">
<meta property="og:url" content="<?= h($site . '/api/share.php?s=' . $s) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="../../assets/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../css/expo.css?v=23">
</head>
<body class="share-page">
<div class="aurora" aria-hidden="true"><i class="a1"></i><i class="a2"></i><i class="a3"></i><i class="a4"></i></div>
<div class="app">
<main class="screen is-active share">
  <div class="hero share-hero">
    <img class="hero-img" src="../img/madinah-dusk.webp" alt="" fetchpriority="high">
    <div class="hero-top">
      <span class="logo logo-dark logo-sm logo-pill"><img src="../img/hala-mark.png" alt=""><span>Card</span></span><span class="sr-only">Hala Card</span>
      <span class="invite-chip"><span class="dot"></span>Sierah Expo 2026</span>
    </div>
  </div>

  <div class="share-body">
    <div class="invite-from">
      <span class="avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr($name ?? 'H', 0, 1))) ?></span>
      <p><b><?= h($name ?? 'A friend') ?></b> reserved an exclusive<br>Hala Card Explorer pass</p>
    </div>

    <div class="pass-stage">
      <div class="pass is-revealed" data-status="reserved">
        <div class="pass-face">
          <div class="pass-sheen"></div>
          <div class="pass-top"><span class="pass-brand">HALA CARD</span><span class="pass-event">SIERAH EXPO 2026</span></div>
          <div class="pass-edition">Exclusive Explorer Edition</div>
          <div class="pass-chip" aria-hidden="true"></div>
          <div class="pass-name"><?= h($name ?? 'Explorer') ?></div>
          <div class="pass-offer"><strong>FREE GIFT</strong><span>RESERVED</span></div>
          <div class="pass-skyline" data-skyline></div>
        </div>
      </div>
    </div>

    <h1 class="display sm">Your journey to <span class="hl">Madinah</span> starts here.</h1>
    <p class="lead">Visiting Sierah Expo? The first <?= (int) cfg('promo_limit') ?> visitors receive a <strong>free Hala Card gift</strong>. Discover cafés, heritage and hidden gems across Madinah.</p>

    <ul class="perks" aria-label="What the pass includes">
      <li class="perk p-gold"><span class="perk-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/></svg></span><span><b>Free gift</b><small>for the first <?= (int) cfg('promo_limit') ?></small></span></li>
      <li class="perk p-green"><span class="perk-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg></span><span><b>Up to 10 places</b><small>with Hala Card benefits</small></span></li>
      <li class="perk p-rose"><span class="perk-ic" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3 6.5 7 .8-5.2 4.8 1.4 7L12 17.6 5.8 21l1.4-7L2 9.3l7-.8z"/></svg></span><span><b><?= (int) $remaining ?> left</b><small>of <?= (int) cfg('promo_limit') ?> exclusive passes</small></span></li>
    </ul>
  </div>

  <div class="sticky-cta">
    <?php if ($remaining > 0): ?>
      <a class="btn btn-gold btn-xl" href="<?= h($site) ?>/">Reserve my free gift
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
    <?php else: ?>
      <a class="btn btn-gold btn-xl" href="<?= h($site) ?>/">Join the launch list</a>
    <?php endif; ?>
  </div>
</main>
</div>
<script src="../js/skyline.js?v=1"></script>
</body>
</html>
