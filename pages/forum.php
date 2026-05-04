<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

$player = currentPlayer();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forum — NVR Community Hub</title>
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Barlow:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/main.css">
<style>
  .post-form { background: var(--bg3); border-radius: var(--radius); padding: 20px; margin-bottom: 20px; border: 1px solid var(--border); }
  .post-form textarea { width:100%; background:var(--bg4); border:1px solid var(--border); border-radius:5px; padding:10px 12px; color:var(--text); font-family:var(--font); font-size:14px; resize:vertical; min-height:100px; outline:none; }
  .post-form textarea:focus { border-color: var(--acc); }
  .post-row { display:flex; gap:12px; margin-bottom:10px; }
  .post-row select { background:var(--bg4); border:1px solid var(--border); border-radius:5px; padding:9px 12px; color:var(--text); font-family:var(--font); font-size:14px; outline:none; cursor:pointer; }
  .post-row input { flex:1; background:var(--bg4); border:1px solid var(--border); border-radius:5px; padding:9px 12px; color:var(--text); font-family:var(--font); font-size:14px; outline:none; }
  .post-row input:focus, .post-row select:focus { border-color:var(--acc); }
  .post-row .btn-primary { flex-shrink:0; }
  .posts-list {}
  .post-card { background:var(--bg2); border:1px solid var(--border); border-radius:var(--radius); padding:16px; margin-bottom:10px; cursor:pointer; transition:border-color .2s; }
  .post-card:hover { border-color:rgba(200,255,0,.3); }
  .post-card.pinned { border-left:3px solid var(--acc); }
  .post-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:8px; }
  .post-meta  { font-size:11px; color:var(--muted); display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
  .post-title { font-family:var(--font-h); font-weight:700; font-size:16px; margin-bottom:4px; color:var(--text); }
  .post-title:hover { color:var(--acc2); }
  .pin-icon  { font-size:12px; color:var(--acc); }
  .lock-icon { font-size:12px; color:var(--muted); }
  .cat-filter { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
  .cat-pill { padding:5px 14px; border-radius:20px; border:1px solid var(--border); background:var(--bg3); color:var(--muted); font-size:12px; cursor:pointer; transition:all .2s; font-weight:500; }
  .cat-pill.active, .cat-pill:hover { background:rgba(200,255,0,.12); border-color:var(--acc); color:var(--acc); }
  .pagination { display:flex; gap:6px; justify-content:center; margin-top:18px; }
  .page-btn { padding:6px 12px; border-radius:4px; border:1px solid var(--border); background:var(--bg3); color:var(--muted); cursor:pointer; font-size:12px; transition:all .2s; }
  .page-btn.active, .page-btn:hover { background:var(--acc); color:#0e1015; border-color:var(--acc); font-weight:700; }
  .no-posts { text-align:center; padding:40px; color:var(--muted); font-size:14px; }
</style>
</head>
<body>
<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

$player = currentPlayer();
?>

<nav id="navbar">
  <div class="nav-inner">
    <div class="logo"><a href="../index.php" style="color:inherit;text-decoration:none">NVR – NEVER CM <span>| COMMUNITY HUB</span></a></div>
    <ul class="nav-links">
      <li><a href="../index.php">Home</a></li>
      <li><a href="forum.php" class="active">Forum</a></li>
      <li><a href="rankings.php">Rankings</a></li>
      <li><a href="tournaments.php">Tournaments</a></li>
    </ul>
    <div class="nav-right">
      <?php if ($player): ?>
        <div class="user-menu">
          <span class="nav-username"><?= htmlspecialchars($player['username']) ?></span>
          <div class="user-dropdown">
            <a href="profile.php">Perfil</a>
            <a href="../api/auth.php?action=logout" onclick="return confirm('Sair?')">Sair</a>
          </div>
        </div>
      <?php else: ?>
        <button class="btn-primary" onclick="NVR.ui.openModal('loginModal')">Login</button>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="forum-page">

  <h1 style="font-family:var(--font-h);font-size:22px;font-weight:700;margin-bottom:18px;letter-spacing:2px">
    FORUM <span style="color:var(--acc)">_</span>
  </h1>

  <!-- Category filter -->
  <div class="cat-filter" id="catFilter">
    <div class="cat-pill active" data-cat="0">Todos</div>
  </div>

  <!-- New post form (auth only) -->
  <?php if ($player): ?>
  <div class="post-form">
    <h3 style="font-family:var(--font-h);font-size:14px;letter-spacing:1.5px;text-transform:uppercase;margin-bottom:14px;color:var(--muted)">Criar Post</h3>
    <div class="post-row">
      <input type="text" id="postTitle" placeholder="Título do post..." maxlength="200">
      <select id="postCategory"><option value="">Categoria...</option></select>
    </div>
    <textarea id="postBody" placeholder="O que queres partilhar com a comunidade?"></textarea>
    <div style="display:flex;justify-content:flex-end;margin-top:10px">
      <button class="btn-primary" onclick="submitPost()">Publicar Post</button>
    </div>
    <div id="postError" class="form-error hidden" style="margin-top:10px"></div>
  </div>
  <?php endif; ?>

  <!-- Posts list -->
  <div id="postsList">
    <div class="skeleton-list">
      <div class="skeleton-item" style="height:80px"></div>
      <div class="skeleton-item" style="height:80px"></div>
      <div class="skeleton-item" style="height:80px"></div>
    </div>
  </div>
  <div class="pagination" id="pagination"></div>
</div>

<!-- MODALS -->
<div id="loginModal" class="modal-backdrop hidden">
  <div class="modal">
    <button class="modal-close" onclick="NVR.ui.closeModal('loginModal')">&times;</button>
    <div class="modal-tabs">
      <button class="tab-btn active" data-tab="loginTab">Entrar</button>
      <button class="tab-btn" data-tab="registerTab">Registar</button>
    </div>
    <div id="loginTab" class="tab-content">
      <h3 class="modal-title">Bem-vindo de volta</h3>
      <div class="form-group"><label>Username ou Email</label><input type="text" id="loginField" placeholder="username ou email"></div>
      <div class="form-group"><label>Password</label><input type="password" id="loginPass" placeholder="••••••••"></div>
      <div id="loginError" class="form-error hidden"></div>
      <button class="btn-primary full" onclick="NVR.auth.login()">Entrar</button>
    </div>
    <div id="registerTab" class="tab-content hidden">
      <h3 class="modal-title">Cria a tua conta</h3>
      <div class="form-group"><label>Username</label><input type="text" id="regUsername"></div>
      <div class="form-group"><label>Email</label><input type="email" id="regEmail"></div>
      <div class="form-group"><label>Password</label><input type="password" id="regPass"></div>
      <div id="regError" class="form-error hidden"></div>
      <button class="btn-primary full" onclick="NVR.auth.register()">Criar Conta</button>
    </div>
  </div>
</div>

<div id="toast" class="toast hidden"></div>

<script src="../assets/js/nvr.js"></script>
<script>
let currentCat = 0;
let currentPage = 1;

// Load categories
async function loadCategories() {
  try {
    const cats = await NVR.api.get('../api/forum.php', { action: 'categories' });
    const filter = document.getElementById('catFilter');
    const select = document.getElementById('postCategory');

    cats.forEach(c => {
      const pill = document.createElement('div');
      pill.className = 'cat-pill';
      pill.dataset.cat = c.id;
      pill.textContent = `${c.icon || ''} ${c.name}`;
      pill.addEventListener('click', () => {
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        pill.classList.add('active');
        currentCat = parseInt(c.id);
        currentPage = 1;
        loadPosts();
      });
      filter.appendChild(pill);

      if (select) {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.icon || ''} ${c.name}`;
        select.appendChild(opt);
      }
    });
  } catch {}
}

// Load posts
async function loadPosts() {
  const container = document.getElementById('postsList');
  const pagEl     = document.getElementById('pagination');
  container.innerHTML = '<div class="skeleton-list"><div class="skeleton-item" style="height:80px"></div><div class="skeleton-item" style="height:80px"></div></div>';

  try {
    const params = { action: 'posts', page: currentPage };
    if (currentCat) params.category_id = currentCat;

    const data = await NVR.api.get('../api/forum.php', params);
    if (!data.posts.length) {
      container.innerHTML = '<div class="no-posts">Sem posts nesta categoria ainda. Sê o primeiro! 🎮</div>';
      if (pagEl) pagEl.innerHTML = '';
      return;
    }

    container.innerHTML = data.posts.map(p => `
      <div class="post-card ${p.is_pinned ? 'pinned' : ''}" onclick="viewPost('${esc(p.slug)}')">
        <div class="post-header">
          <div style="flex:1">
            <div class="post-title">
              ${p.is_pinned ? '<span class="pin-icon">📌 </span>' : ''}
              ${p.is_locked ? '<span class="lock-icon">🔒 </span>' : ''}
              ${esc(p.title)}
            </div>
            <div class="post-meta">
              <span>${NVR.ui.rankBadge(p.author_rank)}</span>
              <span><strong>${esc(p.author)}</strong></span>
              <span>${esc(p.category_name)}</span>
              <span>${NVR.ui.timeAgo(p.created_at)}</span>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:13px;color:var(--muted)">💬 ${p.reply_count}</div>
            <div style="font-size:11px;color:rgba(122,128,149,.5);margin-top:2px">👁 ${p.view_count}</div>
          </div>
        </div>
      </div>
    `).join('');

    // Pagination
    if (pagEl && data.pages > 1) {
      pagEl.innerHTML = Array.from({ length: data.pages }, (_, i) => i + 1)
        .map(n => `<div class="page-btn ${n === currentPage ? 'active' : ''}" onclick="goPage(${n})">${n}</div>`)
        .join('');
    } else if (pagEl) pagEl.innerHTML = '';
  } catch (e) {
    container.innerHTML = `<div class="no-posts" style="color:#ff6b6b">Erro ao carregar posts: ${esc(e.message)}</div>`;
  }
}

function goPage(n) {
  currentPage = n;
  loadPosts();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function viewPost(slug) {
  location.href = 'post.php?slug=' + encodeURIComponent(slug);
}

async function submitPost() {
  const title      = document.getElementById('postTitle')?.value.trim();
  const body       = document.getElementById('postBody')?.value.trim();
  const categoryId = document.getElementById('postCategory')?.value;
  const errEl      = document.getElementById('postError');

  if (!title || !body || !categoryId) {
    errEl.textContent = 'Preenche todos os campos';
    errEl.classList.remove('hidden');
    return;
  }
  try {
    const data = await NVR.api.post('../api/forum.php', { action: 'create_post' }, { title, body, category_id: parseInt(categoryId) });
    NVR.ui.toast('Post criado!', 'success');
    document.getElementById('postTitle').value = '';
    document.getElementById('postBody').value  = '';
    errEl.classList.add('hidden');
    loadPosts();
  } catch (e) {
    errEl.textContent = e.message;
    errEl.classList.remove('hidden');
  }
}

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
  loadCategories();
  loadPosts();
});
</script>
</body>
</html>
