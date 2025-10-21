-- Crear base de datos para la app (ajustada a db.php: omarcitoia)
CREATE DATABASE IF NOT EXISTS omarcitoia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omarcitoia;

CREATE TABLE IF NOT EXISTS medicamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  fecha_vencimiento DATE NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO medicamentos (nombre, descripcion, precio, stock, fecha_vencimiento) VALUES
('Paracetamol 500mg', 'Analgésico y antipirético. Útil para dolor leve a moderado y fiebre.', 2.50, 30, DATE_ADD(CURDATE(), INTERVAL 120 DAY)),
('Ibuprofeno 400mg', 'Antiinflamatorio no esteroideo. Indicado para dolor, inflamación y fiebre.', 3.90, 20, DATE_ADD(CURDATE(), INTERVAL 60 DAY)),
('Amoxicilina 500mg', 'Antibiótico. Usado para tratar diversas infecciones bacterianas.', 8.50, 12, DATE_ADD(CURDATE(), INTERVAL 25 DAY)),
('Loratadina 10mg', 'Antihistamínico. Alivia síntomas de alergia.', 4.20, 15, DATE_ADD(CURDATE(), INTERVAL 200 DAY)),
('Sales de Rehidratación Oral', 'Solución para reponer líquidos y electrolitos en diarrea.', 1.80, 40, DATE_ADD(CURDATE(), INTERVAL 400 DAY));

-- Tabla de administradores (login.php también la crea si no existe y provisiona un admin por defecto)
CREATE TABLE IF NOT EXISTS usuarios_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(190) NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial de consultas (cliente/admin)
CREATE TABLE IF NOT EXISTS consultas_historial (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('client','admin') NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ventas simples
CREATE TABLE IF NOT EXISTS ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicamento_id INT NOT NULL,
  cantidad INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_venta_medicamento FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Restablecimiento de contraseña
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES usuarios_admin(id) ON DELETE CASCADE,
  INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


