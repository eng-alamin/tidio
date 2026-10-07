/*!
 * loop-realtime.js — a tiny, dependency-free Pusher-protocol client for Laravel Reverb.
 * Used by the operator panel and by the chat widget frame (no Echo / pusher-js / Vite needed).
 *
 *   var rt = LoopRealtime.connect({
 *     key: 'app-key', host: 'localhost', port: 8080, scheme: 'http',
 *     authEndpoint: '/broadcasting/auth',          // POST socket_id + channel_name -> { auth }
 *     authHeaders: function () { return {...}; },   // optional (e.g. X-Widget-Session / X-CSRF-TOKEN)
 *     credentials: 'same-origin',                   // fetch credentials for the auth call
 *     onState: function (connected) {}              // true when subscribed-capable, false when down
 *   });
 *   rt.subscribe('private-workspace.1', { 'conversation.signal': function (data) {} });
 *   rt.unsubscribe('private-workspace.1');
 *   rt.close();
 *
 * It reconnects with back-off, re-subscribes after every reconnect, answers server pings and
 * drops dead connections. It never throws into the host page.
 */
(function (root) {
  'use strict';

  function connect(opts) {
    var wsScheme = (opts.scheme === 'https' || opts.scheme === 'wss') ? 'wss' : 'ws';
    var url = wsScheme + '://' + opts.host + (opts.port ? ':' + opts.port : '') +
      '/app/' + encodeURIComponent(opts.key) + '?protocol=7&client=loop-rt&version=1.0&flash=false';

    var ws = null;
    var socketId = null;
    var closed = false;
    var connected = false;
    var attempts = 0;
    var retryTimer = null;
    var watchdog = null;
    var lastActivity = 0;
    var activityTimeout = 30000;
    var channels = {}; // name -> { handlers: {event: fn}, joined: bool }

    function setConnected(value) {
      if (connected === value) { return; }
      connected = value;
      try { if (opts.onState) { opts.onState(value); } } catch (e) {}
    }

    function send(obj) {
      try { if (ws && ws.readyState === 1) { ws.send(JSON.stringify(obj)); return true; } } catch (e) {}
      return false;
    }

    function stopWatchdog() { if (watchdog) { clearInterval(watchdog); watchdog = null; } }

    function startWatchdog() {
      stopWatchdog();
      lastActivity = Date.now();
      watchdog = setInterval(function () {
        var idle = Date.now() - lastActivity;
        if (idle > activityTimeout * 2) {
          try { ws.close(); } catch (e) {}      // dead connection -> onclose -> reconnect
        } else if (idle > activityTimeout) {
          send({ event: 'pusher:ping', data: {} });
        }
      }, Math.max(5000, Math.floor(activityTimeout / 2)));
    }

    function retry() {
      if (closed) { return; }
      var delay = Math.min(30000, 1000 * Math.pow(2, attempts)) + Math.floor(Math.random() * 500);
      attempts = Math.min(attempts + 1, 8);
      clearTimeout(retryTimer);
      retryTimer = setTimeout(open, delay);
    }

    function joinChannel(name) {
      var ch = channels[name];
      if (!ch || ch.joined || !socketId) { return; }

      if (name.indexOf('private-') !== 0 && name.indexOf('presence-') !== 0) {
        ch.joined = true;
        send({ event: 'pusher:subscribe', data: { channel: name } });
        return;
      }

      var headers = { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' };
      try {
        var extra = opts.authHeaders ? opts.authHeaders() : {};
        for (var k in extra) { if (Object.prototype.hasOwnProperty.call(extra, k)) { headers[k] = extra[k]; } }
      } catch (e) {}

      var sid = socketId;
      ch.joined = true; // block duplicate auth calls while this one is in flight

      fetch(opts.authEndpoint, {
        method: 'POST',
        headers: headers,
        credentials: opts.credentials || 'same-origin',
        cache: 'no-store',
        body: 'socket_id=' + encodeURIComponent(sid) + '&channel_name=' + encodeURIComponent(name)
      }).then(function (res) {
        if (!res.ok) { throw new Error('auth ' + res.status); }
        return res.json();
      }).then(function (data) {
        if (!channels[name] || sid !== socketId) { ch.joined = false; return; } // gone or reconnected meanwhile
        send({ event: 'pusher:subscribe', data: { auth: data.auth, channel: name, channel_data: data.channel_data } });
      }).catch(function () {
        ch.joined = false; // retried on the next (re)connect
      });
    }

    function joinAll() { for (var name in channels) { if (Object.prototype.hasOwnProperty.call(channels, name)) { joinChannel(name); } } }

    function handle(msg) {
      lastActivity = Date.now();
      var event = msg.event;

      if (event === 'pusher:connection_established') {
        var info = {};
        try { info = typeof msg.data === 'string' ? JSON.parse(msg.data) : (msg.data || {}); } catch (e) {}
        socketId = info.socket_id || null;
        if (info.activity_timeout) { activityTimeout = Math.max(10, info.activity_timeout) * 1000; }
        attempts = 0;
        startWatchdog();
        for (var n in channels) { if (Object.prototype.hasOwnProperty.call(channels, n)) { channels[n].joined = false; } }
        setConnected(true);
        joinAll();
        return;
      }

      if (event === 'pusher:ping') { send({ event: 'pusher:pong', data: {} }); return; }
      if (event === 'pusher:pong' || event === 'pusher_internal:subscription_succeeded') { return; }

      if (event === 'pusher:error') {
        var err = {};
        try { err = typeof msg.data === 'string' ? JSON.parse(msg.data) : (msg.data || {}); } catch (e) {}
        // 4000-4099: the app/key is wrong — retrying can't fix it.
        if (err.code >= 4000 && err.code < 4100) { closed = true; try { ws.close(); } catch (e) {} }
        return;
      }

      var ch = msg.channel ? channels[msg.channel] : null;
      if (!ch || !ch.handlers[event]) { return; }

      var data = msg.data;
      if (typeof data === 'string') { try { data = JSON.parse(data); } catch (e) {} }
      try { ch.handlers[event](data); } catch (e) {}
    }

    function open() {
      if (closed) { return; }
      try { ws = new WebSocket(url); } catch (e) { retry(); return; }

      ws.onmessage = function (ev) {
        var msg;
        try { msg = JSON.parse(ev.data); } catch (e) { return; }
        handle(msg);
      };
      ws.onclose = function () {
        stopWatchdog();
        socketId = null;
        setConnected(false);
        retry();
      };
      ws.onerror = function () { try { ws.close(); } catch (e) {} };
    }

    open();

    return {
      subscribe: function (name, handlers) {
        channels[name] = { handlers: handlers || {}, joined: false };
        joinChannel(name);
      },
      unsubscribe: function (name) {
        if (channels[name] && channels[name].joined) { send({ event: 'pusher:unsubscribe', data: { channel: name } }); }
        delete channels[name];
      },
      isConnected: function () { return connected; },
      close: function () {
        closed = true;
        clearTimeout(retryTimer);
        stopWatchdog();
        try { if (ws) { ws.close(); } } catch (e) {}
      }
    };
  }

  root.LoopRealtime = { connect: connect };
})(window);
