<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$db = get_db();
$rows = [];
// Detect if 'imagen' column exists in medicamentos
$hasImagen = false;
$chk = $db->query("SHOW COLUMNS FROM medicamentos LIKE 'imagen'");
if ($chk && $chk->num_rows > 0) { $hasImagen = true; }
if ($hasImagen) {
  $res = $db->query('SELECT id, nombre, descripcion, precio, stock, imagen FROM medicamentos ORDER BY nombre ASC');
} else {
  $res = $db->query('SELECT id, nombre, descripcion, precio, stock, NULL AS imagen FROM medicamentos ORDER BY nombre ASC');
}
if ($res) { $rows = $res->fetch_all(MYSQLI_ASSOC); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Catálogo - Farmacia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="container py-3 d-flex justify-content-between align-items-center">
    <h1 class="m-0">Farmacia</h1>
    <a href="login.php" class="btn btn-sm btn-primary">Iniciar Sesión</a>
  </header>

  <main class="container pb-5">
    <h2 class="mb-3">Catálogo de Productos</h2>
    <div class="catalog">
      <?php foreach ($rows as $p): ?>
        <article class="card-product">
          <div class="image" aria-hidden="true" style="<?php if(!empty($p['imagen'])) { echo 'background-image:url(' . htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8') . '); background-size:cover; background-position:center;'; } ?>"></div>
          <div class="body">
            <h3 class="title"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
            <p class="desc"><?= htmlspecialchars((string)$p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="meta">
              <span class="price">S/ <?= number_format((float)$p['precio'], 2) ?></span>
              <span class="stock <?= (int)$p['stock'] > 0 ? 'ok' : 'zero' ?>">Stock: <?= (int)$p['stock'] ?></span>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </main>

  <!-- Asistente de voz cliente -->
  <button id="catalogMicFab" class="fab" title="Hablar con el asistente">🎤</button>
  <div id="voiceToast" class="voice-toast d-none">
    <div class="vt-title">Asistente</div>
    <div class="vt-question"></div>
    <div class="vt-answer"></div>
  </div>

  <script src="catalog.js"></script>
</body>
</html>
