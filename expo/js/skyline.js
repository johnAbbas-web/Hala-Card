/* Elegant line-art Madinah skyline (Uhud ridge, minarets, Green Dome, palms).
   Injected into any element with [data-skyline]. */
(function () {
  function minaret(x, base, h, w) {
    var top = base - h, s = '';
    s += '<path d="M' + (x - w) + ' ' + base + ' L' + (x - w) + ' ' + (top + 26) + ' L' + (x - w * 0.7) + ' ' + (top + 18) + ' L' + (x - w * 0.7) + ' ' + (top + 8) + ' Q' + x + ' ' + (top - 6) + ' ' + (x + w * 0.7) + ' ' + (top + 8) + ' L' + (x + w * 0.7) + ' ' + (top + 18) + ' L' + (x + w) + ' ' + (top + 26) + ' L' + (x + w) + ' ' + base + '"/>';
    s += '<line x1="' + (x - w - 2) + '" y1="' + (top + h * 0.45) + '" x2="' + (x + w + 2) + '" y2="' + (top + h * 0.45) + '"/>';
    s += '<line x1="' + (x - w - 2) + '" y1="' + (top + h * 0.22) + '" x2="' + (x + w + 2) + '" y2="' + (top + h * 0.22) + '"/>';
    s += '<line x1="' + x + '" y1="' + (top - 6) + '" x2="' + x + '" y2="' + (top - 16) + '"/>';
    s += '<path d="M' + (x - 2.5) + ' ' + (top - 18) + ' a3 3 0 1 0 5 -1" />';
    return s;
  }
  function palm(x, base, h, flip) {
    var t = base - h, d = flip ? -1 : 1;
    return '<path d="M' + x + ' ' + base + ' Q' + (x + 4 * d) + ' ' + (base - h / 2) + ' ' + x + ' ' + t + '"/>' +
      '<path d="M' + x + ' ' + t + ' q-14 -2 -22 10 M' + x + ' ' + t + ' q14 -4 22 8 M' + x + ' ' + t + ' q-8 -12 -20 -10 M' + x + ' ' + t + ' q8 -13 19 -11 M' + x + ' ' + t + ' q-2 -10 -4 -16"/>';
  }
  function umbrella(x, base) {
    return '<line x1="' + x + '" y1="' + base + '" x2="' + x + '" y2="' + (base - 22) + '"/>' +
      '<path d="M' + (x - 16) + ' ' + (base - 20) + ' Q' + (x - 8) + ' ' + (base - 32) + ' ' + x + ' ' + (base - 24) + ' Q' + (x + 8) + ' ' + (base - 32) + ' ' + (x + 16) + ' ' + (base - 20) + '"/>';
  }
  var B = 200; // ground line
  var g = '';
  // Uhud mountain ridge behind
  g += '<path class="sk-far" d="M0 ' + (B - 40) + ' L40 ' + (B - 62) + ' L78 ' + (B - 50) + ' L120 ' + (B - 88) + ' L150 ' + (B - 74) + ' L190 ' + (B - 104) + ' L232 ' + (B - 70) + ' L262 ' + (B - 80) + ' L300 ' + (B - 52) + ' L560 ' + (B - 50) + ' L600 ' + (B - 84) + ' L640 ' + (B - 66) + ' L690 ' + (B - 98) + ' L730 ' + (B - 70) + ' L770 ' + (B - 78) + ' L800 ' + (B - 46) + '"/>';
  // Mosque body
  g += '<path d="M250 ' + B + ' L250 ' + (B - 46) + ' L550 ' + (B - 46) + ' L550 ' + B + '"/>';
  for (var i = 0; i < 12; i++) {
    var ax = 262 + i * 24;
    g += '<path d="M' + ax + ' ' + B + ' L' + ax + ' ' + (B - 22) + ' Q' + (ax + 8) + ' ' + (B - 34) + ' ' + (ax + 16) + ' ' + (B - 22) + ' L' + (ax + 16) + ' ' + B + '"/>';
  }
  g += '<line x1="250" y1="' + (B - 40) + '" x2="550" y2="' + (B - 40) + '"/>';
  // Green Dome
  g += '<path class="sk-dome" d="M372 ' + (B - 46) + ' L372 ' + (B - 62) + ' Q372 ' + (B - 104) + ' 400 ' + (B - 112) + ' Q428 ' + (B - 104) + ' 428 ' + (B - 62) + ' L428 ' + (B - 46) + ' Z"/>';
  g += '<line x1="400" y1="' + (B - 112) + '" x2="400" y2="' + (B - 128) + '"/><path d="M396 ' + (B - 130) + ' a4 4 0 1 0 7 -2"/>';
  // Small domes
  g += '<path d="M300 ' + (B - 46) + ' Q312 ' + (B - 66) + ' 324 ' + (B - 46) + ' M476 ' + (B - 46) + ' Q488 ' + (B - 66) + ' 500 ' + (B - 46) + '"/>';
  // Minarets
  g += minaret(262, B - 46, 104, 6) + minaret(538, B - 46, 104, 6) + minaret(338, B - 46, 132, 7) + minaret(462, B - 46, 132, 7);
  // Umbrellas and palms
  g += umbrella(190, B) + umbrella(226, B) + umbrella(574, B) + umbrella(610, B);
  g += palm(70, B, 70) + palm(110, B, 52, true) + palm(150, B, 62) + palm(660, B, 60, true) + palm(700, B, 74) + palm(742, B, 54, true);
  g += '<line class="sk-ground" x1="0" y1="' + B + '" x2="800" y2="' + B + '"/>';

  var svg = '<svg viewBox="0 40 800 166" preserveAspectRatio="xMidYMax meet" aria-hidden="true" focusable="false">' +
    '<g fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">' + g + '</g></svg>';

  document.querySelectorAll('[data-skyline]').forEach(function (el) { el.innerHTML = svg; });
  window.HALA_SKYLINE = svg;
})();
