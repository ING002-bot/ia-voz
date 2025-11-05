<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

// Verificar autenticación
if (!isset($_SESSION['cliente_id']) || $_SESSION['user_type'] !== 'cliente') {
    header('Location: login_unified.php');
    exit;
}

$db = get_db();
$cliente_id = (int)$_SESSION['cliente_id'];
$cliente_username = $_SESSION['cliente_username'];
$cliente_nombre = $_SESSION['cliente_nombre'];

// Obtener información del cliente
$stmt = $db->prepare('SELECT puntos, email, telefono, direccion FROM usuarios_clientes WHERE id = ? LIMIT 1');
$cliente_data = ['puntos' => 0, 'email' => '', 'telefono' => '', 'direccion' => ''];
if ($stmt) {
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $cliente_data = $row;
    }
    $stmt->close();
}

// Obtener historial de compras
$compras = [];
$stmt = $db->prepare('
    SELECT c.id, c.fecha, c.cantidad, c.precio_unitario, c.subtotal, m.nombre as medicamento
    FROM compras c
    INNER JOIN medicamentos m ON c.medicamento_id = m.id
    WHERE c.usuario_id = ?
    ORDER BY c.fecha DESC
    LIMIT 20
');
if ($stmt) {
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $compras = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Obtener boletas
$boletas = [];
$stmt = $db->prepare('
    SELECT id, numero_boleta, total, fecha, detalles
    FROM boletas
    WHERE usuario_id = ?
    ORDER BY fecha DESC
    LIMIT 10
');
if ($stmt) {
    $stmt->bind_param('i', $cliente_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $boletas = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$puntos = (int)$cliente_data['puntos'];
$puntos_percent = min(100, ($puntos / 500) * 100); // Máximo 500 puntos para barra
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Cliente - Farmacia Omarcitoia</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
  <div class="cliente-layout">
    <!-- Sidebar -->
    <aside class="cliente-sidebar">
      <div class="sidebar-header">
        <h2>💊 Farmacia</h2>
        <p>Omarcitoia</p>
      </div>
      
      <nav class="sidebar-nav">
        <a href="#inicio" class="nav-item active" data-section="inicio">
          <span class="nav-icon">🏠</span>
          <span>Inicio</span>
        </a>
        <a href="#perfil" class="nav-item" data-section="perfil">
          <span class="nav-icon">👤</span>
          <span>Mi Perfil</span>
        </a>
        <a href="#compras" class="nav-item" data-section="compras">
          <span class="nav-icon">🛒</span>
          <span>Mis Compras</span>
        </a>
        <a href="#puntos" class="nav-item" data-section="puntos">
          <span class="nav-icon">⭐</span>
          <span>Mis Puntos</span>
        </a>
        <a href="#chat" class="nav-item" data-section="chat">
          <span class="nav-icon">💬</span>
          <span>Chat Omarcitoia</span>
        </a>
        <a href="index.php" class="nav-item">
          <span class="nav-icon">🏪</span>
          <span>Catálogo</span>
        </a>
      </nav>
      
      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">👤</div>
          <div class="user-details">
            <div class="user-name"><?= htmlspecialchars($cliente_nombre, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-username">@<?= htmlspecialchars($cliente_username, ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
        <a href="logout.php" class="btn-logout">Cerrar Sesión</a>
      </div>
    </aside>
    
    <!-- Main Content -->
    <main class="cliente-main">
      <!-- Sección Inicio -->
      <section id="inicio-section" class="content-section active">
        <h1 class="section-title">Bienvenido, <?= htmlspecialchars($cliente_nombre, ENT_QUOTES, 'UTF-8') ?>! 👋</h1>
        
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">⭐</div>
            <div class="stat-content">
              <div class="stat-value"><?= $puntos ?></div>
              <div class="stat-label">Puntos Acumulados</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">🛒</div>
            <div class="stat-content">
              <div class="stat-value"><?= count($compras) ?></div>
              <div class="stat-label">Compras Realizadas</div>
            </div>
          </div>
          
          <div class="stat-card">
            <div class="stat-icon">📄</div>
            <div class="stat-content">
              <div class="stat-value"><?= count($boletas) ?></div>
              <div class="stat-label">Boletas Generadas</div>
            </div>
          </div>
        </div>
        
        <div class="quick-actions">
          <h2>Acciones Rápidas</h2>
          <div class="action-cards">
            <a href="index.php" class="action-card">
              <span class="action-icon">🛍️</span>
              <span class="action-text">Ir a Comprar</span>
            </a>
            <a href="#" class="action-card" onclick="showSection('compras'); return false;">
              <span class="action-icon">📋</span>
              <span class="action-text">Ver Historial</span>
            </a>
            <a href="#" class="action-card" onclick="showSection('chat'); return false;">
              <span class="action-icon">💬</span>
              <span class="action-text">Hablar con Omarcitoia</span>
            </a>
          </div>
        </div>
      </section>
      
      <!-- Sección Perfil -->
      <section id="perfil-section" class="content-section">
        <h1 class="section-title">Mi Perfil 👤</h1>
        
        <div class="profile-card">
          <div class="profile-header">
            <div class="profile-avatar">👤</div>
            <div>
              <h2><?= htmlspecialchars($cliente_nombre, ENT_QUOTES, 'UTF-8') ?></h2>
              <p>@<?= htmlspecialchars($cliente_username, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
          
          <div class="profile-info">
            <div class="info-item">
              <span class="info-label">📧 Email:</span>
              <span class="info-value"><?= htmlspecialchars($cliente_data['email'] ?: 'No registrado', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">📱 Teléfono:</span>
              <span class="info-value"><?= htmlspecialchars($cliente_data['telefono'] ?: 'No registrado', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">📍 Dirección:</span>
              <span class="info-value"><?= htmlspecialchars($cliente_data['direccion'] ?: 'No registrado', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>
          
          <button class="btn primary" onclick="editProfile()">Editar Perfil</button>
        </div>
      </section>
      
      <!-- Sección Compras -->
      <section id="compras-section" class="content-section">
        <h1 class="section-title">Historial de Compras 🛒</h1>
        
        <?php if (empty($compras)): ?>
          <div class="empty-state">
            <p>No has realizado compras aún</p>
            <a href="index.php" class="btn primary">Ir a Comprar</a>
          </div>
        <?php else: ?>
          <div class="table-container">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Medicamento</th>
                  <th>Cantidad</th>
                  <th>Precio Unit.</th>
                  <th>Subtotal</th>
                  <th>Fecha</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($compras as $compra): ?>
                  <tr>
                    <td><?= htmlspecialchars($compra['medicamento'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$compra['cantidad'] ?></td>
                    <td>S/ <?= number_format((float)$compra['precio_unitario'], 2) ?></td>
                    <td>S/ <?= number_format((float)$compra['subtotal'], 2) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($compra['fecha'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
      
      <!-- Sección Puntos -->
      <section id="puntos-section" class="content-section">
        <h1 class="section-title">Mis Puntos ⭐</h1>
        
        <div class="puntos-card">
          <div class="puntos-header">
            <div class="puntos-icon">⭐</div>
            <div>
              <div class="puntos-value"><?= $puntos ?></div>
              <div class="puntos-label">Puntos Acumulados</div>
            </div>
          </div>
          
          <div class="puntos-progress">
            <div class="progress-bar-container">
              <div class="progress-bar-fill" style="width: <?= $puntos_percent ?>%"></div>
            </div>
            <p>¡Sigue comprando para acumular más puntos!</p>
          </div>
          
          <div class="puntos-info">
            <h3>¿Cómo funcionan los puntos?</h3>
            <ul>
              <li>Ganas 1 punto por cada S/ 10 de compra</li>
              <li>Los puntos se acumulan automáticamente</li>
              <li>Pronto podrás canjear tus puntos por descuentos</li>
            </ul>
          </div>
        </div>
      </section>
      
      <!-- Sección Chat -->
      <section id="chat-section" class="content-section">
        <h1 class="section-title">Chat con Omarcitoia 💬</h1>
        
        <div class="chat-container">
          <div class="chat-messages" id="chatMessages">
            <div class="chat-message bot">
              <div class="message-avatar">🤖</div>
              <div class="message-content">
                <p>¡Hola! Soy Omarcitoia, tu asistente virtual. ¿En qué puedo ayudarte?</p>
              </div>
            </div>
          </div>
          
          <div class="chat-input">
            <input type="text" id="chatInput" placeholder="Escribe tu pregunta..." />
            <button class="btn-chat-send" onclick="sendChatMessage()">Enviar</button>
            <button class="btn-chat-voice" id="chatVoiceBtn">🎤</button>
          </div>
        </div>
      </section>
    </main>
  </div>
  
  <script src="cliente_panel.js"></script>
</body>
</html>
