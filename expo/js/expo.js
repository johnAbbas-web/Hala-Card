/* Hala Card · Sierah Expo 2026 — mobile landing app */
(function () {
  'use strict';

  var API = 'api/';
  var KEY_DEVICE = 'hala_expo_device';
  var KEY_PASS = 'hala_expo_pass';
  var KEY_FAVS = 'hala_expo_favs';
  var LIMIT = 500;
  var XP = window.HALA_EXPERIENCES || [];
  var REDUCED = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ───────── utils ───────── */
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { v == null ? localStorage.removeItem(k) : localStorage.setItem(k, v); } catch (e) {} }
  };
  var state = { pass: null, device: store.get(KEY_DEVICE), current: null, prev: null, devLink: null };

  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
  function firstWord(n) { return String(n || '').trim().split(/\s+/)[0] || 'Explorer'; }

  var ICONS = {
    arrow: '<path d="M5 12h14M13 6l6 6-6 6"/>',
    back: '<path d="M15 18l-6-6 6-6"/>',
    heart: '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21l7.8-7.5 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
    pin: '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
    gift: '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v9H5v-9M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
    star: '<path d="M12 2l3 6.5 7 .8-5.2 4.8 1.4 7L12 17.6 5.8 21l1.4-7L2 9.3l7-.8z"/>',
    'Food & Drink': '<path d="M7 2v9a2 2 0 0 0 2 2v9M11 2v6a2 2 0 0 1-2 2M5 2v6a2 2 0 0 0 2 2M17 22V2c-2 1-3 4-3 8h3"/>',
    'Culture & History': '<path d="M3 21h18M5 21V10M9 21V10M15 21V10M19 21V10M2 10h20L12 3z"/>',
    'Nature & Outdoors': '<path d="M3 20l6-10 4 6 3-4 5 8z"/><circle cx="17" cy="6" r="2"/>',
    'Family Fun': '<circle cx="8" cy="6" r="2.5"/><circle cx="16" cy="6" r="2.5"/><path d="M3 20c0-4 2.2-7 5-7s5 3 5 7M11 20c0-4 2.2-7 5-7s5 3 5 7"/>',
    All: '<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>'
  };
  function icon(name, cls) {
    return '<svg class="' + (cls || '') + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || '') + '</svg>';
  }

  function api(path, opts) {
    opts = opts || {};
    var headers = { 'Accept': 'application/json' };
    if (opts.body) headers['Content-Type'] = 'application/json';
    if (state.device) headers['X-Device'] = state.device;
    return fetch(API + path, {
      method: opts.method || (opts.body ? 'POST' : 'GET'),
      headers: headers,
      body: opts.body ? JSON.stringify(opts.body) : undefined,
      credentials: 'same-origin'
    }).then(function (res) {
      return res.json().catch(function () {
        return { ok: false, error: 'Our server is not responding properly right now. Please try again in a moment.' };
      }).then(function (data) { data.httpStatus = res.status; return data; });
    }, function () {
      return { ok: false, httpStatus: 0, error: navigator.onLine === false
        ? 'You seem to be offline. Check your connection and try again.'
        : 'We could not reach the Hala Card server. Please try again in a moment.' };
    });
  }

  function toast(msg) {
    var t = document.createElement('div');
    t.className = 'toast'; t.textContent = msg; t.setAttribute('role', 'status');
    document.body.appendChild(t);
    setTimeout(function () { t.classList.add('out'); }, 2400);
    setTimeout(function () { t.remove(); }, 2900);
  }

  function celebrate() {
    if (REDUCED) return;
    var box = $('#sparkles'), colors = ['#F3D27A', '#D9A93F', '#B8862B', '#FFF3CF', '#1C6B47'];
    for (var i = 0; i < 70; i++) {
      var p = document.createElement('i');
      p.style.left = Math.random() * 100 + 'vw';
      p.style.background = colors[i % colors.length];
      p.style.setProperty('--dx', (Math.random() * 120 - 60) + 'px');
      p.style.setProperty('--r', (Math.random() * 720 - 360) + 'deg');
      p.style.animationDuration = (2.2 + Math.random() * 2) + 's';
      p.style.animationDelay = (Math.random() * .6) + 's';
      box.appendChild(p);
    }
    setTimeout(function () { box.innerHTML = ''; }, 5200);
  }

  /* ───────── routing ───────── */
  var SCREENS = ['welcome', 'register', 'check', 'pass', 'discover'];

  function show(name, opts) {
    opts = opts || {};
    if (name === 'pass' && !state.pass) name = 'welcome';
    if (SCREENS.indexOf(name) < 0) name = 'welcome';
    if (state.current !== name) state.prev = state.current;
    state.current = name;
    $$('.screen').forEach(function (s) { s.classList.toggle('is-active', s.id === 's-' + name); });
    document.documentElement.classList.toggle('on-dark', name === 'welcome');
    if (!opts.keepScroll) window.scrollTo(0, 0);
    if (!opts.noHash && location.hash !== '#' + name) history.pushState(null, '', '#' + name);
    if (name === 'pass') renderPass(opts.celebrate);
    if (name === 'discover') renderDiscover();
    if (name === 'register') setTimeout(function () { var f = $('#firstName'); if (f && !f.value && window.innerWidth > 700) f.focus(); }, 300);
  }

  function route() {
    var h = location.hash.replace(/^#/, '');
    if (h.indexOf('confirm=') === 0) return confirmLink(h.slice(8));
    if (h.indexOf('xp/') === 0) {
      if (state.current !== 'discover') show('discover', { noHash: true });
      return openSheet(h.slice(3), true);
    }
    closeSheet(true);
    if (!h) return show(state.pass ? 'pass' : 'welcome', { noHash: true });
    show(h, { noHash: true });
  }

  document.addEventListener('click', function (e) {
    var el = e.target.closest('[data-go]');
    if (!el) return;
    e.preventDefault();
    if (el.hasAttribute('data-close')) closeSheet();
    var to = el.getAttribute('data-go');
    if (to === 'back') {
      if (state.prev && state.prev !== 'discover') return show(state.prev);
      return show(state.pass ? 'pass' : 'welcome');
    }
    show(to);
  });
  window.addEventListener('popstate', route);

  /* ───────── live counter ───────── */
  function loadStats() {
    api('pass.php?stats=1').then(function (d) {
      if (!d.ok) return;
      LIMIT = d.limit;
      $$('[data-limit]').forEach(function (el) { el.textContent = d.limit; });
      var label = $('#claim-label');
      if (!d.open) label.innerHTML = 'Registrations for this offer are now closed';
      else if (d.remaining <= 0) label.innerHTML = 'All ' + d.limit + ' gifts have been claimed. Join the launch list.';
      else label.innerHTML = '<i></i><b>' + d.remaining + '</b> of ' + d.limit + ' gifts still available';
      label.hidden = false;
    });
  }

  /* ───────── registration ───────── */
  function setFieldError(name, msg) {
    var f = $('[data-field="' + name + '"]');
    var e = $('#err-' + name); if (e) e.textContent = msg || '';
    if (!f) return;
    f.classList.toggle('has-error', !!msg);
    var input = $('input', f); if (input) input.setAttribute('aria-invalid', msg ? 'true' : 'false');
  }

  function validate(data) {
    var errs = {};
    if (!data.firstName) errs.firstName = 'Please enter your name.';
    else if (!/^[\p{L}\p{M}' .-]+$/u.test(data.firstName)) errs.firstName = 'Please use letters only.';
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(data.email)) errs.email = 'Please enter a valid email address.';
    if (!data.acceptTerms) errs.acceptTerms = 'Please accept the Terms & Conditions and Privacy Policy to continue.';
    return errs;
  }

  var form = $('#reg-form');
  form.addEventListener('focusout', function (e) {
    var name = e.target.name;
    if (name !== 'firstName' && name !== 'email') return;
    var v = e.target.value.trim(); if (!v) return;
    var errs = validate({ firstName: name === 'firstName' ? v : 'x', email: name === 'email' ? v : 'a@b.co', acceptTerms: true });
    setFieldError(name, errs[name] || '');
  });
  form.addEventListener('input', function (e) { if (e.target.name) setFieldError(e.target.name, ''); $('#form-alert').hidden = true; });
  form.addEventListener('change', function (e) { if (e.target.name === 'acceptTerms') setFieldError('acceptTerms', ''); });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(form);
    var data = {
      firstName: String(fd.get('firstName') || '').trim(),
      email: String(fd.get('email') || '').trim(),
      country: String(fd.get('country') || '').trim(),
      acceptTerms: fd.get('acceptTerms') === 'on',
      marketing: fd.get('marketing') === 'on',
      website: String(fd.get('website') || '')
    };
    ['firstName', 'email', 'acceptTerms'].forEach(function (k) { setFieldError(k, ''); });
    var errs = validate(data);
    if (Object.keys(errs).length) {
      Object.keys(errs).forEach(function (k) { setFieldError(k, errs[k]); });
      var first = $('[aria-invalid="true"]') || $('[name="' + Object.keys(errs)[0] + '"]');
      if (first) first.focus();
      return;
    }
    var btn = $('#reg-submit'), label = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner"></span> Reserving your gift';
    api('register.php', { body: data }).then(function (d) {
      btn.disabled = false; btn.innerHTML = label;
      if (!d.ok) {
        if (d.fields) Object.keys(d.fields).forEach(function (k) { setFieldError(k, d.fields[k]); });
        var a = $('#form-alert'); a.textContent = d.error || 'Something went wrong. Please try again.'; a.hidden = false;
        a.scrollIntoView({ block: 'center', behavior: REDUCED ? 'auto' : 'smooth' });
        return;
      }
      if (d.state === 'created') {
        state.device = d.device; store.set(KEY_DEVICE, d.device);
        state.devLink = d.devLink || null;
        setPass(d.pass);
        form.reset();
        show('pass', { celebrate: true });
      } else {
        var who = d.emailHint ? '<b>' + esc(d.emailHint) + '</b>' : 'that address';
        $('#check-text').innerHTML = d.state === 'exists'
          ? 'This email already has a Hala Card pass. We\'ve sent a secure link to ' + who + ' so you can open it on this phone.'
          : 'You\'ve already started. We\'ve re-sent the confirmation link to ' + who + '.';
        if (d.devLink) $('#check-text').innerHTML += '<br><br><b>Test mode:</b> <a href="' + esc(d.devLink) + '">open the confirmation link</a>.';
        show('check');
      }
    });
  });

  /* ───────── confirmation link ───────── */
  function confirmLink(token) {
    history.replaceState(null, '', location.pathname + '#pass');
    show(state.pass ? 'pass' : 'welcome', { noHash: true });
    api('verify.php', { body: { token: token } }).then(function (d) {
      if (!d.ok) {
        show('register');
        var a = $('#form-alert'); a.textContent = d.error || 'This link is no longer valid.'; a.hidden = false;
        return;
      }
      state.device = d.device; store.set(KEY_DEVICE, d.device);
      setPass(d.pass);
      show('pass', { celebrate: true, noHash: true });
      if (d.confirmed && d.pass.status === 'reserved') toast('Email confirmed. Your gift is reserved!');
    });
  }

  /* ───────── gift card / pass ───────── */
  function setPass(p) {
    state.pass = p;
    store.set(KEY_PASS, p ? JSON.stringify(p) : null);
    $('#have-pass').hidden = !p;
  }

  function renderPass(withCelebration) {
    var p = state.pass; if (!p) return;
    if (p.limit) LIMIT = p.limit;
    $$('[data-name]').forEach(function (el) { el.textContent = firstWord(p.firstName); });
    $$('[data-limit]').forEach(function (el) { el.textContent = LIMIT; });
    var card = $('#pass'), title = $('#pass-title');
    var meta = $('#pass-meta'), notice = $('#pass-notice'), resend = $('#resend-btn');
    card.setAttribute('data-status', p.status);
    resend.hidden = true; notice.hidden = true; meta.innerHTML = '';

    if (p.status === 'reserved') {
      title.innerHTML = '<span>Your</span> Hala Card<br>Gift is Reserved!';
      meta.innerHTML = '<span class="tag-pill gold">' + icon('star') + 'Explorer #' + esc(p.slot) + ' of ' + LIMIT + '</span>' +
        '<span class="tag-pill">Code <span class="code">' + esc(p.code) + '</span></span>';
      if (p.redeemed) meta.innerHTML += '<span class="tag-pill">Gift claimed</span>';
    } else if (p.status === 'waitlisted') {
      title.innerHTML = '<span>You\'re on the</span> Hala Card<br>Launch List';
      notice.innerHTML = 'All ' + LIMIT + ' Sierah Expo gifts were claimed before your email was confirmed. We\'ll be in touch when Hala Card launches in December.';
      notice.hidden = false;
    } else {
      title.innerHTML = '<span>One step left to</span> reserve<br>your Hala Card Gift';
      notice.innerHTML = '<b>Confirm your email to secure your gift.</b> We sent a link to ' + esc(p.emailHint) + '. Only the first ' + LIMIT + ' confirmed visitors receive it.';
      if (p.testMode) {
        notice.innerHTML += '<span class="test-mode"><b>Test mode:</b> emails aren\'t really sent locally. ' +
          (state.devLink ? '<a class="btn btn-outline btn-sm" href="' + esc(state.devLink) + '">Open confirmation link</a>'
                         : 'Tap <b>Resend</b> to show the confirmation link here.') + '</span>';
      }
      notice.hidden = false; resend.hidden = false;
    }
    $('#manage-link').href = p.manageUrl;
    renderTimeline(p);

    card.classList.remove('is-revealed'); void card.offsetWidth; card.classList.add('is-revealed');
    if (withCelebration) setTimeout(celebrate, 700);
  }

  function renderTimeline(p) {
    var current = p.redeemed ? 3 : p.status === 'reserved' ? 1 : p.status === 'waitlisted' ? -1 : 0;
    $$('#timeline li').forEach(function (li, i) {
      li.classList.toggle('done', current > i);
      li.classList.toggle('current', current === i);
      if (current === i) li.setAttribute('aria-current', 'step'); else li.removeAttribute('aria-current');
    });
    $('#timeline').hidden = current < 0;
  }

  function refreshPass() {
    if (!state.device) return;
    api('pass.php').then(function (d) {
      if (d.ok) {
        var hadNone = !state.pass;
        var changed = hadNone || state.pass.status !== d.pass.status;
        setPass(d.pass);
        if (hadNone && state.current === 'welcome' && /^#?(pass)?$/.test(location.hash)) { show('pass', { noHash: true }); return; }
        if (state.current === 'pass' && changed) renderPass(d.pass.status === 'reserved');
      } else if (d.httpStatus === 401) {
        state.device = null; store.set(KEY_DEVICE, null); setPass(null);
        if (state.current === 'pass') show('welcome');
      }
    });
  }

  $('#pass').addEventListener('click', function () { show('discover'); });
  $('#pass').addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); show('discover'); } });

  (function tilt() {
    var card = $('#pass'); if (REDUCED) return;
    card.addEventListener('pointermove', function (e) {
      var r = card.getBoundingClientRect();
      var x = (e.clientX - r.left) / r.width - .5, y = (e.clientY - r.top) / r.height - .5;
      card.style.animation = 'none'; card.style.opacity = 1;
      card.style.transform = 'rotateY(' + (x * 12) + 'deg) rotateX(' + (-y * 12) + 'deg)';
    });
    card.addEventListener('pointerleave', function () { card.style.transform = ''; });
  })();

  $('#resend-btn').addEventListener('click', function () {
    var b = this; b.disabled = true;
    api('pass.php', { body: { action: 'resend' } }).then(function (d) {
      b.disabled = false;
      if (d.ok && d.devLink) { state.devLink = d.devLink; renderPass(); toast('Test mode: confirmation link shown on the page.'); return; }
      toast(d.ok ? 'Confirmation email sent. Check your inbox.' : (d.error || 'Could not send. Try again shortly.'));
    });
  });

  $('#share-btn').addEventListener('click', function () {
    var p = state.pass; if (!p) return;
    var data = { title: 'My Hala Card gift', text: 'I just reserved my Hala Card gift at Sierah Expo 2026. Discover Madinah with me!', url: p.shareUrl };
    if (navigator.share) { navigator.share(data).catch(function () {}); return; }
    if (navigator.clipboard) navigator.clipboard.writeText(data.url).then(function () { toast('Share link copied'); }, function () { prompt('Copy your share link', data.url); });
    else prompt('Copy your share link', data.url);
  });

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && state.pass && state.pass.status === 'pending') refreshPass();
  });

  /* ───────── discover ───────── */
  var GROUPS = ['Food & Drink', 'Culture & History', 'Nature & Outdoors', 'Family Fun'];
  var activeCat = 'All', rendered = false;
  var favs = {}; try { favs = JSON.parse(store.get(KEY_FAVS) || '{}') || {}; } catch (e) {}
  function saveFavs() { store.set(KEY_FAVS, JSON.stringify(favs)); }

  function renderDiscover() {
    $('#mini-pass').hidden = !state.pass;
    $('#mini-cta').hidden = !!state.pass;
    if (rendered) return;
    rendered = true;
    var tiles = GROUPS.filter(function (g) { return XP.some(function (x) { return x.group === g; }); });
    $('#chips').innerHTML = tiles.map(function (g) {
      return '<button class="cat" type="button" aria-pressed="false" data-cat="' + esc(g) + '"><span class="cat-ic">' + icon(g) + '</span><span>' + esc(g.replace(' & ', ' &\n')) + '</span></button>';
    }).join('');
    drawCards();
  }

  function drawCards() {
    var list = XP.filter(function (x) { return activeCat === 'All' || x.group === activeCat; });
    $('#xp-count').textContent = list.length + (list.length === 1 ? ' place' : ' places');
    $('#cards').innerHTML = list.map(function (x, i) {
      var fav = !!favs[x.id];
      return '<article class="xp-card">' +
        '<a class="xp-link" href="#xp/' + esc(x.id) + '" data-xp="' + esc(x.id) + '">' +
          '<div class="xp-photo"><img src="' + esc(x.image) + '" alt="" loading="' + (i < 2 ? 'eager' : 'lazy') + '" decoding="async">' +
          '<span class="xp-tag">' + icon(x.group) + esc(x.category) + '</span></div>' +
          '<div class="xp-body"><h3 class="xp-name">' + esc(x.name) + '</h3>' +
          '<p class="xp-meta">' + icon('pin') + esc(x.area) + '</p>' +
          '<p class="xp-desc">' + esc(x.short) + '</p>' +
          '<p class="xp-benefit">' + icon('gift') + 'Hala Card benefit</p></div>' +
        '</a>' +
        '<button class="fav' + (fav ? ' on' : '') + '" type="button" data-fav="' + esc(x.id) + '" aria-pressed="' + fav + '" aria-label="Save ' + esc(x.name) + '">' + icon('heart') + '</button>' +
      '</article>';
    }).join('');
    var cards = $$('.xp-card');
    if ('IntersectionObserver' in window && !REDUCED) {
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
      }, { rootMargin: '0px 0px -6% 0px' });
      cards.forEach(function (c, i) { c.style.animationDelay = (i % 2) * 0.06 + 's'; io.observe(c); });
    } else cards.forEach(function (c) { c.classList.add('in'); });
  }

  function toggleFav(id, btn) {
    favs[id] = !favs[id]; if (!favs[id]) delete favs[id];
    saveFavs();
    $$('[data-fav="' + id + '"]').forEach(function (b) {
      b.classList.toggle('on', !!favs[id]); b.setAttribute('aria-pressed', String(!!favs[id]));
    });
    if (btn && !REDUCED) { btn.classList.remove('pop'); void btn.offsetWidth; btn.classList.add('pop'); }
  }

  $('#chips').addEventListener('click', function (e) {
    var b = e.target.closest('[data-cat]'); if (!b) return;
    var cat = b.getAttribute('data-cat');
    activeCat = activeCat === cat ? 'All' : cat;   // tap again to clear
    $$('.cat').forEach(function (c) { c.setAttribute('aria-pressed', String(c.getAttribute('data-cat') === activeCat)); });
    drawCards();
  });

  $('#cards').addEventListener('click', function (e) {
    var f = e.target.closest('[data-fav]');
    if (f) { e.preventDefault(); return toggleFav(f.getAttribute('data-fav'), f); }
    var a = e.target.closest('[data-xp]'); if (!a) return;
    e.preventDefault(); openSheet(a.getAttribute('data-xp'));
  });

  /* ───────── 5 · experience detail ───────── */
  var lastFocus = null;
  function openSheet(id, fromRoute) {
    var x = XP.filter(function (e) { return e.id === id; })[0];
    if (!x) return;
    if (!fromRoute) history.pushState(null, '', '#xp/' + id);
    lastFocus = document.activeElement;
    var i = XP.indexOf(x), next = XP[(i + 1) % XP.length], fav = !!favs[x.id];
    var cta = state.pass
      ? '<button class="btn btn-green" data-go="pass" data-close>View my gift pass</button>'
      : '<button class="btn btn-gold" data-go="register" data-close>Reserve my free gift' + icon('arrow') + '</button>';
    $('#sheet-panel').innerHTML =
      '<div class="d-hero"><img src="' + esc(x.image) + '" alt="' + esc(x.name) + '">' +
        '<button class="round-btn d-back" data-close aria-label="Back">' + icon('back') + '</button>' +
        '<button class="round-btn d-fav fav' + (fav ? ' on' : '') + '" data-fav="' + esc(x.id) + '" aria-pressed="' + fav + '" aria-label="Save ' + esc(x.name) + '">' + icon('heart') + '</button>' +
      '</div>' +
      '<div class="d-body">' +
        '<h2 class="d-title" id="sheet-title">' + esc(x.name) + '</h2>' +
        '<div class="d-meta"><span>' + icon('pin') + esc(x.area) + '</span><span>' + icon(x.group) + esc(x.group) + '</span></div>' +
        '<p class="d-text">' + esc(x.about) + '</p>' +
        '<div class="d-benefit"><span class="d-benefit-ic">' + icon('gift') + '</span><div><small>Your Hala Card benefit</small><b>' + esc(x.benefit || 'Exclusive Hala Card offer') + '</b><em>Revealed at launch · subject to partner confirmation</em></div></div>' +
        '<h3 class="d-h3">Highlights</h3>' +
        '<ul class="highlights">' + x.highlights.map(function (h) { return '<li>' + esc(h) + '</li>'; }).join('') + '</ul>' +
        '<div class="facts"><div class="fact"><small>Best for</small><span>' + esc(x.bestFor) + '</span></div>' +
        '<div class="fact"><small>Category</small><span>' + esc(x.category) + '</span></div></div>' +
        cta +
        '<button class="btn btn-outline" data-next="' + esc(next.id) + '">Next: ' + esc(next.name) + '</button>' +
      '</div>';
    $('#sheet').classList.add('open');
    document.body.style.overflow = 'hidden';
    $('#sheet-panel').scrollTop = 0;
    setTimeout(function () { var c = $('.d-back'); if (c) c.focus({ preventScroll: true }); }, 60);
  }

  function closeSheet(fromRoute) {
    var s = $('#sheet');
    if (!s.classList.contains('open')) return;
    s.classList.remove('open');
    document.body.style.overflow = '';
    if (!fromRoute && location.hash.indexOf('#xp/') === 0) history.replaceState(null, '', '#discover');
    if (lastFocus && lastFocus.focus) lastFocus.focus({ preventScroll: true });
  }

  $('#sheet').addEventListener('click', function (e) {
    var f = e.target.closest('[data-fav]');
    if (f) return toggleFav(f.getAttribute('data-fav'), f);
    var n = e.target.closest('[data-next]');
    if (n) { history.replaceState(null, '', '#xp/' + n.getAttribute('data-next')); return openSheet(n.getAttribute('data-next'), true); }
    if (e.target.closest('[data-close]') && !e.target.closest('[data-go]')) closeSheet();
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeSheet(); });

  /* ───────── countries ───────── */
  ['Saudi Arabia', 'United Arab Emirates', 'Kuwait', 'Qatar', 'Bahrain', 'Oman', 'Egypt', 'Jordan', 'Turkey', 'Pakistan', 'India', 'Bangladesh',
   'Indonesia', 'Malaysia', 'Singapore', 'Nigeria', 'South Africa', 'Morocco', 'Algeria', 'Tunisia', 'United Kingdom', 'United States', 'Canada',
   'Australia', 'France', 'Germany', 'Netherlands', 'Other'].forEach(function (c) {
    var o = document.createElement('option'); o.value = c; o.textContent = c; $('#country').appendChild(o);
  });

  /* ───────── boot ───────── */
  try { var cached = store.get(KEY_PASS); if (cached && state.device) setPass(JSON.parse(cached)); } catch (e) {}
  loadStats();
  route();
  refreshPass();
})();
