<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';
$db = get_db();

// Detectar si hay sesión activa
$isLoggedIn = false;
$userType = '';
$userName = '';

if (isset($_SESSION['cliente_id']) && $_SESSION['user_type'] === 'cliente') {
    $isLoggedIn = true;
    $userType = 'cliente';
    $userName = $_SESSION['cliente_nombre'] ?? $_SESSION['cliente_username'] ?? 'Cliente';
} elseif (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
    $isLoggedIn = true;
    $userType = 'admin';
    $userName = $_SESSION['admin_username'] ?? 'Admin';
}
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
  <title>Catálogo - Farmacia Omarcitoia</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="modern-header">
    <div class="container">
      <div class="header-content">
        <div class="header-brand">
          <h1>💊 Farmacia Omarcitoia</h1>
          <p>Tu salud, nuestra prioridad</p>
        </div>
        <div class="header-actions">
          <?php if ($isLoggedIn): ?>
            <?php if ($userType === 'cliente'): ?>
              <span class="user-welcome">👋 Hola, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
              <a href="cliente_panel.php" class="btn btn-primary">Mi Panel</a>
              <a href="logout.php" class="btn btn-outline">Cerrar Sesión</a>
            <?php elseif ($userType === 'admin'): ?>
              <span class="user-welcome">👋 Hola, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
              <a href="admin_panel.php" class="btn btn-primary">Panel Admin</a>
              <a href="logout.php" class="btn btn-outline">Cerrar Sesión</a>
            <?php endif; ?>
          <?php else: ?>
            <a href="register.php" class="btn btn-outline">Registrarse</a>
            <a href="login_unified.php" class="btn btn-primary">Iniciar Sesión</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <main class="modern-main">
    <div class="container">
      <!-- Mini resumen del carrito -->
      <div id="cartSummaryWidget" class="cart-summary-widget">
        <div class="widget-header">
          <span class="widget-icon">🛍️</span>
          <span class="widget-title">Mi Carrito</span>
        </div>
        <div class="widget-body">
          <div id="widgetItemCount" class="widget-stat">
            <span class="stat-number">0</span>
            <span class="stat-label">productos</span>
          </div>
          <div id="widgetTotal" class="widget-total">S/ 0.00</div>
        </div>
        <button id="widgetViewCart" class="widget-btn">
          Ver Carrito y Pagar
        </button>
      </div>
      
      <div class="catalog-header">
        <div>
          <h2 class="catalog-title">Catálogo de Productos</h2>
          <p class="catalog-subtitle">Encuentra los medicamentos que necesitas</p>
        </div>
        <div class="catalog-count">
          <?= count($rows) ?> productos
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
            <article class="card-product <?= (int)$p['stock'] === 0 ? 'agotado' : '' ?>">
              <div class="image" aria-hidden="true" style="<?php if(!empty($p['imagen'])) { echo 'background-image:url(' . htmlspecialchars($p['imagen'], ENT_QUOTES, 'UTF-8') . '); background-size:cover; background-position:center;'; } ?>">
                <?php if ((int)$p['stock'] === 0): ?>
                  <div class="badge-agotado">No disponible</div>
                <?php endif; ?>
              </div>
              <div class="body">
                <h3 class="title <?= (int)$p['stock'] === 0 ? 'title-agotado' : '' ?>"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="desc"><?= htmlspecialchars((string)$p['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="meta">
                  <span class="price <?= (int)$p['stock'] === 0 ? 'price-agotado' : '' ?>">S/ <?= number_format((float)$p['precio'], 2) ?></span>
                  <span class="disponibilidad <?= (int)$p['stock'] > 0 ? 'disponible' : 'no-disponible' ?>">
                    <?= (int)$p['stock'] > 0 ? '✓ Disponible' : '✕ Agotado' ?>
                  </span>
                </div>
                <?php if ((int)$p['stock'] > 0): ?>
                  <button class="btn-add-cart" 
                          data-id="<?= (int)$p['id'] ?>" 
                          data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8') ?>" 
                          data-precio="<?= (float)$p['precio'] ?>" 
                          data-stock="<?= (int)$p['stock'] ?>">
                    🛍️ Agregar al Carrito
                  </button>
                <?php else: ?>
                  <button class="btn-add-cart" disabled style="opacity: 0.5; cursor: not-allowed;">
                    ❌ No Disponible
                  </button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endforeach; ?>
    </div>
  </main>
  
  <footer class="modern-footer">
    <div class="container">
      <p>© 2025 Farmacia Omarcitoia - Tu salud, nuestra prioridad 💊</p>
    </div>
  </footer>

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

  <!-- Carrito flotante -->
  <div id="cartSidebar" class="cart-sidebar">
    <div class="cart-header">
      <h3>🛍️ Mi Carrito</h3>
      <button id="closeCart" class="btn-close-cart">✕</button>
    </div>
    
    <div id="cartItems" class="cart-items">
      <div class="empty-cart">
        <div class="empty-icon">🛍️</div>
        <p>Tu carrito está vacío</p>
        <small>Agrega productos para comenzar</small>
      </div>
    </div>
    
    <div class="cart-footer">
      <div class="cart-total">
        <span>Total:</span>
        <span id="cartTotal" class="total-amount">S/ 0.00</span>
      </div>
      <button id="checkoutBtn" class="btn-checkout" disabled>
        💳 Proceder al Pago
      </button>
    </div>
  </div>
  
  <!-- Botón flotante del carrito -->
  <button id="cartFab" class="cart-fab">
    🛍️
    <span id="cartCount" class="cart-count">0</span>
  </button>
  
  <!-- Overlay -->
  <div id="cartOverlay" class="cart-overlay"></div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="catalog.js"></script>
  <script src="cart.js"></script>
</body>
</html>
