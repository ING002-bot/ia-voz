<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ensure_admin();

$db = get_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function json_error(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $msg]);
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'inline_update') {
        $id = (int)($_POST['id'] ?? 0);
        $field = (string)($_POST['field'] ?? '');
        $value = (string)($_POST['value'] ?? '');
        if ($id <= 0 || $field === '') json_error('Parámetros inválidos');
        $allowed = ['nombre','descripcion','precio','stock','fecha_vencimiento'];
        if (!in_array($field, $allowed, true)) json_error('Campo no permitido');

        // Basic type handling
        if ($field === 'precio') {
            $sql = 'UPDATE medicamentos SET precio=? WHERE id=?';
            $stmt = $db->prepare($sql);
            if (!$stmt) json_error('Error preparando');
            $f = (float)str_replace(',', '.', $value);
            $stmt->bind_param('di', $f, $id);
        } elseif ($field === 'stock') {
            $sql = 'UPDATE medicamentos SET stock=? WHERE id=?';
            $stmt = $db->prepare($sql);
            if (!$stmt) json_error('Error preparando');
            $n = (int)$value;
            $stmt->bind_param('ii', $n, $id);
        } elseif ($field === 'fecha_vencimiento') {
            $sql = 'UPDATE medicamentos SET fecha_vencimiento=? WHERE id=?';
            $stmt = $db->prepare($sql);
            if (!$stmt) json_error('Error preparando');
            $stmt->bind_param('si', $value, $id);
        } else { // nombre, descripcion
            $sql = 'UPDATE medicamentos SET ' . $field . '=? WHERE id=?';
            $stmt = $db->prepare($sql);
            if (!$stmt) json_error('Error preparando');
            $stmt->bind_param('si', $value, $id);
        }
        if (!$stmt->execute()) json_error('No se pudo actualizar', 500);
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    } elseif ($action === 'bulk_update') {
        $id = (int)($_POST['id'] ?? 0);
        $fieldsJson = (string)($_POST['fields'] ?? '');
        if ($id <= 0 || $fieldsJson === '') json_error('Parámetros inválidos');
        $fields = json_decode($fieldsJson, true);
        if (!is_array($fields)) json_error('Formato de fields inválido');
        $allowed = ['nombre','descripcion','precio','stock','fecha_vencimiento'];
        $sets = [];
        $types = '';
        $values = [];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            if ($k === 'precio') { $types .= 'd'; $v = (float)str_replace(',', '.', (string)$v); }
            elseif ($k === 'stock') { $types .= 'i'; $v = (int)$v; }
            else { $types .= 's'; $v = (string)$v; }
            $sets[] = "$k = ?";
            $values[] = $v;
        }
        if (empty($sets)) json_error('Sin campos válidos');
        $types .= 'i';
        $values[] = $id;
        $sql = 'UPDATE medicamentos SET ' . implode(', ', $sets) . ' WHERE id=?';
        $stmt = $db->prepare($sql);
        if (!$stmt) json_error('Error preparando');
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) json_error('No se pudo actualizar', 500);
        $stmt->close();
        echo json_encode(['ok' => true]);
        exit;
    }
}

json_error('Acción no soportada', 405);
