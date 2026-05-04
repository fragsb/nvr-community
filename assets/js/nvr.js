// assets/js/nvr.js — Core NVR Hub JS library

const NVR = (() => {
  // ── API ────────────────────────────────────────────────────
  const api = {
    async call(endpoint, params = {}, options = {}) {
      const query = new URLSearchParams(params).toString();
      const url   = `${endpoint}${query ? '?' + query : ''}`;
      const res   = await fetch(url, {
        method:  options.method || 'GET',
        headers: { 'Content-Type': 'application/json', ...options.headers },
        body:    options.body ? JSON.stringify(options.body) : undefined,
        credentials: 'same-origin',
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Erro desconhecido');
      return data;
    },

    get:  (ep, p)    => api.call(ep, p),
    post: (ep, p, b) => api.call(ep, p, { method: 'POST', body: b }),
  };

  // ── AUTH ───────────────────────────────────────────────────
  const auth = {
    player: null,

    async init() {
      try {
        const data = await api.get('api/auth.php', { action: 'me' });
        if (data.authenticated) {
          auth.player = data.player;
          auth.updateUI();
        }
      } catch {}
    },

    async login() {
      const login    = document.getElementById('loginField')?.value.trim();
      const password = document.getElementById('loginPass')?.value;
      const errEl    = document.getElementById('loginError');

      if (!login || !password) {
        return showFormError(errEl, 'Preenche todos os campos');
      }
      try {
        const data = await api.post('api/auth.php', { action: 'login' }, { login, password });
        auth.player = data.player;
        auth.updateUI();
        ui.closeModal('loginModal');
        ui.toast('Bem-vindo, ' + data.player.username + '!', 'success');
        setTimeout(() => location.reload(), 800);
      } catch (e) {
        showFormError(errEl, e.message);
      }
    },

    async register() {
      const username = document.getElementById('regUsername')?.value.trim();
      const email    = document.getElementById('regEmail')?.value.trim();
      const password = document.getElementById('regPass')?.value;
      const errEl    = document.getElementById('regError');

      if (!username || !email || !password) {
        return showFormError(errEl, 'Preenche todos os campos');
      }
      try {
        const data = await api.post('api/auth.php', { action: 'register' }, { username, email, password });
        auth.player = data.player;
        auth.updateUI();
        ui.closeModal('loginModal');
        ui.toast('Conta criada com sucesso! Bem-vindo, ' + data.player.username, 'success');
        setTimeout(() => location.reload(), 1000);
      } catch (e) {
        showFormError(errEl, e.message);
      }
    },

    async logout() {
      try {
        await api.post('api/auth.php', { action: 'logout' }, {});
      } catch {}
      auth.player = null;
      auth.updateUI();
      location.reload();
    },

    updateUI() {
      const authArea  = document.getElementById('authArea');
      const userMenu  = document.getElementById('userMenu');
      const username  = document.getElementById('userUsername');

      if (auth.player) {
        if (authArea)  authArea.classList.add('hidden');
        if (userMenu)  userMenu.classList.remove('hidden');
        if (username)  username.textContent = auth.player.username;
      } else {
        if (authArea)  authArea.classList.remove('hidden');
        if (userMenu)  userMenu.classList.add('hidden');
      }
    },
  };

  // ── UI HELPERS ─────────────────────────────────────────────
  const ui = {
    openModal(id) {
      const el = document.getElementById(id);
      if (el) el.classList.remove('hidden');
    },
    closeModal(id) {
      const el = document.getElementById(id);
      if (el) el.classList.add('hidden');
    },
    toast(msg, type = 'success') {
      const el = document.getElementById('toast');
      if (!el) return;
      el.textContent = msg;
      el.className   = `toast ${type}`;
      el.classList.remove('hidden');
      setTimeout(() => el.classList.add('hidden'), 3500);
    },
    goto(url) { location.href = url; },

    rankBadge(rank) {
      return `<span class="rank-badge rank-${rank}">${rank}</span>`;
    },

    timeAgo(dateStr) {
      const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
      if (diff < 60)   return 'agora mesmo';
      if (diff < 3600) return `${Math.floor(diff/60)} min atrás`;
      if (diff < 86400) return `${Math.floor(diff/3600)}h atrás`;
      if (diff < 604800) return `${Math.floor(diff/86400)}d atrás`;
      return new Date(dateStr).toLocaleDateString('pt-PT');
    },

    formatDuration(secs) {
      const m = Math.floor(secs / 60);
      const s = secs % 60;
      return `${m}:${String(s).padStart(2,'0')}`;
    },

    avatarInitials(name = '?') {
      return name.slice(0, 2).toUpperCase();
    },

    avatarColors: [
      ['#2a1a2a','#c8a0ff'],['#1a2a2a','#80ffdd'],['#1a1a2a','#80c0ff'],
      ['#2a2a1a','#ffee80'],['#2a1a1a','#ff8080'],['#1a2a1a','#80ff90'],
    ],

    avatarStyle(username) {
      const idx = (username.charCodeAt(0) || 0) % NVR.ui.avatarColors.length;
      const [bg, color] = NVR.ui.avatarColors[idx];
      return `background:${bg};color:${color}`;
    },
  };

  // ── MODAL TABS ─────────────────────────────────────────────
  function initTabs() {
    document.querySelectorAll('.modal-tabs').forEach(tabs => {
      tabs.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const tabId = btn.dataset.tab;
          // Update buttons
          tabs.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          // Show/hide tab content
          const modal = tabs.closest('.modal');
          modal.querySelectorAll('.tab-content').forEach(t => {
            t.classList.toggle('hidden', t.id !== tabId);
          });
        });
      });
    });
  }

  // ── CLOSE MODAL ON BACKDROP ────────────────────────────────
  function initModalClose() {
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
      backdrop.addEventListener('click', e => {
        if (e.target === backdrop) backdrop.classList.add('hidden');
      });
    });
  }

  // ── ENTER KEY IN FORMS ─────────────────────────────────────
  function initEnterKey() {
    document.getElementById('loginPass')?.addEventListener('keydown', e => {
      if (e.key === 'Enter') auth.login();
    });
    document.getElementById('regPass')?.addEventListener('keydown', e => {
      if (e.key === 'Enter') auth.register();
    });
  }

  // ── FORM ERROR ─────────────────────────────────────────────
  function showFormError(el, msg) {
    if (!el) return;
    el.textContent = msg;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 5000);
  }

  // ── INIT ───────────────────────────────────────────────────
  function init() {
    initTabs();
    initModalClose();
    initEnterKey();
    auth.init();
  }

  document.addEventListener('DOMContentLoaded', init);

  // Public
  return { api, auth, ui };
})();
