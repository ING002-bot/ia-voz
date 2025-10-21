<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$db = get_db();
// Ensure table exists and auto-provision default admin if empty
$db->query("CREATE TABLE IF NOT EXISTS usuarios_admin (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$chk = $db->query("SELECT COUNT(*) AS c FROM usuarios_admin");
if ($chk && ($row = $chk->fetch_assoc()) && (int)$row['c'] === 0) {
    $u = 'admin';
    $p = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO usuarios_admin (username, password) VALUES (?, ?)');
    if ($stmt) { $stmt->bind_param('ss', $u, $p); $stmt->execute(); $stmt->close(); }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username !== '' && $password !== '') {
        $stmt = $db->prepare('SELECT id, username, password FROM usuarios_admin WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $_SESSION['admin_id'] = (int)$row['id'];
                        $_SESSION['admin_username'] = $row['username'];
                        header('Location: admin_panel.php');
                        exit;
                    }
                }
            }
            $stmt->close();
        }
        $error = 'Credenciales inválidas';
    } else {
        $error = 'Complete usuario y contraseña';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Administrador</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <div class="auth-container">
    <h1>Administrador</h1>
    <?php if ($error): ?>
      <div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <form method="post" class="auth-form">
      <label>Usuario</label>
      <input type="text" name="username" required />
      <label>Contraseña</label>
      <input type="password" name="password" required />
      <button type="submit" class="btn primary">Ingresar</button>
    </form>
    <p><a href="forgot.php">¿Olvidaste tu contraseña?</a></p>
    <p><a class="btn" href="index.html">Volver</a></p>
  </div>
</body>
</html>
