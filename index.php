<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
$db = get_db();
$rows = [];
$categorias = [];

// Detect if 'imagen' and 'categoria' columns exist
$hasImagen = false;
$hasCategoria = false;
$chk = $db->query("SHOW COLUMNS FROM medicamentos LIKE 'imagen'");
if ($chk && $chk->num_rows > 0) { $hasImagen = true; }
$chk2 = $db->query("SHOW COLUMNS FROM medicamentos LIKE 'categoria'");
if ($chk2 && $chk2->num_rows > 0) { $hasCategoria = true; }

// Build query based on available columns
$selectFields = 'id, nombre, descripcion, precio, stock';
if ($hasImagen) { $selectFields .= ', imagen'; } else { $selectFields .= ', NULL AS imagen'; }
if ($hasCategoria) { $selectFields .= ', categoria'; } else { $selectFields .= ', "General" AS categoria'; }

$res = $db->query("SELECT {$selectFields} FROM medicamentos ORDER BY categoria ASC, nombre ASC");
if ($res) { 
  $rows = $res->fetch_all(MYSQLI_ASSOC);
  // Agrupar por categoría
  foreach ($rows as $row) {
    $cat = $row['categoria'] ?? 'General';
    if (!isset($categorias[$cat])) {
      $categorias[$cat] = [];
    }
    $categorias[$cat][] = $row;
  }
}
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
  <header class="container py-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #1f2937;">
    <div>
      <h1 class="m-0" style="font-size: 1.8rem; font-weight: 700; color: #f1f5f9;">🏥 Farmacia Virtual</h1>
      <p class="m-0 mt-1" style="font-size: 0.9rem; color: #94a3b8;">Tu salud, nuestra prioridad</p>
    </div>
    <a href="login.php" class="btn btn-primary" style="padding: 10px 20px; border-radius: 10px;">Iniciar Sesión</a>
  </header>

  <main class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
      <div>
        <h2 class="m-0" style="font-size: 1.6rem; font-weight: 700; color: #f1f5f9;">Catálogo de Productos</h2>
        <p class="m-0 mt-1" style="font-size: 0.95rem; color: #94a3b8;">Encuentra los medicamentos que necesitas</p>
      </div>
      <div style="color: #60a5fa; font-weight: 600;">
        <?= count($rows) ?> productos disponibles
      </div>
    </div>

    <?php foreach ($categorias as $categoria => $productos): ?>
      <section class="category-section mb-5">
        <div class="category-header">
          <h3 class="category-title"><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></h3>
          <span class="category-count"><?= count($productos) ?> productos</span>
        </div>
        
        <div class="catalog">
          <?php foreach ($productos as $p): ?>
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
      </section>
    <?php endforeach; ?>
  </main>

  <!-- Asistente de voz cliente -->
  <button id="catalogMicFab" class="fab" title="Hablar con el asistente">🎤</button>
  
  <!-- Input alternativo de texto -->
  <div id="textInputPanel" class="text-input-panel d-none">
    <div class="tip-header">
      <span class="tip-title">💬 Escribe tu pregunta</span>
      <button id="closeTextPanel" class="btn-close-panel">✕</button>
    </div>
    <div class="tip-input-group">
      <input type="text" id="textInput" placeholder="Ej: ¿Tienen paracetamol?" />
      <button id="sendTextBtn" class="btn-send">Enviar</button>
    </div>
    <div class="tip-hint">💡 También puedes usar el micrófono 🎤 si tienes internet</div>
  </div>
  
  <button id="textInputFab" class="fab-text" title="Escribir pregunta">💬</button>
  
  <div id="voiceToast" class="voice-toast d-none">
    <div class="vt-title">Asistente</div>
    <div class="vt-question"></div>
    <div class="vt-answer"></div>
  </div>

  <script src="catalog.js"></script>
</body>
</html>
