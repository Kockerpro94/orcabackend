<?php
// ============================================================
// OrcaStream Media Player
// Handles: ?movie=tt1234567 | ?anime=21 | ?multi_videos=url1,url2
// ============================================================

$movie    = isset($_GET['movie'])        ? trim($_GET['movie'])        : null;
$anime    = isset($_GET['anime'])        ? trim($_GET['anime'])        : null;
$multi    = isset($_GET['multi_videos']) ? trim($_GET['multi_videos']) : null;

// Build the embed URL based on which param is present
$embedUrl   = null;
$pageTitle  = 'OrcaStream Player';
$mediaType  = 'unknown';

if ($movie) {
    // Supports both IMDB IDs (tt...) and TMDb numeric IDs
    $mediaType = 'movie';
    $pageTitle = 'Movie Player · OrcaStream';
    if (strpos($movie, 'tt') === 0) {
        // IMDB ID → use vidsrc
        $embedUrl = 'https://vidsrc.to/embed/movie/' . urlencode($movie);
    } else {
        // Treat as TMDb ID
        $embedUrl = 'https://vidsrc.to/embed/movie/' . urlencode($movie);
    }
}

if ($anime) {
    // Anime MAL ID (Jikan) or title slug
    $mediaType = 'anime';
    $pageTitle = 'Anime Player · OrcaStream';
    if (is_numeric($anime)) {
        $embedUrl = 'https://gogoanime.cl/category/' . urlencode($anime);
    } else {
        $embedUrl = 'https://gogoanime.cl/category/' . urlencode(strtolower(str_replace(' ', '-', $anime)));
    }
}

if ($multi) {
    $mediaType = 'multi';
    $pageTitle = 'Multi-Source Player · OrcaStream';
    // multi_videos accepts comma-separated URLs
    $videoUrls = array_filter(array_map('trim', explode(',', $multi)));
}

// Sanitize embed URL (whitelist trusted embed domains)
$allowedDomains = ['vidsrc.to', 'vidsrc.me', 'gogoanime.cl', 'embed.su', 'moviesapi.club'];
if ($embedUrl) {
    $parsedHost = parse_url($embedUrl, PHP_URL_HOST);
    $parsedHost = preg_replace('/^www\./', '', $parsedHost ?? '');
    if (!in_array($parsedHost, $allowedDomains)) {
        $embedUrl = null; // Block untrusted domains
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <meta name="description" content="OrcaStream – Stream movies, series, and anime instantly." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0a0a0f;
      --surface:   #111118;
      --border:    rgba(255,255,255,0.07);
      --accent:    #e50914;
      --accent2:   #b20710;
      --text:      #f1f1f1;
      --muted:     rgba(255,255,255,0.4);
      --radius:    14px;
    }

    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
    }

    /* ── NAV ── */
    nav {
      position: fixed;
      top: 0; left: 0; right: 0;
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 28px;
      background: rgba(10,10,15,0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
    }
    .nav-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 20px;
      font-weight: 700;
      color: var(--accent);
      text-decoration: none;
      letter-spacing: 0.5px;
    }
    .nav-logo svg { width: 28px; height: 28px; }
    .nav-badge {
      font-size: 11px;
      background: var(--accent);
      color: #fff;
      padding: 3px 9px;
      border-radius: 20px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* ── MAIN LAYOUT ── */
    main {
      padding-top: 70px;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    /* ── PLAYER CONTAINER ── */
    .player-wrap {
      width: 100%;
      max-width: 1100px;
      padding: 24px 20px 10px;
    }

    .player-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }
    .type-badge {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 5px 12px;
      border-radius: 6px;
    }
    .type-movie  { background: rgba(229,9,20,0.2);  color: #ff6b6b; border: 1px solid rgba(229,9,20,0.4); }
    .type-anime  { background: rgba(111,66,193,0.2); color: #bf8fff; border: 1px solid rgba(111,66,193,0.4); }
    .type-multi  { background: rgba(13,110,253,0.2); color: #74b9ff; border: 1px solid rgba(13,110,253,0.4); }

    /* ── IFRAME PLAYER ── */
    .iframe-box {
      width: 100%;
      aspect-ratio: 16/9;
      background: #000;
      border-radius: var(--radius);
      overflow: hidden;
      border: 1px solid var(--border);
      box-shadow: 0 24px 80px rgba(0,0,0,0.7);
    }
    .iframe-box iframe {
      width: 100%;
      height: 100%;
      border: none;
    }

    /* ── MULTI VIDEO TABS ── */
    .tab-bar {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .tab-btn {
      background: var(--surface);
      border: 1px solid var(--border);
      color: var(--muted);
      padding: 8px 18px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 13px;
      font-family: 'Inter', sans-serif;
      transition: all 0.2s;
    }
    .tab-btn:hover, .tab-btn.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }

    /* ── NATIVE VIDEO (multi_videos) ── */
    #nativePlayer {
      width: 100%;
      aspect-ratio: 16/9;
      border-radius: var(--radius);
      background: #000;
      border: 1px solid var(--border);
      box-shadow: 0 24px 80px rgba(0,0,0,0.7);
      display: block;
    }

    /* ── SOURCE SWITCHER PANEL ── */
    .source-panel {
      margin-top: 14px;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 20px;
    }
    .source-label {
      font-size: 12px;
      color: var(--muted);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-bottom: 10px;
    }
    .source-btns {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    .src-btn {
      background: rgba(255,255,255,0.05);
      border: 1px solid var(--border);
      color: var(--text);
      padding: 7px 16px;
      border-radius: 7px;
      cursor: pointer;
      font-size: 12px;
      font-family: 'Inter', sans-serif;
      transition: all 0.2s;
    }
    .src-btn:hover, .src-btn.active {
      background: var(--accent);
      border-color: var(--accent);
      color: #fff;
    }

    /* ── ERROR / EMPTY STATE ── */
    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 16px;
      padding: 80px 20px;
      text-align: center;
    }
    .empty-icon {
      width: 80px; height: 80px;
      border-radius: 50%;
      background: rgba(229,9,20,0.1);
      border: 1px solid rgba(229,9,20,0.25);
      display: flex; align-items: center; justify-content: center;
      font-size: 36px;
    }
    .empty-title { font-size: 20px; font-weight: 700; }
    .empty-desc  { color: var(--muted); font-size: 14px; line-height: 1.7; max-width: 420px; }
    .code-block {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 16px 20px;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      color: #74b9ff;
      text-align: left;
      width: 100%;
      max-width: 520px;
      line-height: 2;
    }

    /* ── FOOTER ── */
    footer {
      text-align: center;
      padding: 30px 20px;
      color: var(--muted);
      font-size: 12px;
      border-top: 1px solid var(--border);
      margin-top: 30px;
      width: 100%;
    }

    /* ── RESPONSIVE ── */
    @media (max-width: 600px) {
      nav { padding: 12px 16px; }
      .player-wrap { padding: 16px 12px; }
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav>
  <a class="nav-logo" href="index.php">
    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="12" cy="12" r="10" fill="#e50914" opacity="0.15"/>
      <path d="M10 8l6 4-6 4V8z" fill="#e50914"/>
    </svg>
    OrcaStream
  </a>
  <?php if ($mediaType !== 'unknown'): ?>
  <span class="nav-badge">
    <?= $mediaType === 'movie' ? '🎬 Movie' : ($mediaType === 'anime' ? '🌸 Anime' : '📺 Multi') ?>
  </span>
  <?php endif; ?>
</nav>

<main>
<div class="player-wrap">

<?php if (!$movie && !$anime && !$multi): ?>
  <!-- ── EMPTY STATE: No params ── -->
  <div class="empty-state">
    <div class="empty-icon">🎬</div>
    <div class="empty-title">OrcaStream Embed Player</div>
    <div class="empty-desc">
      Pass a query parameter to start streaming.<br/>
      Supports movies, anime, and multi-source video.
    </div>
    <div class="code-block">
      ?movie=tt0133093<br/>
      ?anime=21<br/>
      ?multi_videos=https://url1.mp4,https://url2.mp4
    </div>
  </div>

<?php elseif (($movie || $anime) && $embedUrl): ?>
  <!-- ── SINGLE EMBED PLAYER (movie / anime) ── -->
  <div class="player-header">
    <span class="type-badge <?= $movie ? 'type-movie' : 'type-anime' ?>">
      <?= $movie ? '🎬 Movie' : '🌸 Anime' ?>
    </span>
    <span style="color:var(--muted);font-size:13px;">
      ID: <strong style="color:var(--text)"><?= htmlspecialchars($movie ?? $anime) ?></strong>
    </span>
  </div>

  <div class="iframe-box">
    <iframe
      src="<?= htmlspecialchars($embedUrl) ?>"
      allowfullscreen
      allow="autoplay; fullscreen; picture-in-picture"
      referrerpolicy="origin"
      scrolling="no">
    </iframe>
  </div>

  <?php if ($movie): ?>
  <!-- Source switcher for movies -->
  <div class="source-panel">
    <div class="source-label">Switch Source</div>
    <div class="source-btns">
      <button class="src-btn active"
        onclick="switchSource('https://vidsrc.to/embed/movie/<?= urlencode($movie) ?>', this)">
        VidSrc
      </button>
      <button class="src-btn"
        onclick="switchSource('https://vidsrc.me/embed/movie?imdb=<?= urlencode($movie) ?>', this)">
        VidSrc.me
      </button>
      <button class="src-btn"
        onclick="switchSource('https://embed.su/embed/movie/<?= urlencode($movie) ?>', this)">
        Embed.su
      </button>
      <button class="src-btn"
        onclick="switchSource('https://moviesapi.club/movie/<?= urlencode($movie) ?>', this)">
        MoviesAPI
      </button>
    </div>
  </div>
  <?php endif; ?>

<?php elseif ($multi && !empty($videoUrls)): ?>
  <!-- ── MULTI VIDEO PLAYER ── -->
  <div class="player-header">
    <span class="type-badge type-multi">📺 Multi-Source</span>
    <span style="color:var(--muted);font-size:13px;"><?= count($videoUrls) ?> source(s) available</span>
  </div>

  <!-- Tab switcher -->
  <div class="tab-bar" id="tabBar">
    <?php foreach ($videoUrls as $idx => $url): ?>
    <button class="tab-btn <?= $idx === 0 ? 'active' : '' ?>"
      onclick="switchVideo(<?= $idx ?>, this)">
      Source <?= $idx + 1 ?>
    </button>
    <?php endforeach; ?>
  </div>

  <video id="nativePlayer" controls autoplay>
    <source src="<?= htmlspecialchars(reset($videoUrls)) ?>" type="video/mp4" />
    Your browser does not support HTML5 video.
  </video>

  <script>
    const videoUrls = <?= json_encode(array_values($videoUrls)) ?>;
    const player = document.getElementById('nativePlayer');

    function switchVideo(index, btn) {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const currentTime = player.currentTime;
      player.src = videoUrls[index];
      player.load();
      player.play();
    }
  </script>

<?php elseif ($movie || $anime): ?>
  <!-- Embed URL blocked or unresolved -->
  <div class="empty-state">
    <div class="empty-icon">⛔</div>
    <div class="empty-title">Source Unavailable</div>
    <div class="empty-desc">
      The requested media could not be loaded from a trusted source.<br/>
      Try a different ID or check back later.
    </div>
  </div>
<?php endif; ?>

</div>
</main>

<footer>
  &copy; <?= date('Y') ?> OrcaStream · All streams are sourced from third-party embed providers.
</footer>

<script>
  // Source switcher for embed iframes
  function switchSource(url, btn) {
    document.querySelectorAll('.src-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const iframe = document.querySelector('.iframe-box iframe');
    if (iframe) iframe.src = url;
  }
</script>

</body>
</html>
