<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ensure_admin();

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function is_greeting(string $q): bool {
    return preg_match('/\b(hola|buenas|buenos dias|buenos días|buenas tardes|buenas noches|hey|que tal|qué tal)\b/u', $q) === 1;
}

function greeting_response(): string {
    $opts = [
        '¡Hola! 👋 Soy Omarcitoia, tu asistente administrativo. ¿Qué necesitas revisar hoy?',
        '¡Bienvenido al panel administrativo! 😄 Soy Omarcitoia y puedo ayudarte con reportes de stock, vencimientos, ventas y más.',
        '¡Hola! 🗂️ Soy Omarcitoia. ¿Quieres que revisemos productos por vencer, stock bajo o algo específico?',
        '¡Qué gusto verte! 😊 Soy Omarcitoia, listo para ayudarte con la gestión del inventario. ¿Qué necesitas?',
        '¡Hey administrador! 👋 Omarcitoia a tu servicio. Puedo darte información sobre el inventario, ventas y más.'
    ];
    return $opts[array_rand($opts)];
}

function is_thanks(string $q): bool { return preg_match('/\b(gracias|muchas gracias|te agradezco)\b/u', $q) === 1; }
function thanks_response(): string { 
    $o=[
        '¡Con gusto! 🙌 Siempre a tu servicio.',
        '¡Para servirte! ✅ Cuando necesites algo más, aquí estaré.',
        '¡Hecho! 😉 Me alegra poder ayudarte con la gestión.',
        '¡Encantado de ayudar! 😊 Estoy para facilitarte el trabajo.'
    ]; 
    return $o[array_rand($o)]; 
}
function is_bye(string $q): bool { return preg_match('/\b(adios|adiós|hasta luego|nos vemos|chao)\b/u', $q) === 1; }
function bye_response(): string { 
    $o=[
        '¡Hasta luego! 👋 Que tengas un excelente día gestionando la farmacia.',
        '¡Nos vemos! 🧾 Cualquier cosa que necesites, aquí estaré.',
        '¡Que tengas un gran día! 🌟 Mucho éxito con las ventas.',
        '¡Adiós! 😊 Nos vemos pronto. Seguiré vigilando el inventario.'
    ]; 
    return $o[array_rand($o)]; 
}

function normalize(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t !== false) { $s = $t; }
    $s = preg_replace('/[\p{P}¿¡]/u', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

try {
    $body = read_json_body();
    $q = normalize((string)($body['question'] ?? ''));
    if ($q === '') { echo json_encode(['text' => 'Di o escribe tu consulta de administración.']); exit; }

    $db = get_db();
    // Ensure optional tables exist to avoid exceptions on hosts donde no se ejecutó el schema actualizado
    @$db->query("CREATE TABLE IF NOT EXISTS consultas_historial (
      id INT AUTO_INCREMENT PRIMARY KEY,
      user_type ENUM('client','admin') NOT NULL,
      question TEXT NOT NULL,
      answer TEXT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @$db->query("CREATE TABLE IF NOT EXISTS ventas (
      id INT AUTO_INCREMENT PRIMARY KEY,
      medicamento_id INT NOT NULL,
      cantidad INT NOT NULL,
      total DECIMAL(10,2) NOT NULL,
      fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Small-talk primero
    if (is_greeting($q)) {
        $text = greeting_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }
    if (is_thanks($q)) {
        $text = thanks_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }
    if (is_bye($q)) {
        $text = bye_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // Intents básicos admin
    // 1) Por vencer: múltiples variaciones
    if (preg_match('/(vencer|vencen|vencimiento|vencidos|expiran|expira|caducidad|caduca|proximos a vencer|productos vencidos|medicamentos vencidos)/', $q)) {
        $res = $db->query("SELECT COUNT(*) AS c FROM medicamentos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $c = ($res && ($r = $res->fetch_assoc())) ? (int)$r['c'] : 0;
        
        if ($c > 0) {
            $responses = [
                "¡Atención! ⚠️ Hay $c productos que vencen en 30 días o menos. Te recomiendo revisar el inventario pronto.",
                "Tengo una alerta para ti: $c productos están por vencer en los próximos 30 días. 📅 ¿Quieres que te diga cuáles son?",
                "Encontré $c medicamentos que vencerán en 30 días o menos. ⚠️ Es importante gestionarlos pronto."
            ];
        } else {
            $responses = [
                "¡Excelente! 🎉 No hay productos por vencer en los próximos 30 días. Todo está bajo control.",
                "¡Buenas noticias! 😊 No tienes productos próximos a vencer. El inventario está en buen estado."
            ];
        }
        $text = $responses[array_rand($responses)];
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 2) Sin stock o bajo stock - patrones mejorados
    if (preg_match('/(sin stock|sin existencia|agotados|agotado|bajo stock|bajo en stock|stock bajo|falta stock|faltan productos|inventario bajo|productos sin stock|que esta agotado|que falta)/', $q)) {
        $res = $db->query("SELECT nombre, stock FROM medicamentos WHERE stock <= 0 ORDER BY nombre ASC");
        $agotados = [];
        if ($res) { while ($row = $res->fetch_assoc()) { $agotados[] = $row['nombre']; } }
        if (!empty($agotados)) {
            $count = count($agotados);
            $text = "¡Alerta! 🚨 Hay $count productos agotados: " . implode(', ', $agotados) . '. Te recomiendo reabastecer pronto.';
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $text]); exit;
        }
        $res2 = $db->query("SELECT nombre, stock FROM medicamentos WHERE stock > 0 AND stock <= 5 ORDER BY stock ASC");
        $bajos = [];
        if ($res2) { while ($row = $res2->fetch_assoc()) { $bajos[] = $row['nombre'] . ' (' . (int)$row['stock'] . ' unidades)'; } }
        if (!empty($bajos)) {
            $count = count($bajos);
            $text = "⚠️ Atención: $count productos con bajo stock: " . implode(', ', $bajos) . '. Considera hacer un pedido.';
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $text]); exit;
        }
        $responses = [
            '¡Excelente! 😊 No hay productos sin stock ni con bajo stock. El inventario está bien abastecido.',
            '¡Buenas noticias! 🎉 Todos los productos tienen stock adecuado. Todo está bajo control.'
        ];
        $text = $responses[array_rand($responses)];
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 3) Totales - patrones ampliados
    if (preg_match('/(total|totales|cuantos productos|cuantos medicamentos|cantidad de productos|inventario completo|todos los productos|cuantos items|cuantas unidades|resumen del inventario|estadisticas|estadísticas)/', $q)) {
        $res = $db->query('SELECT COUNT(*) AS c, COALESCE(SUM(stock),0) AS u FROM medicamentos');
        $c = 0; $u = 0;
        if ($res && ($r = $res->fetch_assoc())) { $c = (int)$r['c']; $u = (int)$r['u']; }
        $responses = [
            "Perfecto, te cuento: 📊 Tienes $c productos diferentes en el catálogo, con un total de $u unidades en inventario.",
            "¡Aquí están los datos! 📈 Hay $c productos y $u unidades en total. ¿Necesitas más detalles?",
            "Según el inventario actual: $c tipos de productos y $u unidades disponibles. 📦 ¿Quieres saber algo más específico?"
        ];
        $text = $responses[array_rand($responses)];
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 4) Ventas: totales y resumen - patrones mejorados
    if (preg_match('/(ventas|vendido|venta|ingresos|ganancias|monto vendido|resumen de ventas|cuanto vendimos|cuanto hemos vendido|dinero generado|transacciones)/', $q)) {
        // If ventas table is empty or missing, query will still work thanks a la creación IF NOT EXISTS arriba
        $res = $db->query('SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS m FROM ventas');
        $n = 0; $m = 0.0; if ($res && ($r = $res->fetch_assoc())) { $n = (int)$r['n']; $m = (float)$r['m']; }
        if ($n > 0) {
            $responses = [
                "¡Excelente! 💰 Tienes $n ventas registradas con un total de S/ " . number_format($m, 2) . ". ¡Buen trabajo!",
                "Aquí está el resumen de ventas: 📈 $n transacciones completadas por un monto total de S/ " . number_format($m, 2) . ".",
                "Datos de ventas: 📊 Se registraron $n ventas generando S/ " . number_format($m, 2) . " en total. ¿Quieres más detalles?"
            ];
        } else {
            $responses = [
                "Todavía no hay ventas registradas en el sistema. 📊 ¿Necesitas ayuda con algo más?",
                "No se han registrado ventas aún. 📈 El sistema está listo para cuando comiences a vender."
            ];
        }
        $text = $responses[array_rand($responses)];
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 5) Consultas específicas de productos por nombre
    if (preg_match('/(precio de|precio del|cuanto cuesta|valor de|stock de|stock del|existencia de|tenemos|hay|disponibilidad de)\s+([\w\s]+)/', $q, $matches)) {
        $nombreBuscado = trim($matches[2]);
        // Buscar el producto
        $stmt = $db->prepare('SELECT nombre, precio, stock, fecha_vencimiento FROM medicamentos WHERE nombre LIKE ? LIMIT 1');
        $like = '%' . $nombreBuscado . '%';
        $encontrado = false;
        if ($stmt) {
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $encontrado = true;
                $n = $row['nombre'];
                $p = number_format((float)$row['precio'], 2);
                $s = (int)$row['stock'];
                $fv = $row['fecha_vencimiento'] ? date('d/m/Y', strtotime($row['fecha_vencimiento'])) : 'Sin fecha';
                
                $responses = [
                    "Te cuento sobre $n: 📊 Precio S/ $p, Stock: $s unidades, Vence: $fv. ¿Necesitas modificar algo?",
                    "Aquí está la info de $n: 💊 Tenemos $s unidades a S/ $p cada una. Fecha de vencimiento: $fv.",
                    "Datos de $n: Precio S/ $p, $s unidades disponibles, vencimiento $fv. ¿Quieres saber algo más?"
                ];
                $text = $responses[array_rand($responses)];
            }
            $stmt->close();
        }
        
        if ($encontrado) {
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $text]); exit;
        }
    }
    
    // 6) Lista de productos - mostrar algunos productos
    if (preg_match('/(lista de productos|muestra productos|muestrame productos|que productos hay|listar productos|ver productos|catalogo|catálogo|inventario)/', $q)) {
        $res = $db->query('SELECT nombre, stock, precio FROM medicamentos ORDER BY nombre ASC LIMIT 10');
        $productos = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $productos[] = $row['nombre'] . ' (' . (int)$row['stock'] . ' uds, S/ ' . number_format((float)$row['precio'], 2) . ')';
            }
        }
        if (!empty($productos)) {
            $text = "Aquí te muestro algunos productos del inventario: 📋\n\n" . implode(', ', $productos) . ".\n\n¿Quieres detalles de alguno en específico?";
        } else {
            $text = 'No hay productos en el inventario actualmente. 📦';
        }
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }
    
    // 7) Categorías
    if (preg_match('/(categorias|categorías|que categorias|tipos de productos|grupos de productos)/', $q)) {
        $res = $db->query('SELECT categoria, COUNT(*) as cantidad FROM medicamentos WHERE categoria IS NOT NULL GROUP BY categoria ORDER BY cantidad DESC');
        $categorias = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $categorias[] = $row['categoria'] . ' (' . (int)$row['cantidad'] . ' productos)';
            }
        }
        if (!empty($categorias)) {
            $text = "Tenemos productos en estas categorías: 🏷️\n\n" . implode(', ', $categorias) . ".\n\n¿Te gustaría ver productos de alguna categoría específica?";
        } else {
            $text = 'No hay categorías configuradas en el sistema. 📂';
        }
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }
    
    // 8) Productos más caros/baratos
    if (preg_match('/(mas caro|más caro|mas barato|más barato|producto caro|producto barato|precio alto|precio bajo)/', $q)) {
        if (strpos($q, 'caro') !== false) {
            $res = $db->query('SELECT nombre, precio, stock FROM medicamentos ORDER BY precio DESC LIMIT 5');
            $titulo = "Los productos más caros son:";
        } else {
            $res = $db->query('SELECT nombre, precio, stock FROM medicamentos ORDER BY precio ASC LIMIT 5');
            $titulo = "Los productos más económicos son:";
        }
        $productos = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $productos[] = $row['nombre'] . ' (S/ ' . number_format((float)$row['precio'], 2) . ', stock: ' . (int)$row['stock'] . ')';
            }
        }
        if (!empty($productos)) {
            $text = "$titulo 💰\n\n" . implode(', ', $productos) . ".";
        } else {
            $text = 'No hay productos para mostrar. 📊';
        }
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }
    
    // 9) Resumen completo del sistema
    if (preg_match('/(resumen|resumen general|resumen completo|estado del sistema|como esta el sistema|como está todo|panorama general|overview)/', $q)) {
        $resTotal = $db->query('SELECT COUNT(*) AS c, COALESCE(SUM(stock),0) AS u FROM medicamentos');
        $total_prod = 0; $total_units = 0;
        if ($resTotal && ($r = $resTotal->fetch_assoc())) { $total_prod = (int)$r['c']; $total_units = (int)$r['u']; }
        
        $resVencer = $db->query("SELECT COUNT(*) AS c FROM medicamentos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $vencer = 0;
        if ($resVencer && ($r = $resVencer->fetch_assoc())) { $vencer = (int)$r['c']; }
        
        $resBajo = $db->query("SELECT COUNT(*) AS c FROM medicamentos WHERE stock <= 5");
        $bajo = 0;
        if ($resBajo && ($r = $resBajo->fetch_assoc())) { $bajo = (int)$r['c']; }
        
        $resVentas = $db->query('SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS m FROM ventas');
        $num_ventas = 0; $monto_ventas = 0.0;
        if ($resVentas && ($r = $resVentas->fetch_assoc())) { $num_ventas = (int)$r['n']; $monto_ventas = (float)$r['m']; }
        
        $text = "📊 RESUMEN COMPLETO DEL SISTEMA:\n\n";
        $text .= "📦 Inventario: $total_prod productos diferentes, $total_units unidades totales\n";
        $text .= "⚠️ Por vencer (30 días): $vencer productos\n";
        $text .= "🚨 Stock bajo (≤5): $bajo productos\n";
        $text .= "💰 Ventas: $num_ventas transacciones, S/ " . number_format($monto_ventas, 2) . " total\n\n";
        
        if ($vencer > 0 || $bajo > 0) {
            $text .= "⚠️ Atención: Hay productos que requieren tu atención.";
        } else {
            $text .= "✅ Todo está en orden. ¡Buen trabajo!";
        }
        
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }
    
    // 10) Alertas por correo (stub)
    if (preg_match('/(alerta|alertas|correo|email|notificacion|notificación)/', $q)) {
        // Aquí podrías integrar un envío real con PHPMailer/SMTP según configuración.
        $responses = [
            '¡Entendido! 📧 Estoy preparando un resumen completo para enviarlo por correo a los administradores. Te avisaré cuando esté listo.',
            'Perfecto. 📨 Generaré un reporte y lo enviaré a los correos configurados. ¿Necesitas algo más mientras tanto?',
            '¡Por supuesto! ✉️ Prepararé las alertas y las enviaré a tu equipo administrativo.'
        ];
        $text = $responses[array_rand($responses)];
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // Fallback: métricas simples disponibles con más opciones
    $fallbacks = [
        'Mmm... 🤔 No estoy seguro de entender eso. Puedo ayudarte con:\n\n📊 Inventario: totales, stock bajo, productos por vencer\n💰 Ventas: resumen, transacciones\n🔍 Consultas: productos específicos, categorías, precios\n📋 Listas: productos más caros/baratos\n📈 Resumen completo del sistema\n\n¿Qué necesitas?',
        'Disculpa, no comprendí bien. 😅 Prueba preguntarme:\n• "¿Cuáles productos están por vencer?"\n• "Muéstrame el resumen del sistema"\n• "¿Qué productos están sin stock?"\n• "Precio del Paracetamol"\n• "Lista de productos"\n• "Resumen de ventas"',
        'Lo siento, no capté eso. 😊 Puedo ayudarte con muchas cosas del panel administrativo. Algunas ideas:\n\n🔍 Buscar productos específicos\n📊 Ver estadísticas completas\n⚠️ Revisar alertas de stock\n💊 Listar medicamentos\n📈 Analizar ventas\n\nIntenta reformular tu pregunta.'
    ];
    $text = $fallbacks[array_rand($fallbacks)];
    $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
    if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
    echo json_encode(['text' => $text]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno admin']);
}
