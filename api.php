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
        '¡Con gusto! 😊 Estoy aquí siempre que me necesites.',
        '¡Para eso estoy! 🙌 Es un placer ayudarte.',
        '¡Me alegra mucho ayudarte! ✨ No dudes en consultarme cuando quieras.',
        '¡Cuando quieras! 😄 Siempre es un gusto atenderte.',
        '¡De nada! 🌟 Recuerda que estoy aquí para lo que necesites.',
        '¡Encantado de ayudarte! 💙 Vuelve cuando quieras.'
    ];
    return $opts[array_rand($opts)];
}

function is_bye(string $q): bool {
    return preg_match('/\b(adios|adiós|hasta luego|nos vemos|chao)\b/u', $q) === 1;
}

function bye_response(): string {
    $opts = [
        '¡Hasta luego! 👋 Que tengas un gran día. Cuídate mucho.',
        '¡Nos vemos pronto! 🌟 Fue un placer ayudarte.',
        '¡Cuídate mucho! 🫶 Vuelve cuando necesites, estaré aquí.',
        '¡Adiós! 😊 Que estés muy bien. Nos vemos pronto.',
        '¡Hasta la próxima! 👋 Recuerda que siempre estaré aquí para ayudarte.',
        '¡Que te vaya súper! 🌈 Vuelve pronto a visitarme.'
    ];
    return $opts[array_rand($opts)];
}

// ============================================
// COMANDOS DE VOZ PARA CARRITO Y COMPRAS
// ============================================

function is_cart_command(string $q): bool {
    return preg_match('/\b(carrito|mi carrito|ver carrito|abrir carrito|mostrar carrito|muestra el carrito)\b/iu', $q) === 1;
}

function is_checkout_command(string $q): bool {
    return preg_match('/\b(pagar|proceder al pago|finalizar compra|comprar|realizar pago|hacer pago|quiero pagar)\b/iu', $q) === 1;
}

function is_historial_command(string $q): bool {
    return preg_match('/\b(historial|mis compras|compras anteriores|compras previas|ver compras|mostrar compras|muestra mi historial)\b/iu', $q) === 1;
}

function is_download_pdf_command(string $q): bool {
    return preg_match('/\b(descargar pdf|descarga pdf|pdf|boleta|mi boleta|ultima boleta|última boleta|comprobante|descargar boleta|descargar comprobante)\b/iu', $q) === 1;
}

function is_clear_cart_command(string $q): bool {
    return preg_match('/\b(vaciar carrito|limpiar carrito|borrar carrito|eliminar todo del carrito)\b/iu', $q) === 1;
}

function is_greeting(string $q): bool {
    return preg_match('/\b(hola|buenas|buenos dias|buenos días|buenas tardes|buenas noches|hey|que tal|qué tal)\b/u', $q) === 1;
}

function greeting_response(): string {
    $opts = [
        '¡Hola! 😊 Soy Omarcitoia, tu asistente virtual. ¿En qué puedo ayudarte hoy?',
        '¡Qué gusto escucharte! 🙌 Soy Omarcitoia y estoy aquí para ayudarte. Dime, ¿qué necesitas?',
        '¡Hola! Bienvenido a nuestra farmacia 🏪 Me llamo Omarcitoia y estoy listo para asistirte.',
        '¡Hey! 😄 Soy Omarcitoia, tu compañero de salud. Puedo ayudarte con información sobre medicamentos, precios y más.',
        '¡Hola! 🌟 Soy Omarcitoia. Pregúntame por cualquier medicamento, con gusto te ayudaré.',
        '¡Qué alegría verte por aquí! 😊 Soy Omarcitoia y estoy para servirte. ¿Qué necesitas saber?'
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
        $desc = trim((string)$med['descripcion']);
        
        // Respuestas conversacionales sin mencionar stock
        $responses = [
            "¡Claro! Tengo información sobre $nombre. 😊 Su precio es de S/ $precio.",
            "¡Por supuesto! El $nombre tiene un costo de S/ $precio.",
            "¡Perfecto! Te cuento sobre $nombre: su precio es S/ $precio."
        ];
        
        $base = $responses[array_rand($responses)];
        
        if ($desc !== '') {
            $base .= " Déjame contarte más: $desc";
        }
        
        if ($priceIntent) {
            return $base . " ¿Hay algo más en lo que pueda ayudarte? 😊";
        } elseif ($availabilityIntent) {
            if ($stock > 0) {
                return "¡Sí, claro! Contamos con $nombre disponible. 😊 El precio es S/ $precio. ¿Te gustaría saber algo más sobre este medicamento?";
            } else {
                return "Lo siento, en este momento $nombre no está disponible. 😔 Pero puedo recomendarte alternativas similares o puedes consultarlo más adelante. ¿Necesitas que te sugiera algo parecido?";
            }
        } else {
            return $base . " ¿Quieres saber algo más sobre este medicamento o te puedo ayudar con otro? 😊";
        }
    }

    $bySym = search_by_symptom($db, $q);
    if (!empty($bySym)) {
        $parts = [];
        foreach ($bySym as $r) {
            $parts[] = $r['nombre'] . ' (S/ ' . number_format((float)$r['precio'], 2) . ')';
        }
        return '¡Mira! 👀 Te puedo recomendar estas opciones: ' . implode(', ', $parts) . '. ¿Te gustaría saber más detalles sobre alguno de estos? 😊';
    }

    $res = $db->query('SELECT nombre, precio FROM medicamentos ORDER BY nombre ASC LIMIT 5');
    if ($res && $res->num_rows) {
        $parts = [];
        while ($row = $res->fetch_assoc()) {
            $parts[] = $row['nombre'] . ' (S/ ' . number_format((float)$row['precio'], 2) . ')';
        }
        return 'Mmm... 🤔 No estoy seguro de qué producto buscas, pero aquí te muestro algunos disponibles: ' . implode(', ', $parts) . '. ¿Alguno de estos te interesa?';
    }

    return 'Lo siento, no encontré información sobre eso. 😅 ¿Podrías reformular tu pregunta o preguntarme por otro medicamento?';
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
    
    // ============================================
    // COMANDOS DE CARRITO Y COMPRAS
    // ============================================
    
    // Abrir carrito
    if (is_cart_command($q)) {
        $ans = '¡Perfecto! 🛍️ Te muestro tu carrito de compras. Puedes ver los productos que has agregado.';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans, 'action' => 'open_cart']);
        exit;
    }
    
    // Proceder al pago
    if (is_checkout_command($q)) {
        $ans = '¡Entendido! 💳 Te ayudo a proceder con el pago. Voy a abrir el carrito para que puedas finalizar tu compra.';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans, 'action' => 'checkout']);
        exit;
    }
    
    // Ver historial de compras
    if (is_historial_command($q)) {
        $ans = '📋 ¡Claro! Te muestro tu historial de compras. Aquí podrás ver todas tus compras anteriores.';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans, 'action' => 'show_historial']);
        exit;
    }
    
    // Descargar PDF
    if (is_download_pdf_command($q)) {
        $ans = '📄 ¡Por supuesto! Voy a descargar tu última boleta en PDF. Un momento...';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans, 'action' => 'download_pdf']);
        exit;
    }
    
    // Vaciar carrito
    if (is_clear_cart_command($q)) {
        $ans = '🗑️ Entendido. Voy a vaciar tu carrito de compras.';
        $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
        if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
        echo json_encode(['text' => $ans, 'action' => 'clear_cart']);
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

    // Total stock intent - respuesta sin mostrar cantidades específicas
    $intents = detect_intents($q);
    if (!empty($intents['stock'])) {
        $res = $db->query('SELECT COUNT(*) AS productos FROM medicamentos WHERE stock > 0');
        $productos = 0;
        if ($res && ($r = $res->fetch_assoc())) { $productos = (int)$r['productos']; }
        $responses = [
            "¡Claro! Contamos con $productos tipos de medicamentos diferentes disponibles. 😊 ¿Te gustaría saber sobre alguno en particular?",
            "¡Por supuesto! Tenemos $productos productos distintos que puedo mostrarte. ¿Hay alguno que te interese específicamente?",
            "¡Sí! Manejamos $productos medicamentos diferentes. 💊 ¿Quieres que te ayude a encontrar algo específico?"
        ];
        $ans = $responses[array_rand($responses)];
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
            $d = trim((string)($med['descripcion'] ?? ''));
            
            $responses = [
                "¡Claro! Te cuento sobre $n: 😊 Tiene un precio de S/ $p.",
                "¡Perfecto! El $n cuesta S/ $p.",
                "¡Sí! Tengo información sobre $n. Su precio es S/ $p."
            ];
            $ans = $responses[array_rand($responses)];
            
            if ($s > 0) {
                $ans .= " Lo tenemos disponible. 😊";
            } else {
                $ans .= " Actualmente no está disponible, pero puedo ayudarte a encontrar alternativas. 💊";
            }
            
            if ($d !== '') {
                $ans .= " Para que sepas: $d";
            }
            
            $ans .= " ¿Hay algo más que quieras saber?";
            
            $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
            if ($stmt) { $stmt->bind_param('ss', $q, $ans); $stmt->execute(); $stmt->close(); }
            echo json_encode(['text' => $ans]);
            exit;
        }
    }

    $answer = answer_from_db($db, $q);
    if (trim($answer) === '' || $answer === 'No encontré información para esa consulta.') {
        $fallbacks = [
            'Mmm... 🤔 No estoy seguro de entender eso. Puedo ayudarte con información sobre medicamentos, precios y más. Por ejemplo, puedes preguntarme: "¿Tienen paracetamol?" o "¿Cuánto cuesta el ibuprofeno?"',
            'Disculpa, no comprendí bien tu pregunta. 😅 Pero puedo ayudarte con información de medicamentos. ¿Me puedes decir de qué medicamento quieres saber?',
            'Lo siento, no capté eso. 🤔 Estoy aquí para ayudarte con precios y disponibilidad de medicamentos. ¿Qué medicamento te interesa?',
            'Hmm, no estoy seguro de eso. 😊 Pero cuéntame, ¿qué medicamento estás buscando? Puedo darte información sobre precios y detalles.'
        ];
        $answer = $fallbacks[array_rand($fallbacks)];
    }
    $stmt = $db->prepare('INSERT INTO consultas_historial (user_type, question, answer) VALUES ("client", ?, ?)');
    if ($stmt) { $stmt->bind_param('ss', $q, $answer); $stmt->execute(); $stmt->close(); }
    echo json_encode(['text' => $answer]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno']);
}
