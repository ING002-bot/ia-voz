<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function is_thanks(string $q): bool {
    return preg_match('/\b(gracias|muchas gracias|te agradezco|gracias!|gracias\.)\b/u', $q) === 1;
}

function thanks_response(): string {
    $opts = [
        '¡Con gusto! 😊','¡Para eso estoy! 🙌','¡Me alegra ayudarte! ✨','¡Cuando quieras! 😄'
    ];
    return $opts[array_rand($opts)];
}

function is_bye(string $q): bool {
    return preg_match('/\b(adios|adiós|hasta luego|nos vemos|chao)\b/u', $q) === 1;
}

function bye_response(): string {
    $opts = [
        '¡Hasta luego! 👋 Que tengas un gran día.','¡Nos vemos pronto! 🌟','¡Cuídate! 🫶'
    ];
    return $opts[array_rand($opts)];
}

function is_greeting(string $q): bool {
    return preg_match('/\b(hola|buenas|buenos dias|buenos días|buenas tardes|buenas noches|hey|que tal|qué tal)\b/u', $q) === 1;
}

function greeting_response(): string {
    $opts = [
        '¡Hola! 😊 ¿En qué puedo ayudarte hoy?','¡Qué gusto escucharte! 🙌 Dime, ¿qué necesitas?','¡Hola, bienvenido a la farmacia! 🏪 Estoy listo para ayudarte.','¡Hey! 😄 Puedo responder sobre precios, stock y más.','¡Hola! 🌟 Pregúntame por disponibilidad o precio de un medicamento.'
    ];
    return $opts[array_rand($opts)];
}

function normalize(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t !== false) { $s = $t; }
    $s = preg_replace('/[\p{P}¿¡]/u', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function tokenize(string $s): array {
    $stop = ['el','la','los','las','un','una','unos','unas','de','del','al','a','y','o','u','que','para','por','en','con','se','me','mi','su','tu','es','son','hay','tienen','tiene','cuanto','cual','cuales','cualquiera','quiero','necesito','dame','porfavor','porfavor','favor'];
    $parts = preg_split('/\s+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    $out = [];
    foreach ($parts as $p) {
        if (mb_strlen($p, 'UTF-8') < 3) continue;
        if (in_array($p, $stop, true)) continue;
        $out[] = $p;
    }
    return array_values(array_unique($out));
}

function detect_intents(string $q): array {
    $priceSyn = ['precio','cuesta','vale','valor','cuanto sale','cuanto es','cuanto cuesta'];
    $availSyn = ['tienen','hay','disponible','disponibilidad','queda','quedan'];
    $listSyn = ['que tienen','lista','catalogo','catálogo','mostrar'];
    $stockSyn = ['cuantos productos','cuantos tienen','cuanto stock','stock total','total stock','productos en stock'];
    $qsp = ' ' . $q . ' ';
    $hasPrice = false; foreach ($priceSyn as $w) { if (strpos($q, $w) !== false) { $hasPrice = true; break; } }
    $hasAvail = false; foreach ($availSyn as $w) { if (strpos($q, $w) !== false) { $hasAvail = true; break; } }
    $hasList = false; foreach ($listSyn as $w) { if (strpos($q, $w) !== false) { $hasList = true; break; } }
    $hasStock = false; foreach ($stockSyn as $w) { if (strpos($q, $w) !== false) { $hasStock = true; break; } }
    return ['price'=>$hasPrice,'avail'=>$hasAvail,'list'=>$hasList,'stock'=>$hasStock];
}

function is_time_question(string $q): bool {
    return preg_match('/\b(hora|hora exacta|que hora)\b/u', $q) === 1;
}

function is_date_question(string $q): bool {
    return preg_match('/\b(fecha|dia|día|hoy)\b/u', $q) === 1;
}

function format_time_response(): string {
    $dt = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
    return 'Son las ' . $dt->format('H:i');
}

function format_date_response(): string {
    $dt = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
    $dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $diaSemana = $dias[(int)$dt->format('w')];
    $diaMes = (int)$dt->format('j');
    $mes = $meses[(int)$dt->format('n') - 1];
    $anio = $dt->format('Y');
    return "Hoy es $diaSemana, $diaMes de $mes de $anio";
}

function find_medicine_by_name(mysqli $db, string $q): ?array {
    $tokens = tokenize($q);
    $tokens = array_slice($tokens, 0, 6);
    if (!empty($tokens)) {
        $likes = [];
        $params = [];
        $types = '';
        foreach ($tokens as $t) {
            $likes[] = 'nombre LIKE ?';
            $params[] = '%' . $t . '%';
            $types .= 's';
        }
        $sql = 'SELECT id, nombre, descripcion, precio, stock FROM medicamentos WHERE ' . implode(' OR ', $likes) . ' ORDER BY stock DESC, nombre ASC LIMIT 1';
        $stmt = $db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                $row = $res->fetch_assoc();
                $stmt->close();
                if ($row) return $row;
            } else { $stmt->close(); }
        }
    }

    $all = $db->query('SELECT id, nombre, descripcion, precio, stock FROM medicamentos');
    if (!$all) return null;
    $best = null; $bestScore = 0.0;
    $qTokens = $tokens;
    $qStr = $q;
    while ($m = $all->fetch_assoc()) {
        $name = normalize((string)$m['nombre']);
        $nameTokens = tokenize($name);
        $inter = array_intersect($qTokens, $nameTokens);
        $union = array_unique(array_merge($qTokens, $nameTokens));
        $jacc = count($union) > 0 ? (count($inter) / count($union)) : 0.0;
        $lev = 0;
        foreach ($qTokens as $qt) { $lev += levenshtein($qt, $name); }
        $levScore = 1.0 / (1 + $lev);
        $score = (0.7 * $jacc) + (0.3 * $levScore);
        if ($score > $bestScore) { $bestScore = $score; $best = $m; }
    }
    if ($best && $bestScore >= 0.12) return $best;
    return null;
}

function search_by_symptom(mysqli $db, string $q): array {
    $sym = '';
    if (preg_match('/para\s+([\p{L}]+)/u', $q, $m)) {
        $sym = $m[1];
    } elseif (preg_match('/(fiebre|dolor|tos|gripa|gripe|cabeza|estomago|estomago|estomacal|garganta|resfriado|alergia)/u', $q, $m)) {
        $sym = $m[1];
    }
    if ($sym === '') return [];

    $like = '%' . $sym . '%';
    $stmt = $db->prepare('SELECT id, nombre, descripcion, precio, stock FROM medicamentos WHERE descripcion LIKE ? OR nombre LIKE ? ORDER BY stock DESC, nombre ASC LIMIT 5');
    if (!$stmt) return [];
    $stmt->bind_param('ss', $like, $like);
    if (!$stmt->execute()) return [];
    $res = $stmt->get_result();
    $rows = $res->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function answer_from_db(mysqli $db, string $q): string {
    $int = detect_intents($q);
    $priceIntent = $int['price'];
    $availabilityIntent = $int['avail'];

    $med = find_medicine_by_name($db, $q);
    if ($med) {
        $nombre = $med['nombre'];
        $precio = number_format((float)$med['precio'], 2);
        $stock = (int)$med['stock'];
        $disp = $stock > 0 ? 'Sí, tenemos' : 'No tenemos disponible por ahora';
        if ($priceIntent && $availabilityIntent) {
            return "$disp $nombre. Su precio es S/ $precio.";
        } elseif ($priceIntent) {
            return "El $nombre cuesta S/ $precio.";
        } elseif ($availabilityIntent) {
            return $stock > 0
                ? "Sí, tenemos $nombre. Quedan $stock en stock."
                : "No, actualmente no tenemos $nombre en stock.";
        } else {
            $desc = trim((string)$med['descripcion']);
            $base = $stock > 0 ? "Tenemos $nombre en S/ $precio." : "$nombre está agotado actualmente.";
            return $desc !== '' ? "$base Descripción: $desc" : $base;
        }
    }

    $bySym = search_by_symptom($db, $q);
    if (!empty($bySym)) {
        $parts = [];
        foreach ($bySym as $r) {
            $parts[] = $r['nombre'] . ' (S/ ' . number_format((float)$r['precio'], 2) . ', stock ' . (int)$r['stock'] . ')';
        }
        return 'Algunas opciones son: ' . implode('; ', $parts) . '.';
    }

    $res = $db->query('SELECT nombre, precio, stock FROM medicamentos ORDER BY nombre ASC LIMIT 5');
    if ($res && $res->num_rows) {
        $parts = [];
        while ($row = $res->fetch_assoc()) {
            $parts[] = $row['nombre'] . ' (S/ ' . number_format((float)$row['precio'], 2) . ', stock ' . (int)$row['stock'] . ')';
        }
        return 'No entendí el producto exacto. Disponibles: ' . implode('; ', $parts) . '.';
    }

    return 'No encontré información en la base de datos.';
}

// Main
try {
    $body = read_json_body();
    $question = isset($body['question']) ? (string)$body['question'] : '';
    $q = normalize($question);

    if ($q === '') {
        echo json_encode(['text' => 'Por favor, di o escribe tu pregunta.']);
        exit;
    }

    $db = get_db();

    if (is_greeting($q)) {
        $ans = greeting_response();
        $db = get_db();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans]);
        exit;
    }

    if (is_thanks($q)) {
        $ans = thanks_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans]);
        exit;
    }

    if (is_bye($q)) {
        $ans = bye_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans]);
        exit;
    }

    if (is_time_question($q)) {
        $ans = format_time_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans]);
        exit;
    }

    if (is_date_question($q)) {
        $ans = format_date_response();
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans]);
        exit;
    }

    // Total stock intent
    $intents = detect_intents($q);
    if (!empty($intents['stock'])) {
        $res = $db->query('SELECT COUNT(*) AS productos, COALESCE(SUM(stock),0) AS unidades FROM medicamentos WHERE stock > 0');
        $productos = 0; $unidades = 0;
        if ($res && ($r = $res->fetch_assoc())) { $productos = (int)$r['productos']; $unidades = (int)$r['unidades']; }
        $ans = "Tenemos $productos productos en stock con $unidades unidades disponibles.";
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans]);
        exit;
    }

    // If no explicit intent but the text looks like a medicamento, respond with availability + price
    if (function_exists('find_medicine_by_name')) {
        $med = find_medicine_by_name($db, $q);
        if (is_array($med) && !empty($med)) {
            $n = (string)($med['nombre'] ?? '');
            $p = number_format((float)($med['precio'] ?? 0), 2);
            $s = (int)($med['stock'] ?? 0);
            $ans = $s > 0
                ? "Sí, tenemos $n. Precio S/ $p. Stock disponible: $s."
                : "$n no tiene stock en este momento. Precio S/ $p.";
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $ans]);
            exit;
        }
    }

    $answer = answer_from_db($db, $q);
    if (trim($answer) === '' || $answer === 'No encontré información para esa consulta.') {
        $answer = 'Mmm... 🤔 no estoy seguro de eso. Puedo ayudarte con disponibilidad, precios o buscar por síntoma. Por ejemplo: "¿Tienen paracetamol?" o "¿Cuánto cuesta el ibuprofeno?"';
    }
    $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
    if ($stmt) { $stmt->bind_param('ss', $q, $answer); $stmt->execute(); $stmt->close(); }
    echo json_encode(['text' => $answer]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
