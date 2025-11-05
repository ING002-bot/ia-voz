<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$success = '';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $nombre_completo = trim((string)($_POST['nombre_completo'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    $direccion = trim((string)($_POST['direccion'] ?? ''));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    
    // Validaciones
    if ($username === '' || $nombre_completo === '' || $password === '') {
        $error = 'Username, nombre completo y contraseña son obligatorios';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden';
    } else {
        // Verificar si el username ya existe
        $stmt = $db->prepare('SELECT id FROM usuarios_clientes WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows > 0) {
                $error = 'El username ya está en uso';
            } else {
                // Registrar usuario
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $db->prepare('INSERT INTO usuarios_clientes (username, password, nombre_completo, direccion, telefono, email) VALUES (?, ?, ?, ?, ?, ?)');
                if ($insertStmt) {
                    $insertStmt->bind_param('ssssss', $username, $hashed, $nombre_completo, $direccion, $telefono, $email);
                    if ($insertStmt->execute()) {
                        $success = '¡Registro exitoso! Ya puedes iniciar sesión.';
                    } else {
                        $error = 'Error al registrar el usuario';
                    }
                    $insertStmt->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro - Farmacia Omarcitoia</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="auth-container">
    <div class="auth-header">
      <h1>💊 Registro de Usuario</h1>
      <p>Farmacia Omarcitoia</p>
    </div>
    
    <form method="post" class="auth-form" id="registerForm">
      <div class="form-group">
        <label>Username *</label>
        <input type="text" name="username" required minlength="3" placeholder="Ingresa tu username" />
      </div>
      
      <div class="form-group">
        <label>Nombre Completo *</label>
        <input type="text" name="nombre_completo" required placeholder="Ingresa tu nombre completo" />
      </div>
      
      <div class="form-group">
        <label>Contraseña *</label>
        <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres" />
      </div>
      
      <div class="form-group">
        <label>Confirmar Contraseña *</label>
        <input type="password" name="confirm_password" required minlength="6" placeholder="Repite tu contraseña" />
      </div>
      
      <div class="form-group">
        <label>Dirección</label>
        <input type="text" name="direccion" placeholder="Tu dirección (opcional)" />
      </div>
      
      <div class="form-group">
        <label>Teléfono</label>
        <input type="tel" name="telefono" placeholder="Tu teléfono (opcional)" />
      </div>
      
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="Tu email (opcional)" />
      </div>
      
      <button type="submit" class="btn primary">Registrarse</button>
    </form>
    
    <p class="auth-footer">¿Ya tienes cuenta? <a href="login_unified.php">Iniciar sesión</a></p>
    <p class="auth-footer"><a href="index.php">← Volver al inicio</a></p>
  </div>
  
  <script>
    <?php if ($error): ?>
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: '<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>',
        confirmButtonColor: '#FB8C00'
      });
    <?php endif; ?>
    
    <?php if ($success): ?>
      Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '<?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>',
        confirmButtonColor: '#1E88E5'
      }).then(() => {
        window.location.href = 'login_unified.php';
      });
    <?php endif; ?>
  </script>
</body>
</html>
