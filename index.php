<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NVR – Never CM | Community Hub</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=Barlow:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<!-- ═══ NAV ═══════════════════════════════════════════════════ -->
<nav id="navbar">
  <div class="nav-inner">
    <div class="logo">NVR – NEVER CM <span>| COMMUNITY HUB</span></div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php" class="active">Home</a></li>
      <li><a href="pages/forum.php">Forum</a></li>
      <li><a href="pages/rankings.php">Rankings</a></li>
      <li><a href="pages/tournaments.php">Tournaments</a></li>
      <li><a href="pages/about.php">About</a></li>
    </ul>
    <div class="nav-right">
      <div id="authArea">
        <button class="btn-primary" onclick="NVR.ui.openModal('loginModal')">Login / Sign Up</button>
      </div>
      <div id="userMenu" class="user-menu hidden">
        <img id="userAvatar" src="assets/img/default-avatar.png" class="nav-avatar" alt="">
        <span id="userUsername" class="nav-username"></span>
        <div class="user-dropdown">
          <a href="pages/profile.php">Perfil</a>
          <a href="pages/settings.php">Definições</a>
          <a href="#" onclick="NVR.auth.logout()">Sair</a>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- ═══ MAIN ══════════════════════════════════════════════════ -->
<main class="hub-main">
  <div class="hub-grid">

    <!-- LEFT COLUMN -->
    <div class="col-left">

      <!-- NEWS -->
      <section class="card section-gap">
        <div class="sec-header">
          <h2 class="sec-title">Latest Community News</h2>
          <a href="pages/news.php" class="btn-outline">See All</a>
        </div>
        <div id="newsList">
          <div class="skeleton-list">
            <div class="skeleton-item"></div>
            <div class="skeleton-item"></div>
            <div class="skeleton-item"></div>
          </div>
        </div>
      </section>

      <!-- CLIPS -->
      <section class="card">
        <div class="sec-header">
          <h2 class="sec-title">Featured Clips</h2>
          <a href="pages/clips.php" class="btn-outline">See All</a>
        </div>
        <div id="clipsGrid" class="clips-grid"></div>
      </section>

    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-right">

      <!-- FORUM -->
      <section class="card section-gap">
        <div class="sec-header">
          <h2 class="sec-title">Recent Forum Activity</h2>
        </div>
        <div id="forumActivity"></div>
        <div class="show-more" onclick="NVR.ui.goto('pages/forum.php')">Show more posts</div>
      </section>

      <!-- ONLINE -->
      <section class="card">
        <div class="sec-header">
          <h2 class="sec-title">Community Members Online</h2>
          <span id="onlineCount" class="badge-online">0</span>
        </div>
        <div id="onlineList"></div>
      </section>

    </div>
  </div>
</main>

<!-- ═══ FOOTER ════════════════════════════════════════════════ -->
<footer>
  <div class="foot-links">
    <a href="#">Community</a>
    <a href="pages/forum.php">Forum</a>
    <a href="#">Links</a>
    <a href="pages/about.php">About</a>
  </div>
  <div class="foot-icons">
    <a href="#" class="foot-icon" aria-label="GitHub">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.3 3.44 9.8 8.2 11.38.6.11.82-.26.82-.58v-2.03c-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.74.08-.73.08-.73 1.2.08 1.84 1.24 1.84 1.24 1.07 1.83 2.81 1.3 3.5 1 .1-.78.42-1.3.76-1.6-2.67-.3-5.47-1.33-5.47-5.93 0-1.31.47-2.38 1.24-3.22-.13-.3-.54-1.52.12-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 3-.4c1.02.005 2.04.14 3 .4 2.28-1.55 3.29-1.23 3.29-1.23.66 1.66.25 2.88.12 3.18.77.84 1.24 1.91 1.24 3.22 0 4.61-2.81 5.63-5.48 5.93.43.37.81 1.1.81 2.22v3.29c0 .32.22.7.83.58C20.57 21.8 24 17.3 24 12c0-6.63-5.37-12-12-12z"/></svg>
    </a>
    <a href="#" class="foot-icon" aria-label="Discord">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.32 4.37A19.8 19.8 0 0 0 15.5 3c-.22.4-.48.93-.65 1.36a18.3 18.3 0 0 0-5.7 0A13.5 13.5 0 0 0 8.5 3 19.9 19.9 0 0 0 3.68 4.37C.53 9.19-.32 13.9.1 18.54a20 20 0 0 0 6.1 3.07 15.1 15.1 0 0 0 1.3-2.1 13 13 0 0 1-2.04-.98c.17-.12.33-.25.49-.38a14.16 14.16 0 0 0 12.1 0c.16.13.32.26.5.38-.65.38-1.34.71-2.05.98.37.73.8 1.43 1.3 2.1a19.9 19.9 0 0 0 6.1-3.07c.5-5.28-.84-9.87-3.58-14.17zM8.02 15.82c-1.23 0-2.24-1.14-2.24-2.54S6.77 10.74 8 10.74c1.25 0 2.27 1.14 2.24 2.54-.01 1.4-1 2.54-2.22 2.54zm7.96 0c-1.22 0-2.22-1.14-2.22-2.54s.99-2.54 2.22-2.54c1.24 0 2.25 1.14 2.22 2.54 0 1.4-.98 2.54-2.22 2.54z"/></svg>
    </a>
  </div>
  <span class="foot-copy">© NVR Community 2024</span>
</footer>

<!-- ═══ MODALS ════════════════════════════════════════════════ -->

<!-- LOGIN / REGISTER -->
<div id="loginModal" class="modal-backdrop hidden">
  <div class="modal">
    <button class="modal-close" onclick="NVR.ui.closeModal('loginModal')">&times;</button>
    <div class="modal-tabs">
      <button class="tab-btn active" data-tab="loginTab">Entrar</button>
      <button class="tab-btn" data-tab="registerTab">Registar</button>
    </div>

    <!-- Login form -->
    <div id="loginTab" class="tab-content">
      <h3 class="modal-title">Bem-vindo de volta</h3>
      <div class="form-group">
        <label>Username ou Email</label>
        <input type="text" id="loginField" placeholder="username ou email">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="loginPass" placeholder="••••••••">
      </div>
      <div id="loginError" class="form-error hidden"></div>
      <button class="btn-primary full" onclick="NVR.auth.login()">Entrar</button>
    </div>

    <!-- Register form -->
    <div id="registerTab" class="tab-content hidden">
      <h3 class="modal-title">Cria a tua conta</h3>
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="regUsername" placeholder="NVR_Player" maxlength="32">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" id="regEmail" placeholder="gamer@nvr.gg">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="regPass" placeholder="Min. 8 caracteres">
      </div>
      <div id="regError" class="form-error hidden"></div>
      <button class="btn-primary full" onclick="NVR.auth.register()">Criar Conta</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div id="toast" class="toast hidden"></div>

<script src="assets/js/nvr.js"></script>
<script src="assets/js/home.js"></script>
</body>
</html>
