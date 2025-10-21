<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    if ($username !== '') {
        $db = get_db();
        $stmt = $db->prepare('SELECT id, email FROM usuarios_admin WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $userId = (int)$row['id'];
                    $token = bin2hex(random_bytes(16));
                    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
                    $ins = $db->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
                    if ($ins) { $ins->bind_param('iss', $userId, $token, $expires); $ins->execute(); $ins->close(); }
                    // En un entorno real, envía un correo con el link de reseteo
                    $link = sprintf('%s/reset.php?token=%s', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'), $token);
                    $base = sprintf('%s://%s%s', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https':'http', $_SERVER['HTTP_HOST'], $link);
                    $msg = 'Se ha generado un enlace de recuperación (demo): ' . htmlspecialchars($base, ENT_QUOTES, 'UTF-8');
                } else {
                    $msg = 'Usuario no encontrado';
                }
            }
            $stmt->close();
        }
    } else {
        $msg = 'Ingrese el usuario';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Recuperar contraseña</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="auth-container">
    <h1>Recuperar contraseña</h1>
    <?php if ($msg): ?>
      <div class="alert"><?= $msg ?></div>
    <?php endif; ?>
    <form method="post" class="auth-form">
      <label>Usuario</label>
      <input type="text" name="username" required />
      <button class="btn primary" type="submit">Generar enlace</button>
    </form>
    <p><a class="btn" href="login.php">Volver</a></p>
  </div>
</body>
</html>
