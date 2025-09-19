  //const files = <?php echo json_encode($files); ?>;
  
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
        <b class="song-title" style="cursor:pointer">${f.name}</b>
        <a href="${f.url}" download class="btn btn-success btn-sm">Descargar</a>
      </div>
      <audio preload="none" data-index="${i}">
        <source src="${f.url}">
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
      li.textContent = files[idx].name;
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
    nowPlayingEl.textContent = files[playbackOrder[orderPos]].name || "Sin reproducir";
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
