-- ============================================
-- SCHEMA COMPLETO - FARMACIA VIRTUAL
-- Base de datos completa con productos y categorías
-- ============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS omarcitoia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE omarcitoia;

-- ============================================
-- TABLA: medicamentos
-- ============================================
DROP TABLE IF EXISTS ventas;
DROP TABLE IF EXISTS medicamentos;

CREATE TABLE medicamentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(255) NOT NULL,
  descripcion TEXT,
  precio DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  fecha_vencimiento DATE NULL,
  imagen VARCHAR(500) NULL,
  categoria VARCHAR(100) NULL DEFAULT 'General',
  INDEX idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INSERTAR PRODUCTOS POR CATEGORÍA
-- ============================================

INSERT INTO medicamentos (nombre, descripcion, precio, stock, fecha_vencimiento, imagen, categoria) VALUES
-- ANALGÉSICOS Y ANTIINFLAMATORIOS
('Paracetamol 500mg', 'Analgésico y antipirético. Útil para dolor leve a moderado y fiebre.', 2.50, 30, DATE_ADD(CURDATE(), INTERVAL 120 DAY), 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&h=400&fit=crop&q=80', 'Analgésicos'),
('Ibuprofeno 400mg', 'Antiinflamatorio no esteroideo. Indicado para dolor, inflamación y fiebre.', 3.90, 20, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=500&h=400&fit=crop&q=80', 'Analgésicos'),
('Diclofenaco 50mg', 'Antiinflamatorio potente. Alivia dolor e inflamación intensa.', 4.80, 19, DATE_ADD(CURDATE(), INTERVAL 110 DAY), 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=400&fit=crop&q=80', 'Analgésicos'),
('Aspirina 100mg', 'Antiagregante plaquetario. Previene eventos cardiovasculares.', 3.20, 28, DATE_ADD(CURDATE(), INTERVAL 150 DAY), 'https://images.unsplash.com/photo-1550572017-4a6a5e3c8c3f?w=500&h=400&fit=crop&q=80', 'Analgésicos'),

-- ANTIBIÓTICOS
('Amoxicilina 500mg', 'Antibiótico. Usado para tratar diversas infecciones bacterianas.', 8.50, 12, DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=500&h=400&fit=crop&q=80', 'Antibióticos'),
('Azitromicina 500mg', 'Antibiótico macrólido. Infecciones respiratorias y de piel.', 11.20, 14, DATE_ADD(CURDATE(), INTERVAL 70 DAY), 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=400&fit=crop&q=80&sat=-10', 'Antibióticos'),
('Ciprofloxacino 500mg', 'Antibiótico de amplio espectro. Infecciones urinarias y gastrointestinales.', 9.80, 16, DATE_ADD(CURDATE(), INTERVAL 85 DAY), 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=500&h=400&fit=crop&q=80&sat=-10', 'Antibióticos'),

-- ANTIHISTAMÍNICOS
('Loratadina 10mg', 'Antihistamínico. Alivia síntomas de alergia.', 4.20, 15, DATE_ADD(CURDATE(), INTERVAL 200 DAY), 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&h=400&fit=crop&q=80&hue=20', 'Antihistamínicos'),
('Cetirizina 10mg', 'Antihistamínico de segunda generación. Alivia alergias sin somnolencia.', 4.50, 22, DATE_ADD(CURDATE(), INTERVAL 220 DAY), 'https://images.unsplash.com/photo-1550572017-4a6a5e3c8c3f?w=500&h=400&fit=crop&q=80&hue=20', 'Antihistamínicos'),
('Desloratadina 5mg', 'Antihistamínico avanzado. Control efectivo de rinitis alérgica.', 5.80, 18, DATE_ADD(CURDATE(), INTERVAL 210 DAY), 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=400&fit=crop&q=80&hue=20', 'Antihistamínicos'),

-- GASTROINTESTINALES
('Omeprazol 20mg', 'Inhibidor de la bomba de protones. Reduce la acidez estomacal.', 5.50, 25, DATE_ADD(CURDATE(), INTERVAL 180 DAY), 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=500&h=400&fit=crop&q=80&hue=40', 'Gastrointestinales'),
('Ranitidina 150mg', 'Antiácido. Reduce la producción de ácido gástrico.', 3.60, 26, DATE_ADD(CURDATE(), INTERVAL 160 DAY), 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&h=400&fit=crop&q=80&hue=40', 'Gastrointestinales'),
('Sales de Rehidratación Oral', 'Solución para reponer líquidos y electrolitos en diarrea.', 1.80, 40, DATE_ADD(CURDATE(), INTERVAL 400 DAY), 'https://images.unsplash.com/photo-1550572017-4a6a5e3c8c3f?w=500&h=400&fit=crop&q=80&hue=40', 'Gastrointestinales'),

-- CARDIOVASCULARES
('Atorvastatina 20mg', 'Estatina. Reduce el colesterol y previene enfermedades cardiovasculares.', 9.50, 16, DATE_ADD(CURDATE(), INTERVAL 100 DAY), 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=500&h=400&fit=crop&q=80&hue=200', 'Cardiovasculares'),
('Losartán 50mg', 'Antihipertensivo. Control de la presión arterial alta.', 6.90, 24, DATE_ADD(CURDATE(), INTERVAL 140 DAY), 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=400&fit=crop&q=80&hue=200', 'Cardiovasculares'),
('Captopril 25mg', 'Inhibidor de la ECA. Tratamiento de hipertensión arterial.', 5.40, 21, DATE_ADD(CURDATE(), INTERVAL 130 DAY), 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=500&h=400&fit=crop&q=80&hue=200', 'Cardiovasculares'),
('Enalapril 10mg', 'Antihipertensivo. Protección cardiovascular y renal.', 6.20, 20, DATE_ADD(CURDATE(), INTERVAL 125 DAY), 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&h=400&fit=crop&q=80&hue=200', 'Cardiovasculares'),

-- DIABETES
('Metformina 850mg', 'Antidiabético oral. Control de glucosa en diabetes tipo 2.', 7.20, 18, DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'https://images.unsplash.com/photo-1550572017-4a6a5e3c8c3f?w=500&h=400&fit=crop&q=80&hue=280', 'Diabetes'),
('Glibenclamida 5mg', 'Hipoglucemiante oral. Estimula la producción de insulina.', 5.90, 15, DATE_ADD(CURDATE(), INTERVAL 95 DAY), 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=400&fit=crop&q=80&hue=280', 'Diabetes'),

-- VITAMINAS
('Vitamina C 1000mg', 'Suplemento vitamínico. Fortalece el sistema inmunológico.', 6.80, 35, DATE_ADD(CURDATE(), INTERVAL 300 DAY), 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=500&h=400&fit=crop&q=80&hue=60', 'Vitaminas'),
('Complejo B', 'Suplemento de vitaminas del complejo B. Energía y salud nerviosa.', 8.90, 32, DATE_ADD(CURDATE(), INTERVAL 350 DAY), 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=500&h=400&fit=crop&q=80&hue=60', 'Vitaminas'),
('Vitamina D3 2000 UI', 'Suplemento esencial. Fortalece huesos y sistema inmune.', 12.50, 28, DATE_ADD(CURDATE(), INTERVAL 380 DAY), 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=500&h=400&fit=crop&q=80&hue=60', 'Vitaminas'),

-- RESPIRATORIOS
('Salbutamol Inhalador', 'Broncodilatador. Alivia síntomas de asma y broncoespasmo.', 15.80, 11, DATE_ADD(CURDATE(), INTERVAL 240 DAY), 'https://images.unsplash.com/photo-1550572017-4a6a5e3c8c3f?w=500&h=400&fit=crop&q=80&hue=180', 'Respiratorios'),
('Ambroxol 30mg', 'Mucolítico. Facilita la expectoración en enfermedades respiratorias.', 4.90, 24, DATE_ADD(CURDATE(), INTERVAL 170 DAY), 'https://images.unsplash.com/photo-1587854692152-cbe660dbde88?w=500&h=400&fit=crop&q=80&hue=180', 'Respiratorios'),

-- NEUROLÓGICOS
('Clonazepam 2mg', 'Ansiolítico y anticonvulsivo. Tratamiento de ansiedad y epilepsia.', 12.50, 10, DATE_ADD(CURDATE(), INTERVAL 80 DAY), 'https://images.unsplash.com/photo-1631549916768-4119b2e5f926?w=500&h=400&fit=crop&q=80', 'Neurológicos'),

-- CORTICOIDES
('Dexametasona 4mg', 'Corticoide potente. Antiinflamatorio e inmunosupresor.', 7.60, 17, DATE_ADD(CURDATE(), INTERVAL 95 DAY), 'https://images.unsplash.com/photo-1471864190281-a93a3070b6de?w=500&h=400&fit=crop&q=80', 'Corticoides');

-- ============================================
-- TABLA: usuarios_admin
-- ============================================
CREATE TABLE IF NOT EXISTS usuarios_admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(190) NULL,
  role VARCHAR(32) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLA: consultas_historial
-- ============================================
CREATE TABLE IF NOT EXISTS consultas_historial (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type ENUM('client','admin') NOT NULL,
  question TEXT NOT NULL,
  answer TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLA: ventas
-- ============================================
CREATE TABLE IF NOT EXISTS ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  medicamento_id INT NOT NULL,
  cantidad INT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_venta_medicamento FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABLA: password_resets
-- ============================================
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(128) NOT NULL,
  expires_at DATETIME NOT NULL,
  CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES usuarios_admin(id) ON DELETE CASCADE,
  INDEX (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- RESUMEN
-- ============================================
SELECT 
  'Base de datos creada exitosamente!' as Estado,
  COUNT(*) as Total_Productos 
FROM medicamentos;

SELECT 
  categoria as Categoria,
  COUNT(*) as Cantidad_Productos
FROM medicamentos
GROUP BY categoria
ORDER BY categoria;
