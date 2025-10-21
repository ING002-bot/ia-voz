<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$db = get_db();
$msg = '';
$token = (string)($_GET['token'] ?? '');
if ($token === '') {
    $msg = 'Token inválido';
} else {
    $stmt = $db->prepare('SELECT pr.id, pr.user_id, ua.username, pr.expires_at FROM password_resets pr JOIN usuarios_admin ua ON ua.id = pr.user_id WHERE pr.token = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $token);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $expires = new DateTime($row['expires_at']);
                if (new DateTime() > $expires) {
                    $msg = 'El enlace ha expirado';
                } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $pass = (string)($_POST['password'] ?? '');
                    if (strlen($pass) < 6) {
                        $msg = 'La contraseña debe tener al menos 6 caracteres';
                    } else {
                        $hash = password_hash($pass, PASSWORD_DEFAULT);
                        $upd = $db->prepare('UPDATE usuarios_admin SET password=? WHERE id=?');
                        if ($upd) { $uid = (int)$row['user_id']; $upd->bind_param('si', $hash, $uid); $upd->execute(); $upd->close(); }
                        $del = $db->prepare('DELETE FROM password_resets WHERE id=?');
                        if ($del) { $rid = (int)$row['id']; $del->bind_param('i', $rid); $del->execute(); $del->close(); }
                        $msg = 'Contraseña actualizada. Ya puedes iniciar sesión.';
                    }
                }
            } else {
                $msg = 'Token no encontrado';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Restablecer contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="auth-container">
    <h1>Restablecer contraseña</h1>
    <?php if ($msg): ?>
      <div class="alert"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($token && (!$msg || strpos($msg, 'actualizada') === false)): ?>
      <form method="post" class="auth-form">
        <label>Nueva contraseña</label>
        <input type="password" name="password" required />
        <button class="btn primary" type="submit">Actualizar</button>
      </form>
    <?php endif; ?>
    <p><a class="btn" href="login.php">Volver</a></p>
  </div>
</body>
</html>
