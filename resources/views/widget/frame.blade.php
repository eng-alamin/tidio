<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Chat</title>
<style nonce="{{ $nonce }}">
:root{--c:#2B5FE2;--ct:#fff;--ink:#14213D;--soft:#4A5578;--mist:#E7ECF5;--line:#DCE2EF;--bad:#C0262D;--ok:#1FAA59}
*{box-sizing:border-box}
[hidden]{display:none!important}
html,body{height:100%;margin:0}
body{font:15px/1.45 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Noto Sans Bengali",Helvetica,Arial,sans-serif;color:var(--ink);background:#fff}
.app{display:flex;flex-direction:column;height:100%}
.hd{display:flex;align-items:center;gap:12px;padding:16px 16px 14px 20px;background:var(--c);color:var(--ct)}
.hd-text{flex:1;min-width:0}
.hd h1{margin:0;font-size:17px;font-weight:700;line-height:1.25;overflow-wrap:anywhere}
.status{margin:3px 0 0;font-size:13px;opacity:.92;display:flex;align-items:center;gap:6px}
.dot{width:8px;height:8px;border-radius:50%;background:#9AA3B8;flex:none}
.dot.on{background:#46E08A}
.icon-btn{all:unset;box-sizing:border-box;display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;cursor:pointer;color:inherit}
.icon-btn:hover{background:rgba(255,255,255,.18)}
.icon-btn:focus-visible{outline:2px solid currentColor;outline-offset:1px}
.icon-btn svg{width:22px;height:22px;fill:currentColor}
.log{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:6px;background:#F7F9FD;scroll-behavior:smooth}
.row{display:flex;flex-direction:column;max-width:84%}
.row.visitor{align-self:flex-end;align-items:flex-end}
.row.agent,.row.bot{align-self:flex-start;align-items:flex-start}
.row.system{align-self:center;max-width:92%;align-items:center}
.who{font-size:12px;color:var(--soft);margin:6px 4px 2px}
.bubble{padding:9px 13px;border-radius:16px;white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word}
.visitor .bubble{background:var(--c);color:var(--ct);border-bottom-right-radius:5px}
.agent .bubble,.bot .bubble{background:#fff;border:1px solid var(--line);border-bottom-left-radius:5px}
.system .bubble{background:transparent;color:var(--soft);font-size:13px;text-align:center;padding:4px 8px}
.bubble a{color:inherit;text-decoration:underline}
.meta{font-size:11px;color:var(--soft);margin:2px 4px 0}
.row.failed .meta{color:var(--bad)}
.row.sending .bubble{opacity:.65}
.retry{all:unset;cursor:pointer;color:var(--bad);text-decoration:underline;font-size:11px}
.retry:focus-visible{outline:2px solid var(--bad);outline-offset:2px}
.prompt{padding:12px 16px;border-top:1px solid var(--line);background:#fff}
.prompt p{margin:0 0 8px;font-size:13px;color:var(--soft)}
.prompt form{display:flex;flex-direction:column;gap:8px}
.prompt input{font:inherit;padding:9px 11px;border:1px solid var(--line);border-radius:10px;min-height:40px;width:100%}
.prompt input:focus-visible,.composer textarea:focus-visible{outline:2px solid var(--c);outline-offset:0;border-color:var(--c)}
.prompt .actions{display:flex;gap:8px}
.btn{font:inherit;font-weight:600;border:0;border-radius:10px;padding:0 16px;min-height:40px;cursor:pointer;background:var(--c);color:var(--ct)}
.btn.ghost{background:transparent;color:var(--soft)}
.btn:focus-visible{outline:2px solid var(--ink);outline-offset:2px}
.btn[disabled]{opacity:.5;cursor:not-allowed}
.err{color:var(--bad);font-size:12px;margin:0}
.composer{border-top:1px solid var(--line);background:#fff;padding:10px 12px}
.composer form{display:flex;align-items:flex-end;gap:8px}
.composer textarea{flex:1;font:inherit;resize:none;border:1px solid var(--line);border-radius:12px;padding:10px 12px;max-height:120px;min-height:42px;line-height:1.35}
.composer .btn{min-height:42px}
@media (prefers-reduced-motion:reduce){.log{scroll-behavior:auto}}
</style>
</head>
<body data-config="{{ json_encode($config, JSON_UNESCAPED_UNICODE) }}">
<div class="app">
  <header class="hd">
    <div class="hd-text">
      <h1 id="title"></h1>
      <p class="status"><span class="dot" id="dot"></span><span id="statusText"></span></p>
    </div>
    <button class="icon-btn" id="closeBtn" type="button">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.3 5.71 12 12l6.3 6.29-1.41 1.42L10.59 13.4 4.3 19.71 2.89 18.3 9.17 12 2.89 5.71 4.3 4.29l6.29 6.3 6.3-6.3z"/></svg>
    </button>
  </header>

  <main class="log" id="log" role="log" aria-live="polite" aria-relevant="additions"></main>

  <section class="prompt" id="emailPrompt" hidden>
    <p id="emailTitle"></p>
    <form id="emailForm" novalidate>
      <input id="nameInput" type="text" name="name" maxlength="120" autocomplete="name">
      <input id="emailInput" type="email" name="email" maxlength="190" autocomplete="email" required>
      <p class="err" id="emailErr" role="alert" hidden></p>
      <div class="actions">
        <button class="btn" id="emailSave" type="submit"></button>
        <button class="btn ghost" id="emailSkip" type="button"></button>
      </div>
    </form>
  </section>

  <footer class="composer">
    <form id="form">
      <textarea id="input" rows="1"></textarea>
      <button class="btn" id="sendBtn" type="submit"></button>
    </form>
  </footer>
</div>

<script nonce="{{ $nonce }}">
(function () {
'use strict';

var cfg = JSON.parse(document.body.dataset.config);
var S = cfg.strings;
var base = location.pathname.replace(/\/widget-frame\/[^\/]*$/, '') + '/widget-api/' + cfg.key;

var $ = function (id) { return document.getElementById(id); };
var logEl = $('log'), input = $('input'), sendBtn = $('sendBtn'), form = $('form');
var emailPrompt = $('emailPrompt'), emailForm = $('emailForm'), emailErr = $('emailErr');

// ---------------------------------------------------------------- storage (may be blocked)
var memory = {};
var store = {
  get: function (k) { try { var v = window.localStorage.getItem(k); return v === null ? (memory[k] || null) : v; } catch (e) { return memory[k] || null; } },
  set: function (k, v) { memory[k] = v; try { window.localStorage.setItem(k, v); } catch (e) {} },
  del: function (k) { delete memory[k]; try { window.localStorage.removeItem(k); } catch (e) {} }
};
var SID_KEY = 'loopchat_sid_' + cfg.key;
var DISMISS_KEY = 'loopchat_email_dismissed_' + cfg.key;

// ---------------------------------------------------------------- state
var sid = store.get(SID_KEY);
var page = '';
var isOpen = false;
var started = false;
var lastId = 0;
var known = {};           // message id -> row element
var hasConv = false;
var convStatus = null;
var identified = false;
var online = cfg.online;
var unread = 0;
var errors = 0;
var timer = null;
var polling = false;
var tmpCounter = 0;
var lastFrom = null, lastName = null;
var disabled = false;
var sessionReady = false;   // the composer can type before this, but can't send

// ---------------------------------------------------------------- theming
function contrast(hex) {
  var h = hex.replace('#', '');
  if (h.length === 3) { h = h.split('').map(function (c) { return c + c; }).join(''); }
  var n = parseInt(h, 16), r = (n >> 16) & 255, g = (n >> 8) & 255, b = n & 255;
  var lin = function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
  var L = 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
  return L > 0.4 ? '#14213D' : '#FFFFFF';
}
document.documentElement.style.setProperty('--c', cfg.color);
document.documentElement.style.setProperty('--ct', contrast(cfg.color));

// ---------------------------------------------------------------- static text
$('title').textContent = S.header;
$('closeBtn').setAttribute('aria-label', S.close);
$('closeBtn').setAttribute('title', S.close);
input.setAttribute('placeholder', S.placeholder);
input.setAttribute('aria-label', S.placeholder);
input.setAttribute('maxlength', String(cfg.max_length));
sendBtn.textContent = S.send_button;
sendBtn.disabled = true;
$('emailTitle').textContent = S.email_title;
$('nameInput').setAttribute('placeholder', S.name_placeholder);
$('nameInput').setAttribute('aria-label', S.name_placeholder);
$('emailInput').setAttribute('placeholder', S.email_placeholder);
$('emailInput').setAttribute('aria-label', S.email_placeholder);
$('emailSave').textContent = S.email_save;
$('emailSkip').textContent = S.email_skip;

function renderStatus() {
  $('dot').className = 'dot' + (online ? ' on' : '');
  $('statusText').textContent = online ? S.online : S.offline;
}
renderStatus();

// ---------------------------------------------------------------- rendering
var URL_RE = /https?:\/\/[^\s<]+[^\s<.,;:!?)\]'"]/gi;

function linkify(parent, text) {
  var last = 0, m;
  URL_RE.lastIndex = 0;
  while ((m = URL_RE.exec(text)) !== null) {
    if (m.index > last) { parent.appendChild(document.createTextNode(text.slice(last, m.index))); }
    var a = document.createElement('a');
    a.href = m[0];
    a.textContent = m[0];
    a.target = '_blank';
    a.rel = 'noopener noreferrer nofollow';
    parent.appendChild(a);
    last = m.index + m[0].length;
  }
  if (last < text.length) { parent.appendChild(document.createTextNode(text.slice(last))); }
}

function timeLabel(iso) {
  try { return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }); }
  catch (e) { return ''; }
}

function nearBottom() {
  return logEl.scrollHeight - logEl.scrollTop - logEl.clientHeight < 80;
}

function scrollDown() {
  logEl.scrollTop = logEl.scrollHeight;
}

/** m: { from, body, name, at, state } */
function addRow(m) {
  var stick = nearBottom() || m.from === 'visitor';
  var row = document.createElement('div');
  row.className = 'row ' + m.from + (m.state ? ' ' + m.state : '');

  if ((m.from === 'agent' || m.from === 'bot') && m.name && (lastFrom !== m.from || lastName !== m.name)) {
    var who = document.createElement('div');
    who.className = 'who';
    who.textContent = m.name;
    row.appendChild(who);
  }

  var bubble = document.createElement('div');
  bubble.className = 'bubble';
  linkify(bubble, m.body);
  row.appendChild(bubble);

  if (m.from !== 'system') {
    var meta = document.createElement('div');
    meta.className = 'meta';
    meta.textContent = m.state === 'sending' ? S.sending : timeLabel(m.at);
    row.appendChild(meta);
  }

  lastFrom = m.from;
  lastName = m.name || null;
  logEl.appendChild(row);
  if (stick) { scrollDown(); }
  return row;
}

function setRowState(row, state, at) {
  row.className = row.className.replace(/\b(sending|failed)\b/g, '').replace(/\s+/g, ' ').trim();
  if (state) { row.className += ' ' + state; }
  var meta = row.querySelector('.meta');
  if (!meta) { return; }
  meta.textContent = '';
  if (state === 'sending') { meta.textContent = S.sending; }
  else if (state === 'failed') { return meta; }
  else { meta.textContent = timeLabel(at); }
  return meta;
}

function resetLog() {
  logEl.textContent = '';
  known = {};
  lastFrom = null;
  lastName = null;
  lastId = 0;
  addRow({ from: 'agent', body: cfg.welcome_message, at: new Date().toISOString() });
}

function addServerMessage(m) {
  if (known[m.id]) { return false; }
  known[m.id] = addRow({ from: m.from, body: m.body, name: m.name, at: m.at });
  if (m.id > lastId) { lastId = m.id; }
  return true;
}

// ---------------------------------------------------------------- host page <-> frame
function toHost(msg) {
  msg.source = 'loop-chat';
  // The embedding page's origin isn't knowable here (no-referrer); payloads carry nothing sensitive.
  try { window.parent.postMessage(msg, '*'); } catch (e) {}
}

window.addEventListener('message', function (e) {
  if (e.source !== window.parent) { return; }
  var d = e.data;
  if (!d || d.source !== 'loop-chat-host' || d.type !== 'visibility') { return; }

  isOpen = !!d.open;
  if (d.page && typeof d.page.url === 'string') { page = d.page.url; }
  start();

  if (isOpen) {
    unread = 0;
    toHost({ type: 'unread', count: 0 });
    scrollDown();
    if (!disabled) { input.focus(); }
    if (hasConv) { poll(); } else { schedule(); }
  } else {
    schedule();
  }
});

$('closeBtn').addEventListener('click', function () { toHost({ type: 'close' }); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { toHost({ type: 'close' }); } });
document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible' && hasConv) { poll(); } });

// ---------------------------------------------------------------- API
function api(method, path, body) {
  var headers = { 'Accept': 'application/json' };
  if (sid) { headers['X-Widget-Session'] = sid; }
  if (body) { headers['Content-Type'] = 'application/json'; }

  return fetch(base + path, {
    method: method,
    headers: headers,
    body: body ? JSON.stringify(body) : undefined,
    credentials: 'omit',
    cache: 'no-store'
  }).then(function (res) {
    return res.json().catch(function () { return {}; }).then(function (data) {
      if (!res.ok) {
        var err = new Error(data.message || ('HTTP ' + res.status));
        err.status = res.status;
        err.data = data;
        throw err;
      }
      return data;
    });
  });
}

function setDisabled(on, message) {
  disabled = on;
  input.disabled = on;
  sendBtn.disabled = on;
  if (on && message) { addRow({ from: 'system', body: message, at: new Date().toISOString() }); }
}

// ---------------------------------------------------------------- session
function start() {
  if (started) { return; }
  started = true;
  init();
}

function init() {
  return api('POST', '/init', { session_id: sid, page_url: page || null }).then(function (data) {
    sid = data.session_id;
    store.set(SID_KEY, sid);
    online = data.online;
    identified = !!data.identified;
    renderStatus();

    data.messages.forEach(addServerMessage);
    convStatus = data.conversation ? data.conversation.status : null;
    hasConv = !!data.conversation;
    errors = 0;
    sessionReady = true;
    sendBtn.disabled = disabled;
    schedule();
  }).catch(function (e) {
    if (e.status === 403 || e.status === 404) {
      setDisabled(true, S.unavailable);
    } else {
      errors++;
      if (errors === 1) { addRow({ from: 'system', body: S.error_generic, at: new Date().toISOString() }); }
      timer = setTimeout(init, Math.min(30000, 3000 * errors));
    }
  });
}

function sessionLost() {
  sid = null;
  sessionReady = false;
  sendBtn.disabled = true;
  store.del(SID_KEY);
  return init();
}

// ---------------------------------------------------------------- polling
function schedule() {
  clearTimeout(timer);
  if (disabled || !hasConv) { return; }
  var visible = isOpen && document.visibilityState === 'visible';
  var delay = visible ? 3000 : 12000;
  if (errors) { delay = Math.min(30000, delay * Math.pow(2, errors)); }
  timer = setTimeout(poll, delay);
}

function poll() {
  clearTimeout(timer);
  if (polling || disabled || !hasConv) { return; }
  polling = true;

  var qs = '?after=' + lastId + (page ? '&page_url=' + encodeURIComponent(page) : '');

  api('GET', '/messages' + qs).then(function (data) {
    errors = 0;
    var incoming = 0;

    data.messages.forEach(function (m) {
      if (addServerMessage(m) && m.from !== 'visitor' && m.from !== 'system') { incoming++; }
    });

    var status = data.conversation ? data.conversation.status : null;
    if (status === 'solved' && convStatus !== 'solved') {
      addRow({ from: 'system', body: S.solved_notice, at: new Date().toISOString() });
    }
    convStatus = status;
    if (status === null || status === 'solved') { hasConv = false; }

    if (incoming > 0) {
      var visible = isOpen && document.visibilityState === 'visible';
      if (!visible) { unread += incoming; toHost({ type: 'unread', count: unread }); }
    }
  }).catch(function (e) {
    if (e.status === 401) { return sessionLost(); }
    if (e.status === 403 || e.status === 404) { setDisabled(true, S.unavailable); return; }
    errors = Math.min(errors + 1, 4); // 429 and network errors back off
  }).then(function () {
    polling = false;
    schedule();
  });
}

// ---------------------------------------------------------------- sending
function send(text, row, retried) {
  if (!row) {
    row = addRow({ from: 'visitor', body: text, at: new Date().toISOString(), state: 'sending' });
  } else {
    setRowState(row, 'sending');
  }

  return api('POST', '/messages', { body: text, page_url: page || null }).then(function (res) {
    var m = res.message;

    if (known[m.id]) {
      row.parentNode && row.parentNode.removeChild(row); // the poll already delivered it
    } else {
      known[m.id] = row;
      setRowState(row, '', m.at);
    }
    if (m.id > lastId) { lastId = m.id; }

    convStatus = res.conversation.status;
    hasConv = true;
    errors = 0;
    schedule();
    maybeAskEmail();
  }).catch(function (e) {
    if (e.status === 401 && !retried) {
      return sessionLost().then(function () { return send(text, row, true); });
    }
    markFailed(row, text, e);
  });
}

function markFailed(row, text, e) {
  setRowState(row, 'failed');
  var meta = row.querySelector('.meta');
  meta.textContent = '';
  var btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'retry';
  btn.textContent = (e && e.status === 422 && e.data && e.data.message) ? e.data.message : S.retry;
  btn.addEventListener('click', function () { send(text, row); });
  meta.appendChild(btn);
}

form.addEventListener('submit', function (ev) {
  ev.preventDefault();
  var text = input.value.trim();
  if (!text || disabled || !sessionReady) { return; }
  input.value = '';
  autosize();
  send(text);
  input.focus();
});

input.addEventListener('keydown', function (e) {
  if (e.key === 'Enter' && !e.shiftKey && !e.isComposing && e.keyCode !== 229) {
    e.preventDefault();
    form.requestSubmit();
  }
});

function autosize() {
  input.style.height = 'auto';
  input.style.height = Math.min(input.scrollHeight, 120) + 'px';
}
input.addEventListener('input', autosize);

// ---------------------------------------------------------------- optional email capture
function maybeAskEmail() {
  if (identified || store.get(DISMISS_KEY) === '1' || !emailPrompt.hidden) { return; }
  emailPrompt.hidden = false;
}

$('emailSkip').addEventListener('click', function () {
  store.set(DISMISS_KEY, '1');
  emailPrompt.hidden = true;
});

emailForm.addEventListener('submit', function (ev) {
  ev.preventDefault();
  emailErr.hidden = true;

  var emailEl = $('emailInput');
  if (!emailEl.value.trim() || !emailEl.checkValidity()) {
    emailErr.textContent = S.email_placeholder;
    emailErr.hidden = false;
    emailEl.focus();
    return;
  }

  $('emailSave').disabled = true;
  api('POST', '/identify', { name: $('nameInput').value.trim() || null, email: emailEl.value.trim() }).then(function () {
    identified = true;
    emailPrompt.hidden = true;
    addRow({ from: 'system', body: S.email_thanks, at: new Date().toISOString() });
  }).catch(function (e) {
    var msg = S.error_generic;
    if (e.status === 422 && e.data && e.data.errors) {
      var first = Object.keys(e.data.errors)[0];
      msg = e.data.errors[first][0] || msg;
    }
    emailErr.textContent = msg;
    emailErr.hidden = false;
  }).then(function () {
    $('emailSave').disabled = false;
  });
});

// ---------------------------------------------------------------- go
resetLog();
toHost({ type: 'ready' });
setTimeout(start, 400); // don't wait forever for the host page's first message
})();
</script>
</body>
</html>
