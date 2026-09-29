/* Builds the animated science background (blobs, discs, atoms, ripples, bubbles) inside .sci-bg */
(function () {
  var bg = document.querySelector('.sci-bg'); if (!bg) return;
  var small = window.matchMedia('(max-width:768px)').matches;
  var r = function (a, b) { return a + Math.random() * (b - a); };
  function add(cls, css, html) {
    var d = document.createElement('div'); d.className = cls; d.style.cssText = css;
    if (html) d.innerHTML = html; bg.appendChild(d); return d;
  }
  // soft colour blobs
  [['#2B5FE2', 46, '-10%', '-8%', 42], ['#7B4DFF', 38, '62%', '30%', 55], ['#12B5A0', 34, '18%', '70%', 48]].forEach(function (b) {
    add('sci-blob', 'width:' + b[1] + 'vmax;height:' + b[1] + 'vmax;left:' + b[2] + ';top:' + b[3] + ';background:' + b[0] +
      ';--dur:' + b[4] + 's;--dx:' + r(-90, 90) + 'px;--dy:' + r(-70, 70) + 'px');
  });
  // rotating discs (petri dish / CD)
  [[520, '-8%', '52%', 110], [340, '74%', '-6%', 80], [240, '48%', '78%', 65], [420, '84%', '58%', 140]].slice(0, small ? 2 : 4).forEach(function (d, i) {
    add('sci-disc', 'width:' + d[0] + 'px;height:' + d[0] + 'px;left:' + d[1] + ';top:' + d[2] + ';--dur:' + d[3] + 's' + (i % 2 ? ';animation-direction:reverse' : ''));
  });
  // atoms with orbiting electrons
  function atom(size) {
    var e = [['0', '5s', '#7fb0ff'], ['60', '7s', '#FFC93C'], ['120', '6s', '#5eead4']].map(function (o) {
      return '<g transform="rotate(' + o[0] + ')"><ellipse rx="90" ry="32" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="1"/>' +
        '<circle r="4" fill="' + o[2] + '"><animateMotion dur="' + o[1] + '" repeatCount="indefinite" path="M-90,0 A90,32 0 1,1 90,0 A90,32 0 1,1 -90,0"/></circle></g>';
    }).join('');
    return '<svg viewBox="-100 -100 200 200" width="' + size + '" height="' + size + '"><circle r="14" fill="rgba(255,201,60,.18)"/><circle r="6" fill="#FFC93C"/>' + e + '</svg>';
  }
  [[300, '6%', '8%', 140], [220, '78%', '44%', 100], [180, '30%', '84%', 120]].slice(0, small ? 1 : 3).forEach(function (a) {
    add('sci-atom', 'left:' + a[1] + ';top:' + a[2] + ';--dur:' + a[3] + 's', atom(a[0]));
  });
  // sonar-style ripples
  [['70%', '10%', 360], ['14%', '46%', 300]].slice(0, small ? 1 : 2).forEach(function (p) {
    var el = add('sci-ripple', 'left:' + p[0] + ';top:' + p[1] + ';width:' + p[2] + 'px;height:' + p[2] + 'px',
      '<i></i><i style="animation-delay:3s"></i><i style="animation-delay:6s"></i>');
  });
  // rising bubbles
  var n = small ? 10 : 22;
  for (var i = 0; i < n; i++) {
    var s = r(14, 84);
    add('sci-bubble', 'width:' + s + 'px;height:' + s + 'px;left:' + r(0, 98) + '%;--dur:' + r(18, 40) + 's;--delay:' + (-r(0, 30)) + 's;--dx:' + r(-60, 60) + 'px');
  }
})();

/* Cookie consent banner (remembers the choice in localStorage when available) */
(function () {
  var k = 'loop_cookie_choice';
  try { if (localStorage.getItem(k)) return; } catch (e) {}
  var b = document.createElement('div'); b.className = 'cookie'; b.setAttribute('role', 'dialog'); b.setAttribute('aria-label', 'Cookie preferences');
  b.innerHTML = '<span style="flex:1;min-width:200px">We use cookies to keep Loop working and to understand how it is used. See our <a href="privacy-policy.html">Privacy Policy</a>.</span>' +
    '<button class="btn btn-outline-ink" data-c="no" type="button">Decline</button><button class="btn btn-cobalt" data-c="yes" type="button">Accept</button>';
  b.addEventListener('click', function (e) {
    var c = e.target.getAttribute && e.target.getAttribute('data-c'); if (!c) return;
    try { localStorage.setItem(k, c); } catch (x) {} b.remove();
  });
  document.body.appendChild(b);
})();

/* Reduce-motion control: follows the OS setting by default, footer button overrides and is remembered */
(function () {
  var K = 'loop_motion', root = document.documentElement, mq = window.matchMedia('(prefers-reduced-motion: reduce)');
  function stored() { try { return localStorage.getItem(K); } catch (e) { return null; } }
  function reduced() { var s = stored(); return s ? s === 'reduced' : mq.matches; }
  function apply() {
    var r = reduced();
    root.setAttribute('data-motion', r ? 'reduced' : 'full');
    document.querySelectorAll('.sci-bg svg').forEach(function (s) { try { r ? s.pauseAnimations() : s.unpauseAnimations(); } catch (e) {} });
    var b = document.querySelector('.motion-toggle');
    if (b) { b.setAttribute('aria-pressed', r ? 'true' : 'false'); b.querySelector('span').textContent = r ? 'Motion: reduced' : 'Reduce motion'; }
  }
  var fine = document.querySelector('.ft .fine');
  if (fine) {
    var b = document.createElement('button'); b.type = 'button'; b.className = 'motion-toggle';
    b.innerHTML = '<i class="bi bi-activity"></i><span>Reduce motion</span>';
    b.addEventListener('click', function () { try { localStorage.setItem(K, reduced() ? 'full' : 'reduced'); } catch (e) {} apply(); });
    fine.insertBefore(b, fine.children[1] || null);
  }
  if (mq.addEventListener) mq.addEventListener('change', apply);
  setTimeout(apply, 0);
})();
