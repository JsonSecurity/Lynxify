<?php
# servidor de musica (python -m http.server 8080)
$baseUrl = "http://192.168.101.13:8080/";

// Obtener el HTML del índice
$html = file_get_contents($baseUrl);

$files = [];

if ($html !== false) {
    if (preg_match_all('/href="([^"]+\.(mp3|m4a|opus))"/i', $html, $matches)) {
        foreach ($matches[1] as $file) {
            $files[] = [
                "name" => urldecode(basename($file)), // nombre legible
                "url"  => $baseUrl . $file            // URL para reproducir
            ];
        }
    }
}
/*
// Mostrar en HTML con reproductor
foreach ($files as $song) {
    echo "<p>$song</p>";
    echo "<audio controls src='$song'></audio><br>";
}*/
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

<script>const files = <?php echo json_encode($files); ?>;</script>
<script src="index.js"></script>

</body>
</html>
