(function () {
'use strict';
var CFG = {{ \Illuminate\Support\Js::from($loader) }};
@verbatim
if (window.__loopChatLoaded && window.__loopChatLoaded[CFG.key]) { return; }
(window.__loopChatLoaded = window.__loopChatLoaded || {})[CFG.key] = true;

var script = document.currentScript;
var origin;
try { origin = new URL(script && script.src ? script.src : CFG.frame_url).origin; }
catch (e) { origin = new URL(CFG.frame_url).origin; }

var frameSrc = origin + new URL(CFG.frame_url).pathname;
var root, launcher, panel, iframe, badge, isOpen = false, unread = 0;

function ready(fn) {
  if (document.body) { fn(); return; }
  document.addEventListener('DOMContentLoaded', fn);
}

function css() {
  var side = CFG.position === 'left' ? 'left' : 'right';
  return [
    '#loopchat-root{all:initial;position:fixed;bottom:20px;' + side + ':20px;z-index:2147483000;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}',
    '#loopchat-root *{box-sizing:border-box}',
    '#loopchat-root .lc-launcher{all:unset;box-sizing:border-box;position:relative;display:flex;align-items:center;justify-content:center;width:60px;height:60px;border-radius:50%;background:' + CFG.color + ';color:#fff;cursor:pointer;box-shadow:0 6px 24px rgba(20,33,61,.28);transition:transform .15s ease}',
    '#loopchat-root .lc-launcher:hover{transform:scale(1.06)}',
    '#loopchat-root .lc-launcher:focus-visible{outline:3px solid #FFC93C;outline-offset:3px}',
    '#loopchat-root .lc-launcher svg{width:28px;height:28px;fill:currentColor}',
    '#loopchat-root .lc-launcher .lc-ico-close{display:none}',
    '#loopchat-root.lc-open .lc-launcher .lc-ico-chat{display:none}',
    '#loopchat-root.lc-open .lc-launcher .lc-ico-close{display:block}',
    '#loopchat-root .lc-badge{position:absolute;top:-4px;' + (side === 'left' ? 'right' : 'left') + ':-4px;min-width:20px;height:20px;padding:0 5px;border-radius:10px;background:#E5484D;color:#fff;font:600 12px/20px sans-serif;text-align:center;display:none}',
    '#loopchat-root .lc-panel{position:absolute;bottom:76px;' + side + ':0;width:380px;height:620px;max-height:calc(100vh - 110px);border-radius:16px;overflow:hidden;background:#fff;box-shadow:0 12px 48px rgba(20,33,61,.32);opacity:0;transform:translateY(12px) scale(.98);transform-origin:bottom ' + side + ';visibility:hidden;transition:opacity .18s ease,transform .18s ease,visibility 0s linear .18s}',
    '#loopchat-root.lc-open .lc-panel{opacity:1;transform:none;visibility:visible;transition:opacity .18s ease,transform .18s ease}',
    '#loopchat-root .lc-panel iframe{display:block;width:100%;height:100%;border:0;background:#fff}',
    '@media (max-width:480px){#loopchat-root.lc-open{bottom:0;' + side + ':0}#loopchat-root.lc-open .lc-panel{position:fixed;inset:0;width:100%;height:100%;max-height:none;border-radius:0}#loopchat-root.lc-open .lc-launcher{display:none}}',
    '@media (prefers-reduced-motion:reduce){#loopchat-root .lc-panel,#loopchat-root .lc-launcher{transition:none}}'
  ].join('\n');
}

function svg(cls, path, label) {
  var ns = 'http://www.w3.org/2000/svg';
  var el = document.createElementNS(ns, 'svg');
  el.setAttribute('viewBox', '0 0 24 24');
  el.setAttribute('class', cls);
  el.setAttribute('aria-hidden', 'true');
  var p = document.createElementNS(ns, 'path');
  p.setAttribute('d', path);
  el.appendChild(p);
  return el;
}

function post(msg) {
  if (!iframe || !iframe.contentWindow) { return; }
  msg.source = 'loop-chat-host';
  iframe.contentWindow.postMessage(msg, origin);
}

function pageContext() {
  return { url: location.href, title: document.title };
}

function sendVisibility() {
  post({ type: 'visibility', open: isOpen, page: pageContext() });
}

function setBadge(n) {
  unread = n;
  if (n > 0) { badge.textContent = n > 9 ? '9+' : String(n); badge.style.display = 'block'; }
  else { badge.style.display = 'none'; }
  launcher.setAttribute('aria-label', n > 0 ? 'Open chat, ' + n + ' unread' : (isOpen ? 'Close chat' : 'Open chat'));
}

function open() {
  if (isOpen) { return; }
  isOpen = true;
  root.classList.add('lc-open');
  launcher.setAttribute('aria-expanded', 'true');
  setBadge(0);
  sendVisibility();
}

function close(returnFocus) {
  if (!isOpen) { return; }
  isOpen = false;
  root.classList.remove('lc-open');
  launcher.setAttribute('aria-expanded', 'false');
  launcher.setAttribute('aria-label', 'Open chat');
  sendVisibility();
  if (returnFocus !== false) { launcher.focus(); }
}

function build() {
  var style = document.createElement('style');
  style.setAttribute('data-loopchat', '');
  style.appendChild(document.createTextNode(css()));
  document.head.appendChild(style);

  root = document.createElement('div');
  root.id = 'loopchat-root';

  panel = document.createElement('div');
  panel.className = 'lc-panel';
  panel.id = 'loopchat-panel';

  iframe = document.createElement('iframe');
  iframe.title = 'Chat';
  iframe.src = frameSrc;
  iframe.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox');
  iframe.setAttribute('referrerpolicy', 'no-referrer');
  iframe.setAttribute('loading', 'eager');
  panel.appendChild(iframe);

  launcher = document.createElement('button');
  launcher.type = 'button';
  launcher.className = 'lc-launcher';
  launcher.setAttribute('aria-label', 'Open chat');
  launcher.setAttribute('aria-expanded', 'false');
  launcher.setAttribute('aria-controls', 'loopchat-panel');
  launcher.appendChild(svg('lc-ico-chat', 'M12 2C6.48 2 2 5.92 2 10.75c0 2.6 1.3 4.93 3.36 6.54L4.5 21.5l4.1-2.1c1.07.28 2.2.43 3.4.43 5.52 0 10-3.92 10-8.75S17.52 2 12 2z'));
  launcher.appendChild(svg('lc-ico-close', 'M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.3-6.3z'));
  badge = document.createElement('span');
  badge.className = 'lc-badge';
  badge.setAttribute('aria-hidden', 'true');
  launcher.appendChild(badge);
  launcher.addEventListener('click', function () { isOpen ? close() : open(); });

  root.appendChild(panel);
  root.appendChild(launcher);
  document.body.appendChild(root);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen) { close(); }
  });

  window.addEventListener('message', function (e) {
    if (e.origin !== origin || !iframe || e.source !== iframe.contentWindow) { return; }
    var d = e.data;
    if (!d || d.source !== 'loop-chat') { return; }
    if (d.type === 'ready') { sendVisibility(); }
    else if (d.type === 'close') { close(); }
    else if (d.type === 'unread') { if (!isOpen) { setBadge(Number(d.count) || 0); } }
  });
}

ready(build);

window.LoopChat = {
  open: function () { ready(open); },
  close: function () { ready(function () { close(false); }); },
  toggle: function () { ready(function () { isOpen ? close(false) : open(); }); }
};
@endverbatim
})();
