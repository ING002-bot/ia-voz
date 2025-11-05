<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db.php';

$error = '';
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $remember = isset($_POST['remember']);
    
    if ($username !== '' && $password !== '') {
        // Primero verificar si es admin
        $stmt = $db->prepare('SELECT id, username, password, role FROM usuarios_admin WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $_SESSION['admin_id'] = (int)$row['id'];
                        $_SESSION['admin_username'] = $row['username'];
                        $_SESSION['user_type'] = 'admin';
                        
                        if ($remember) {
                            setcookie('remember_admin', $username, time() + (30 * 24 * 60 * 60), '/');
                        }
                        
                        header('Location: admin_panel.php');
                        exit;
                    }
                }
            }
            $stmt->close();
        }
        
        // Si no es admin, verificar si es cliente
        $stmt = $db->prepare('SELECT id, username, password, nombre_completo FROM usuarios_clientes WHERE username = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        $_SESSION['cliente_id'] = (int)$row['id'];
                        $_SESSION['cliente_username'] = $row['username'];
                        $_SESSION['cliente_nombre'] = $row['nombre_completo'];
                        $_SESSION['user_type'] = 'cliente';
                        
                        if ($remember) {
                            setcookie('remember_client', $username, time() + (30 * 24 * 60 * 60), '/');
                        }
                        
                        header('Location: cliente_panel.php');
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
  <title>Iniciar Sesión - Farmacia Omarcitoia</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <div class="auth-container">
    <div class="auth-header">
      <h1>🏥 Iniciar Sesión</h1>
      <p>Farmacia Omarcitoia</p>
    </div>
    
    <form method="post" class="auth-form">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required placeholder="Ingresa tu username" 
               value="<?= isset($_COOKIE['remember_admin']) ? htmlspecialchars($_COOKIE['remember_admin'], ENT_QUOTES) : (isset($_COOKIE['remember_client']) ? htmlspecialchars($_COOKIE['remember_client'], ENT_QUOTES) : '') ?>" />
      </div>
      
      <div class="form-group">
        <label>Contraseña</label>
        <input type="password" name="password" required placeholder="Ingresa tu contraseña" />
      </div>
      
      <div class="form-group-checkbox">
        <label>
          <input type="checkbox" name="remember" /> Mantener sesión iniciada
        </label>
      </div>
      
      <button type="submit" class="btn primary">Ingresar</button>
    </form>
    
    <p class="auth-footer">¿No tienes cuenta? <a href="register.php">Regístrate aquí</a></p>
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
  </script>
</body>
</html>
