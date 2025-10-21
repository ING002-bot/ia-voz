-- Crear base de datos y tabla de ejemplo para farmacia_db
CREATE DATABASE IF NOT EXISTS farmacia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE farmacia_db;

CREATE TABLE IF NOT EXISTS medicamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO medicamentos (nombre, descripcion, precio, stock) VALUES
('Paracetamol 500mg', 'Analgésico y antipirético. Útil para dolor leve a moderado y fiebre.', 2.50, 30),
('Ibuprofeno 400mg', 'Antiinflamatorio no esteroideo. Indicado para dolor, inflamación y fiebre.', 3.90, 20),
('Amoxicilina 500mg', 'Antibiótico. Usado para tratar diversas infecciones bacterianas.', 8.50, 12),
('Loratadina 10mg', 'Antihistamínico. Alivia síntomas de alergia.', 4.20, 15),
('Sales de Rehidratación Oral', 'Solución para reponer líquidos y electrolitos en diarrea.', 1.80, 40);
