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
        '¡Hola! 👋 ¿Qué reporte necesitas hoy?','¡Bienvenido al panel! 😄 Puedo ayudarte con stock, vencimientos y ventas.','¡Hola! 🗂️ ¿Revisamos productos por vencer o el total de unidades?'
    ];
    return $opts[array_rand($opts)];
}

function is_thanks(string $q): bool { return preg_match('/\b(gracias|muchas gracias|te agradezco)\b/u', $q) === 1; }
function thanks_response(): string { $o=['¡Con gusto! 🙌','¡Para servirte! ✅','¡Hecho! 😉']; return $o[array_rand($o)]; }
function is_bye(string $q): bool { return preg_match('/\b(adios|adiós|hasta luego|nos vemos|chao)\b/u', $q) === 1; }
function bye_response(): string { $o=['¡Hasta luego! 👋','¡Nos vemos! 🧾','¡Que tengas un gran día! 🌟']; return $o[array_rand($o)]; }

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
    // 1) Por vencer: "cuantos productos estan por vencer" (30 días)
    if (preg_match('/(por vencer|vencen|vencimiento|expiran|expira)/', $q)) {
        $res = $db->query("SELECT COUNT(*) AS c FROM medicamentos WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
        $c = ($res && ($r = $res->fetch_assoc())) ? (int)$r['c'] : 0;
        $text = "Hay $c productos que vencen en 30 días o menos.";
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 2) Sin stock o bajo stock
    if (preg_match('/(sin stock|sin existencia|agotados|bajo stock|bajo en stock)/', $q)) {
        $res = $db->query("SELECT nombre, stock FROM medicamentos WHERE stock <= 0 ORDER BY nombre ASC");
        $agotados = [];
        if ($res) { while ($row = $res->fetch_assoc()) { $agotados[] = $row['nombre']; } }
        if (!empty($agotados)) {
            $text = 'Agotados: ' . implode(', ', $agotados) . '.';
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $text]); exit;
        }
        $res2 = $db->query("SELECT nombre, stock FROM medicamentos WHERE stock > 0 AND stock <= 5 ORDER BY stock ASC");
        $bajos = [];
        if ($res2) { while ($row = $res2->fetch_assoc()) { $bajos[] = $row['nombre'] . ' (' . (int)$row['stock'] . ')'; } }
        if (!empty($bajos)) {
            $text = 'Bajo stock: ' . implode(', ', $bajos) . '.';
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $text]); exit;
        }
        $text = 'No hay productos sin stock ni con bajo stock.';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 3) Totales
    if (preg_match('/(total de productos|total de items|total de ítems|cuantos productos)/', $q)) {
        $res = $db->query('SELECT COUNT(*) AS c, COALESCE(SUM(stock),0) AS u FROM medicamentos');
        $c = 0; $u = 0;
        if ($res && ($r = $res->fetch_assoc())) { $c = (int)$r['c']; $u = (int)$r['u']; }
        $text = "Hay $c productos y $u unidades en total.";
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 4) Ventas: totales y resumen
    if (preg_match('/(ventas|total vendido|monto vendido|resumen de ventas)/', $q)) {
        // If ventas table is empty or missing, query will still work thanks a la creación IF NOT EXISTS arriba
        $res = $db->query('SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS m FROM ventas');
        $n = 0; $m = 0.0; if ($res && ($r = $res->fetch_assoc())) { $n = (int)$r['n']; $m = (float)$r['m']; }
        $text = "Hay $n ventas registradas por un total de S/ " . number_format($m, 2);
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // 5) Alertas por correo (stub)
    if (preg_match('/(alerta|alertas|correo|email)/', $q)) {
        // Aquí podrías integrar un envío real con PHPMailer/SMTP según configuración.
        $text = 'Se preparará un resumen para enviar por correo a los administradores configurados.';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $text]); exit;
    }

    // Fallback: métricas simples disponibles
    $text = 'Puedes preguntar: ¿Cuántos productos están por vencer?, ¿Muéstrame los sin stock?, ¿Dime el total de productos?, ¿Resumen de ventas?, ¿Enviar alertas por correo?';
    $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("admin", ?, ?)');
    if ($stmt) { $stmt->bind_param('ss', $q, $text); $stmt->execute(); $stmt->close(); }
    echo json_encode(['text' => $text]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno admin']);
}
