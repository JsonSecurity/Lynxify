<?php
// Carpeta donde están tus canciones
$musicDir = __DIR__ . "/music";
$files = [];

// Leer todos los archivos .mp3, .m4a, .opus
if (is_dir($musicDir)) {
    foreach (scandir($musicDir) as $file) {
        if (preg_match('/\.(mp3|m4a|opus)$/i', $file)) {
            $files[] = "music/" . $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reproductor de Música</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-bottom: 110px; background: #f8f9fa; }
    .song-card { margin-bottom: 10px; padding: 12px; background: white; border-radius: 8px;
                 box-shadow: 0 2px 4px rgba(0,0,0,.06); display: flex; flex-direction: column; }
    .song-card.playing { border-left: 4px solid #0d6efd; background: #eef6ff; }
    audio { width: 100%; margin-top: 8px; }
    .controls { margin-bottom: 10px; display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
    .queue-box { max-height: 220px; overflow:auto; }
    .fixed-player { position: fixed; left: 0; right: 0; bottom: 0; z-index: 999;
                    background: #ffffff; border-top: 1px solid #ddd; padding: 8px 12px; }
    .list-group-item.active { background-color: #0d6efd; border-color: #0d6efd; color: white; }
  </style>
</head>
<body>
  <div class="container py-3">
    <h2>Mis canciones</h2>

    <div class="row mb-2">
      <div class="col-md-8">
        <input type="text" id="search" class="form-control" placeholder="Buscar canciones...">
      </div>
      <div class="col-md-4 controls">
        <button id="randomBtn" class="btn btn-primary">Aleatorio</button>
        <button id="playAllBtn" class="btn btn-primary">Todas</button>
        <button id="repeatBtn" class="btn btn-outline-warning">Repetir</button>
      </div>
    </div>

    <div class="row">
      <div class="col-md-7">
        <div id="songList"></div>
      </div>
      <div class="col-md-5">
        <h5>Lista actual</h5>
        <div class="queue-box">
          <ul id="currentQueue" class="list-group"></ul>
        </div>
      </div>
    </div>
  </div>

<!-- Footer con controles -->
<div class="fixed-player">
  <div class="d-flex align-items-center justify-content-between mb-1">
    <div>
      <button id="prevBtn" class="btn btn-secondary">⏮</button>
      <button id="playPauseBtn" class="btn btn-primary">▶️</button>
      <button id="nextBtn" class="btn btn-secondary">⏭</button>
    </div>
    <div style="flex:1; text-align:center;">
      <span id="nowPlaying">Sin reproducir</span>
    </div>
    <div style="text-align:right;">
      <small id="modeLabel" class="text-muted">Modo: normal</small>
    </div>
  </div>

  <!-- Barra de progreso -->
  <div class="d-flex align-items-center">
    <small id="currentTime">0:00</small>
    <input type="range" id="progressBar" value="0" min="0" step="1" class="form-range mx-2" style="flex:1;">
    <small id="totalTime">0:00</small>
  </div>
</div>

<script>
  // PHP genera esta lista automáticamente
  const files = <?php echo json_encode($files); ?>;

  const songList = document.getElementById("songList");
  const currentQueue = document.getElementById("currentQueue");
  const nowPlayingEl = document.getElementById("nowPlaying");
  const modeLabel = document.getElementById("modeLabel");
  const playPauseBtn = document.getElementById("playPauseBtn");
  const prevBtn = document.getElementById("prevBtn");
  const nextBtn = document.getElementById("nextBtn");
  const randomBtn = document.getElementById("randomBtn");
  const playAllBtn = document.getElementById("playAllBtn");
  const repeatBtn = document.getElementById("repeatBtn");
  const searchInput = document.getElementById("search");
  const progressBar = document.getElementById("progressBar");
  const currentTimeEl = document.getElementById("currentTime");
  const totalTimeEl = document.getElementById("totalTime");

  let playlist = [];
  let playbackOrder = [];
  let orderPos = 0;
  let playMode = "normal"; // normal | shuffle
  let repeatAll = false;
  let isSeeking = false;

  // construir lista principal
  files.forEach((f, i) => {
    const card = document.createElement("div");
    card.className = "song-card";
    card.dataset.index = i;
    card.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center">
        <b class="song-title" style="cursor:pointer">${f}</b>
        <a href="${f}" download class="btn btn-success btn-sm">Descargar</a>
      </div>
      <audio preload="none" data-index="${i}">
        <source src="${f}">
      </audio>
    `;
    songList.appendChild(card);
    card.querySelector(".song-title").addEventListener("click", () => playSong(i));
  });

  playlist = Array.from(document.querySelectorAll("#songList audio"));
  const songCards = Array.from(document.querySelectorAll(".song-card"));

  function shuffle(arr) {
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
  }

  function buildOrder() {
    let arr = files.map((_, i) => i);
    if (playMode === "shuffle") shuffle(arr);
    playbackOrder = arr;
    orderPos = 0;
    renderQueue();
  }

  function renderQueue() {
    currentQueue.innerHTML = "";
    playbackOrder.forEach((idx, pos) => {
      const li = document.createElement("li");
      li.className = "list-group-item";
      if (pos === orderPos) li.classList.add("active");
      li.textContent = files[idx];
      li.addEventListener("click", () => playSong(pos));
      currentQueue.appendChild(li);
    });
    updateUI();
  }

  function playSong(pos) {
    playlist.forEach(a => { a.pause(); a.currentTime = 0; });
    orderPos = pos;
    playlist[playbackOrder[orderPos]].play();
    renderQueue();
  }

  function nextSong() {
    if (orderPos < playbackOrder.length - 1) {
      playSong(orderPos + 1);
    } else if (repeatAll) {
      buildOrder();
      playSong(0);
    }
  }

  function prevSong() {
    if (orderPos > 0) {
      playSong(orderPos - 1);
    } else if (repeatAll) {
      playSong(playbackOrder.length - 1);
    }
  }

  function togglePlayPause() {
    const audio = playlist[playbackOrder[orderPos]];
    if (audio.paused) audio.play(); else audio.pause();
    updateUI();
  }

  function updateUI() {
    nowPlayingEl.textContent = files[playbackOrder[orderPos]] || "Sin reproducir";
    modeLabel.textContent = `Modo: ${playMode}${repeatAll ? " • repetir" : ""}`;
    const audio = playlist[playbackOrder[orderPos]];
    playPauseBtn.textContent = audio && !audio.paused ? "⏸" : "▶️";
    songCards.forEach(c => c.classList.remove("playing"));
    const card = songCards[playbackOrder[orderPos]];
    if (card) card.classList.add("playing");
  }

  function formatTime(sec) {
    if (isNaN(sec)) return "0:00";
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return `${m}:${s.toString().padStart(2,"0")}`;
  }

  // actualizar barra en tiempo real
  playlist.forEach(audio => {
    audio.addEventListener("timeupdate", () => {
      if (playbackOrder.length === 0 || isSeeking) return;
      if (audio === playlist[playbackOrder[orderPos]]) {
        progressBar.max = audio.duration || 0;
        progressBar.value = audio.currentTime;
        currentTimeEl.textContent = formatTime(audio.currentTime);
        totalTimeEl.textContent = formatTime(audio.duration);
      }
    });
  });

  // permitir adelantar/retroceder
  progressBar.addEventListener("input", () => {
    const audio = playlist[playbackOrder[orderPos]];
    if (!isNaN(audio.duration)) {
      audio.currentTime = progressBar.value;
    }
  });
  
  progressBar.addEventListener("touchstart", () => { isSeeking = true; });
  progressBar.addEventListener("touchend", () => {
    isSeeking = false;
    const audio = playlist[playbackOrder[orderPos]];
    if (!isNaN(audio.duration)) {
      audio.currentTime = progressBar.value;
    }
  });

  playlist.forEach((audio, idx) => {
    audio.addEventListener("play", () => {
      playlist.forEach(a => { if (a !== audio) a.pause(); });
      orderPos = playbackOrder.indexOf(idx);
      renderQueue();
    });
    audio.addEventListener("ended", nextSong);
  });

  // botones
  randomBtn.onclick = () => { playMode="shuffle"; buildOrder(); playSong(0); };
  playAllBtn.onclick = () => { playMode="normal"; buildOrder(); playSong(0); };
  repeatBtn.onclick = () => { repeatAll=!repeatAll; repeatBtn.classList.toggle("btn-warning"); updateUI(); };
  prevBtn.onclick = prevSong;
  nextBtn.onclick = nextSong;
  playPauseBtn.onclick = togglePlayPause;

  // búsqueda
  searchInput.addEventListener("input", () => {
    const filter = searchInput.value.toLowerCase();
    songCards.forEach((c, i) => {
      c.style.display = files[i].toLowerCase().includes(filter) ? "" : "none";
    });
  });

  // inicializar
  buildOrder();
</script>

</body>
</html>
