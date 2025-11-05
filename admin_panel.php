<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ensure_admin();

$db = get_db();

// Handle CRUD actions
$notice = '';
// Ensure 'imagen' column exists (idempotent)
$colChk = $db->query("SHOW COLUMNS FROM medicamentos LIKE 'imagen'");
if ($colChk && $colChk->num_rows === 0) {
    $db->query("ALTER TABLE medicamentos ADD COLUMN imagen VARCHAR(500) NULL");
}
// Ensure UNIQUE index on nombre to avoid duplicates
$idxChk = $db->query("SHOW INDEX FROM medicamentos WHERE Key_name = 'uniq_medicamentos_nombre'");
if ($idxChk && $idxChk->num_rows === 0) {
    $db->query("ALTER TABLE medicamentos ADD UNIQUE KEY uniq_medicamentos_nombre (nombre)");
}
// Ensure uploads directory exists
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $fv = (string)($_POST['fecha_vencimiento'] ?? '');
        // Check duplicate by nombre
        $du = $db->prepare('SELECT id FROM medicamentos WHERE nombre = ? LIMIT 1');
        if ($du) { $du->bind_param('s', $nombre); $du->execute(); $duRes = $du->get_result(); }
        if (isset($duRes) && $duRes && $duRes->num_rows > 0) {
            $notice = 'Ya existe un medicamento con ese nombre. No se ha creado un duplicado.';
        } else {
        // Handle image upload
        $imagenPath = null;
        if (!empty($_FILES['imagen']['name']) && is_uploaded_file($_FILES['imagen']['tmp_name'])) {
            $fname = $_FILES['imagen']['name'];
            $tmp = $_FILES['imagen']['tmp_name'];
            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];
            if (in_array($ext, $allowed, true)) {
                $new = 'med_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . DIRECTORY_SEPARATOR . $new;
                if (@move_uploaded_file($tmp, $dest)) {
                    $imagenPath = 'uploads/' . $new;
                }
            }
        }
        $stmt = $db->prepare('INSERT INTO medicamentos (nombre, descripcion, precio, stock, fecha_vencimiento, imagen) VALUES (?,?,?,?,?,?)');
        if ($stmt) {
            $stmt->bind_param('ssdiss', $nombre, $descripcion, $precio, $stock, $fv, $imagenPath);
            if (!$stmt->execute()) {
                if ($stmt->errno === 1062) {
                    $notice = 'Ya existe un medicamento con ese nombre. No se creó un duplicado.';
                } else {
                    $notice = 'Error al crear el medicamento.';
                }
            } else {
                $notice = 'Medicamento creado.';
            }
            $stmt->close();
        }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $precio = (float)($_POST['precio'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $fv = (string)($_POST['fecha_vencimiento'] ?? '');
        $stmt = $db->prepare('UPDATE medicamentos SET nombre=?, descripcion=?, precio=?, stock=?, fecha_vencimiento=? WHERE id=?');
        if ($stmt) { $stmt->bind_param('ssdisi', $nombre, $descripcion, $precio, $stock, $fv, $id); $stmt->execute(); $stmt->close(); $notice = 'Medicamento actualizado.'; }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM medicamentos WHERE id=?');
        if ($stmt) { $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close(); $notice = 'Medicamento eliminado.'; }
    }
}

// Metrics
$lowStockThreshold = 5;
$stats = [
    'total_items' => 0,
    'total_units' => 0,
    'expiring_30' => 0,
    'low_or_zero' => 0,
];
$res = $db->query('SELECT COUNT(*) AS c, COALESCE(SUM(stock),0) AS u FROM medicamentos');
if ($res && ($r = $res->fetch_assoc())) { $stats['total_items'] = (int)$r['c']; $stats['total_units'] = (int)$r['u']; }
$res = $db->query("SELECT COUNT(*) AS c FROM medicamentos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
if ($res && ($r = $res->fetch_assoc())) { $stats['expiring_30'] = (int)$r['c']; }
$res = $db->query("SELECT COUNT(*) AS c FROM medicamentos WHERE stock <= $lowStockThreshold");
if ($res && ($r = $res->fetch_assoc())) { $stats['low_or_zero'] = (int)$r['c']; }

// Data for table
$rows = [];
$res = $db->query('SELECT id, nombre, descripcion, precio, stock, fecha_vencimiento, imagen FROM medicamentos ORDER BY nombre ASC');
if ($res) { $rows = $res->fetch_all(MYSQLI_ASSOC); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Panel Admin - Farmacia Omarcitoia</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="admin-container">
    <div class="admin-topbar">
      <div class="topbar-left">
        <h1>🛡️ Panel de Administración</h1>
        <small>Hola, <strong><?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin', ENT_QUOTES, 'UTF-8') ?></strong></small>
      </div>
      <div class="topbar-actions">
        <a class="btn btn-outline" href="index.html">Inicio</a>
        <a class="btn btn-danger" href="logout.php">Salir</a>
      </div>
    </div>

    <?php if ($notice): ?>
      <div class="alert"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="admin-layout">
      <aside class="admin-sidebar">
        <h2 class="sidebar-title">📊 Resumen</h2>
        <?php
          $total = max(1, (int)$stats['total_items']);
          $pctExpire = min(100, round(($stats['expiring_30'] / $total) * 100));
          $pctLow = min(100, round(($stats['low_or_zero'] / $total) * 100));
        ?>
        <div class="admin-stats">
          <div class="admin-stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-info">
              <div class="stat-value"><?= (int)$stats['total_items'] ?></div>
              <div class="stat-label">Ítems</div>
            </div>
          </div>
          <div class="admin-stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
              <div class="stat-value"><?= (int)$stats['total_units'] ?></div>
              <div class="stat-label">Unidades</div>
            </div>
          </div>
          <div class="admin-stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-info">
              <div class="stat-value"><?= (int)$stats['expiring_30'] ?></div>
              <div class="stat-label">Por vencer (30d)</div>
              <div class="stat-progress">
                <div class="progress-bar" style="width: <?= $pctExpire ?>%"></div>
              </div>
            </div>
          </div>
          <div class="admin-stat-card danger">
            <div class="stat-icon">🚫</div>
            <div class="stat-info">
              <div class="stat-value"><?= (int)$stats['low_or_zero'] ?></div>
              <div class="stat-label">Bajo/Sin stock</div>
              <div class="stat-progress">
                <div class="progress-bar" style="width: <?= $pctLow ?>%"></div>
              </div>
            </div>
          </div>
        </div>
        <button id="adminMicBtn" class="btn-admin-mic">🎤 Asistente Omarcitoia</button>
        <div id="adminVoiceOut" class="admin-voice-out"></div>
      </aside>

      <section class="admin-content">
        <h2 class="content-title">💊 Inventario de Medicamentos</h2>
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Stock</th><th>Vence</th><th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $m): ?>
              <tr data-row-id="<?= (int)$m['id'] ?>">
                <td><?= (int)$m['id'] ?></td>
                <td class="inline-cell" data-field="nombre"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="inline-cell" data-field="descripcion"><?= htmlspecialchars($m['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="inline-cell" data-field="precio"><?= number_format((float)$m['precio'], 2) ?></td>
                <td class="inline-cell" data-field="stock"><?= (int)$m['stock'] ?></td>
                <td class="inline-cell" data-field="fecha_vencimiento"><?= htmlspecialchars((string)$m['fecha_vencimiento'], ENT_QUOTES, 'UTF-8') ?></td>
                <td class="row-actions d-flex gap-2">
                  <button type="button" class="btn btn-sm btn-secondary js-edit">Modificar</button>
                  <button type="button" class="btn btn-sm btn-success js-save d-none">Guardar</button>
                  <button type="button" class="btn btn-sm btn-outline-light js-cancel d-none">Cancelar</button>
                  <form method="post" class="ms-2">
                    <input type="hidden" name="action" value="delete" />
                    <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
                    <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar?')">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <h3>Nuevo / Editar</h3>
        <form method="post" class="grid grid-3" enctype="multipart/form-data">
          <input type="hidden" name="action" value="create" />
          <label>Nombre
            <input type="text" name="nombre" required />
          </label>
          <label>Precio
            <input type="number" step="0.01" name="precio" required />
          </label>
          <label>Stock
            <input type="number" name="stock" required />
          </label>
          <label class="grid" style="grid-column:1/-1;">Descripción
            <textarea name="descripcion" rows="3"></textarea>
          </label>
          <label>Fecha vencimiento
            <input type="date" name="fecha_vencimiento" />
          </label>
          <label>Imagen (jpg, png, webp)
            <input type="file" name="imagen" accept="image/*" />
          </label>
          <button class="btn primary" style="align-self:end;">Guardar</button>
        </form>
      </section>
    </div>
  </div>

  <script src="admin.js"></script>
</body>
</html>
