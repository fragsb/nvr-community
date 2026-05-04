// assets/js/home.js — Homepage dynamic content loader

document.addEventListener('DOMContentLoaded', () => {
  loadNews();
  loadClips();
  loadForumActivity();
  loadOnlinePlayers();

  // Refresh online players every 30s
  setInterval(loadOnlinePlayers, 30000);
});

// ── NEWS ─────────────────────────────────────────────────────
async function loadNews() {
  const container = document.getElementById('newsList');
  if (!container) return;

  try {
    const items = await NVR.api.get('api/news.php', { action: 'list', limit: 3 });
    if (!items.length) {
      container.innerHTML = '<p style="color:var(--muted);font-size:13px;padding:8px 0">Sem notícias recentes.</p>';
      return;
    }
    container.innerHTML = items.map(n => `
      <a class="news-item" href="pages/news.php?slug=${encodeURIComponent(n.slug)}">
        <div class="news-thumb">
          ${n.thumbnail
            ? `<img src="${escHtml(n.thumbnail)}" alt="">`
            : '<span>📰</span>'}
        </div>
        <div>
          <div class="news-title">${escHtml(n.title)}</div>
          <div class="news-desc">${escHtml(n.excerpt || '')}</div>
          <div class="news-time">${NVR.ui.timeAgo(n.published_at)}</div>
        </div>
      </a>
    `).join('');
  } catch {
    // Fallback: show demo content while API is being set up
    container.innerHTML = demosNews();
  }
}

function demosNews() {
  const items = [
    { icon: '⚡', title: 'Patch Notes v2.1', desc: 'Novas atualizações de balance para arenas e comunidades.', time: '2 horas atrás' },
    { icon: '📋', title: 'Patch Notes Iorue',  desc: 'Tournament Signups disponíveis. Acompanha os patch notes.', time: '2 horas atrás' },
    { icon: '🏆', title: 'Weekend Tournament Signups', desc: 'Inscrições abertas para o torneio de fim de semana!', time: '2 horas atrás' },
  ];
  return items.map(n => `
    <div class="news-item">
      <div class="news-thumb"><span>${n.icon}</span></div>
      <div>
        <div class="news-title">${n.title}</div>
        <div class="news-desc">${n.desc}</div>
        <div class="news-time">${n.time}</div>
      </div>
    </div>
  `).join('');
}

// ── CLIPS ────────────────────────────────────────────────────
async function loadClips() {
  const container = document.getElementById('clipsGrid');
  if (!container) return;

  try {
    const clips = await NVR.api.get('api/news.php', { action: 'clips', featured: 1, limit: 6 });
    if (!clips.length) { container.innerHTML = demoClips(); return; }

    container.innerHTML = clips.map(c => `
      <div class="clip-card" onclick="playClip('${escHtml(c.video_url)}', '${escHtml(c.title)}')">
        <div class="clip-thumb">
          ${c.thumbnail ? `<img src="${escHtml(c.thumbnail)}" alt="">` : ''}
          <div class="play-btn"></div>
          <span class="clip-duration">${NVR.ui.formatDuration(c.duration)}</span>
        </div>
        <div class="clip-info">
          <div class="avatar-sm" style="${NVR.ui.avatarStyle(c.username)}">
            ${c.avatar ? `<img src="${escHtml(c.avatar)}" alt="">` : NVR.ui.avatarInitials(c.username)}
          </div>
          <span class="clip-user">${escHtml(c.username)}</span>
        </div>
      </div>
    `).join('');
  } catch {
    container.innerHTML = demoClips();
  }
}

function demoClips() {
  const clips = [
    { user: 'Tocotller', dur: '0:23', bg: '#2a1a1a', color: '#c8a0ff' },
    { user: 'Rlosse',    dur: '0:23', bg: '#1a2a1a', color: '#80ffdd' },
    { user: 'Sod Kncrt', dur: '0:29', bg: '#1a1a2a', color: '#80c0ff' },
    { user: 'Sahlimmer', dur: '0:29', bg: '#2a2a1a', color: '#ffee80' },
    { user: 'Sen Koora', dur: '0:35', bg: '#2a1a2a', color: '#ff80c0' },
    { user: 'Tonciller', dur: '0:25', bg: '#1a2a2a', color: '#80ffee' },
  ];
  return clips.map(c => `
    <div class="clip-card">
      <div class="clip-thumb">
        <div style="position:absolute;inset:0;background:linear-gradient(135deg,${c.bg},#0e1015);opacity:.6"></div>
        <div class="play-btn"></div>
        <span class="clip-duration">${c.dur}</span>
      </div>
      <div class="clip-info">
        <div class="avatar-sm" style="background:${c.bg};color:${c.color}">${c.user[0]}</div>
        <span class="clip-user">${c.user}</span>
      </div>
    </div>
  `).join('');
}

// ── FORUM ACTIVITY ───────────────────────────────────────────
async function loadForumActivity() {
  const container = document.getElementById('forumActivity');
  if (!container) return;

  try {
    const posts = await NVR.api.get('api/forum.php', { action: 'recent' });
    if (!posts.length) { container.innerHTML = demoForum(); return; }

    container.innerHTML = posts.map(p => `
      <a class="forum-item" href="pages/forum.php?post=${encodeURIComponent(p.slug)}">
        <div class="forum-avatar" style="${NVR.ui.avatarStyle(p.author)}">
          ${p.author_avatar
            ? `<img src="${escHtml(p.author_avatar)}" alt="">`
            : NVR.ui.avatarInitials(p.author)}
        </div>
        <div class="forum-body">
          <div class="forum-user">${escHtml(p.author)}</div>
          <div class="forum-title">${escHtml(p.title)}</div>
          <div class="forum-meta">${p.reply_count} resposta${p.reply_count !== 1 ? 's' : ''} · ${NVR.ui.timeAgo(p.created_at)}</div>
        </div>
        <div class="forum-replies">${formatCount(p.reply_count)}</div>
      </a>
    `).join('');
  } catch {
    container.innerHTML = demoForum();
  }
}

function demoForum() {
  const posts = [
    { user: 'Real Salnoa',       init: 'RS', bg: '#2a1a1a', color: '#ff8080', title: 'What are wishing ation you continue?',     meta: '1 resposta · 5 meses atrás',  cnt: '13k' },
    { user: 'DosiriSamectoo',    init: 'DS', bg: '#1a1a2a', color: '#80a0ff', title: 'Tualleo lere teshor wv mam the foten',      meta: '2 respostas · 5 meses atrás', cnt: '10k' },
    { user: 'Nardos Inteh',      init: 'NI', bg: '#1a2a1a', color: '#80ff90', title: 'What are still posts into our community?',  meta: '2 respostas · 3 meses atrás', cnt: '11k' },
  ];
  return posts.map(p => `
    <div class="forum-item">
      <div class="forum-avatar" style="background:${p.bg};color:${p.color}">${p.init}</div>
      <div class="forum-body">
        <div class="forum-user">${p.user}</div>
        <div class="forum-title">${p.title}</div>
        <div class="forum-meta">${p.meta}</div>
      </div>
      <div class="forum-replies">${p.cnt}</div>
    </div>
  `).join('');
}

// ── ONLINE PLAYERS ───────────────────────────────────────────
async function loadOnlinePlayers() {
  const container = document.getElementById('onlineList');
  const countEl   = document.getElementById('onlineCount');
  if (!container) return;

  try {
    const players = await NVR.api.get('api/players.php', { action: 'online' });
    if (countEl) countEl.textContent = players.length;

    if (!players.length) { container.innerHTML = demoOnline(); return; }

    container.innerHTML = players.slice(0, 6).map(p => `
      <div class="online-item" onclick="NVR.ui.goto('pages/profile.php?username=${encodeURIComponent(p.username)}')">
        <div class="online-avatar" style="${NVR.ui.avatarStyle(p.username)}">
          ${p.avatar
            ? `<img src="${escHtml(p.avatar)}" alt="">`
            : NVR.ui.avatarInitials(p.username)}
          <div class="online-dot"></div>
        </div>
        <div class="online-info">
          <div class="online-name">${escHtml(p.username)}</div>
          <div class="online-game">${p.current_game ? 'In Game: ' + escHtml(p.current_game) : 'Online'}</div>
        </div>
        <button class="add-btn" onclick="event.stopPropagation();addFriend(${p.id})" title="Adicionar amigo">+</button>
      </div>
    `).join('');
  } catch {
    if (countEl) countEl.textContent = '4';
    container.innerHTML = demoOnline();
  }
}

function demoOnline() {
  const players = [
    { n: 'Jmah',     g: 'Cyberpunk', bg: '#2a1a2a', c: '#d090ff' },
    { n: 'Kimly',    g: 'Cyberpunk', bg: '#1a2a1a', c: '#70dd80' },
    { n: 'Shartiien',g: 'Cyberpunk', bg: '#1a1a2a', c: '#70a0ff' },
    { n: 'Amrian',   g: 'Cyberpunk', bg: '#2a1a1a', c: '#ff9070' },
  ];
  return players.map(p => `
    <div class="online-item">
      <div class="online-avatar" style="background:${p.bg};color:${p.c}">
        ${p.n.slice(0,2).toUpperCase()}<div class="online-dot"></div>
      </div>
      <div class="online-info">
        <div class="online-name">${p.n}</div>
        <div class="online-game">In Game: ${p.g}</div>
      </div>
      <button class="add-btn">+</button>
    </div>
  `).join('');
}

// ── ACTIONS ──────────────────────────────────────────────────
async function addFriend(playerId) {
  if (!NVR.auth.player) {
    NVR.ui.openModal('loginModal');
    NVR.ui.toast('Inicia sessão para adicionar amigos', 'error');
    return;
  }
  try {
    await NVR.api.post('api/players.php', { action: 'add_friend' }, { player_id: playerId });
    NVR.ui.toast('Pedido de amizade enviado!', 'success');
  } catch (e) {
    NVR.ui.toast(e.message, 'error');
  }
}

function playClip(url, title) {
  // Simple video modal — extend as needed
  window.open(url, '_blank', 'noopener');
}

// ── UTILS ─────────────────────────────────────────────────────
function escHtml(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatCount(n) {
  n = parseInt(n) || 0;
  if (n >= 1000) return (n / 1000).toFixed(1) + 'k';
  return String(n);
}
