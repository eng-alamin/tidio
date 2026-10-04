/* =========================================================
   Loop Super Admin — Livewire-safe shell behaviour
   (theme, sidebar, dropdowns, toasts). Everything is event-delegated
   so it keeps working after Livewire morphs the DOM.
   Replaces the static-demo app.js, which renders fake in-memory data.
   ========================================================= */
(function () {
  'use strict';

  var root = document.documentElement;

  /* ---------- Theme ---------- */
  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    var icon = document.getElementById('themeIcon');
    if (icon) icon.className = theme === 'light' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
    var btn = document.getElementById('themeToggleBtn');
    if (btn) btn.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
    try { localStorage.setItem('loop-theme', theme); } catch (e) {}
  }
  applyTheme(root.getAttribute('data-theme') || 'dark');

  /* ---------- Toasts (textContent only, never innerHTML) ---------- */
  function removeToast(el) {
    clearTimeout(el._timer);
    el.style.transition = '.2s';
    el.style.opacity = '0';
    el.style.transform = 'translateY(6px)';
    setTimeout(function () { el.remove(); }, 200);
  }

  window.toast = function (msg, type) {
    type = type || 'ok';
    var wrap = document.getElementById('toastWrap');
    if (!wrap) return;
    var icons = { ok: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warn: 'bi-exclamation-triangle-fill' };

    var el = document.createElement('div');
    el.className = 'toast-item glass ' + type;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');

    var icon = document.createElement('i');
    icon.className = 'bi ' + (icons[type] || icons.warn) + ' ti-icon';
    var text = document.createElement('span');
    text.textContent = msg;
    var close = document.createElement('button');
    close.className = 'ti-close';
    close.type = 'button';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = '<i class="bi bi-x"></i>';
    close.addEventListener('click', function () { removeToast(el); });

    el.appendChild(icon);
    el.appendChild(text);
    el.appendChild(close);
    wrap.appendChild(el);
    el._timer = setTimeout(function () { removeToast(el); }, 4000);
  };

  document.addEventListener('livewire:init', function () {
    Livewire.on('toast', function (payload) {
      var data = Array.isArray(payload) ? payload[0] : payload;
      window.toast((data && data.message) || '', (data && data.type) || 'ok');
    });
  });

  /* ---------- Sidebar (desktop collapse + mobile drawer) ---------- */
  function shell() { return document.querySelector('.app-shell'); }
  function isMobile() { return window.matchMedia('(max-width:900px)').matches; }

  function setCollapsed(on) {
    var s = shell();
    if (!s) return;
    s.classList.toggle('sidebar-collapsed', on);
    try { localStorage.setItem('loop-sidebar-collapsed', on ? '1' : '0'); } catch (e) {}
  }

  function wireSidebarLabels() {
    document.querySelectorAll('.sidebar .nav-item, .sidebar .brand').forEach(function (el) {
      Array.prototype.slice.call(el.childNodes).forEach(function (node) {
        if (node.nodeType === 3 && node.textContent.trim()) {
          var span = document.createElement('span');
          span.className = 'nav-label';
          span.textContent = node.textContent;
          el.replaceChild(span, node);
          if (el.classList.contains('nav-item') && !el.title) el.title = span.textContent.trim();
        }
      });
    });
  }

  function closeDrawer() {
    var sb = document.getElementById('sidebar');
    var scrim = document.getElementById('sidebarScrim');
    var burger = document.getElementById('hamburgerBtn');
    if (sb) sb.classList.remove('open');
    if (scrim) scrim.classList.remove('open');
    if (burger) burger.setAttribute('aria-expanded', 'false');
  }

  function closeDropdowns() {
    document.querySelectorAll('.dropdown-panel.open').forEach(function (p) { p.classList.remove('open'); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    wireSidebarLabels();
    try {
      if (localStorage.getItem('loop-sidebar-collapsed') === '1') setCollapsed(true);
    } catch (e) {}
  });

  /* ---------- Delegated clicks ---------- */
  document.addEventListener('click', function (e) {
    var t = e.target;

    if (t.closest('#themeToggleBtn')) {
      applyTheme(root.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
      return;
    }

    if (t.closest('#hamburgerBtn')) {
      var sb = document.getElementById('sidebar');
      var scrim = document.getElementById('sidebarScrim');
      var burger = document.getElementById('hamburgerBtn');
      if (isMobile()) {
        var open = !sb.classList.contains('open');
        sb.classList.toggle('open', open);
        scrim.classList.toggle('open', open);
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
      } else {
        setCollapsed(!shell().classList.contains('sidebar-collapsed'));
      }
      return;
    }

    if (t.closest('#sidebarScrim') || t.closest('.sidebar a')) closeDrawer();

    var toastLink = t.closest('[data-toast]');
    if (toastLink) {
      e.preventDefault();
      window.toast(toastLink.getAttribute('data-toast'), 'warn');
      return;
    }

    var chip = t.closest('#avatarBtn');
    if (chip) {
      e.stopPropagation();
      var panel = document.getElementById('avatarPanel');
      var willOpen = !panel.classList.contains('open');
      closeDropdowns();
      panel.classList.toggle('open', willOpen);
      return;
    }

    if (!t.closest('.dropdown-panel')) closeDropdowns();
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeDropdowns();
      closeDrawer();
    }
  });
})();
