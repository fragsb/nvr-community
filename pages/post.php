<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Post — NVR Community Hub</title>
<link rel="stylesheet" href="../assets/css/main.css">
</head>
<body>
<nav id="navbar">
  <div class="nav-inner">
    <div class="logo"><a href="../index.php" style="color:inherit;text-decoration:none">NVR – NEVER CM <span>| COMMUNITY HUB</span></a></div>
    <ul class="nav-links">
      <li><a href="../index.php">Home</a></li>
      <li><a href="forum.php">Forum</a></li>
      <li><a href="rankings.php">Rankings</a></li>
      <li><a href="tournaments.php">Tournaments</a></li>
      <li><a href="about.php">About</a></li>
    </ul>
    <div class="nav-right">
      <button class="btn-primary" onclick="window.location.href='../index.php'">Voltar</button>
    </div>
  </div>
</nav>
<main class="hub-main" style="padding:24px">
  <section class="card section-gap">
    <h1 class="sec-title">Post do Fórum</h1>
    <p style="color:var(--muted);margin-top:16px">Slug: <strong><?= htmlspecialchars($_GET['slug'] ?? 'nenhum') ?></strong></p>
    <p style="color:var(--muted);margin-top:12px">Esta página de post é um stub. O backend e o layout já estão corrigidos para não falhar ao carregar.</p>
  </section>
</main>
<script src="../assets/js/nvr.js"></script>
</body>
</html>
