<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function send_response($data) {
    echo json_encode($data);
    exit;
}

try {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['cliente_id']) || $_SESSION['user_type'] !== 'cliente') {
        send_response([
            'success' => false,
            'error' => 'No autorizado. Debes iniciar sesión.'
        ]);
    }
    
    $usuario_id = (int)$_SESSION['cliente_id'];
    $db = get_db();
    
    $action = $_GET['action'] ?? '';
    
    // ============================================
    // OBTENER HISTORIAL DE COMPRAS
    // ============================================
    if ($action === 'get_compras') {
        $stmt = $db->prepare('
            SELECT c.id, c.fecha, c.cantidad, c.precio_unitario, c.subtotal, m.nombre as medicamento
            FROM compras c
            INNER JOIN medicamentos m ON c.medicamento_id = m.id
            WHERE c.usuario_id = ?
            ORDER BY c.fecha DESC
            LIMIT 50
        ');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $compras = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        send_response([
            'success' => true,
            'compras' => $compras
        ]);
    }
    
    // ============================================
    // OBTENER BOLETAS
    // ============================================
    if ($action === 'get_boletas') {
        $stmt = $db->prepare('
            SELECT id, numero_boleta, total, fecha, detalles
            FROM boletas
            WHERE usuario_id = ?
            ORDER BY fecha DESC
            LIMIT 20
        ');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $boletas = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        send_response([
            'success' => true,
            'boletas' => $boletas
        ]);
    }
    
    // ============================================
    // OBTENER ÚLTIMA BOLETA
    // ============================================
    if ($action === 'get_ultima_boleta') {
        $stmt = $db->prepare('
            SELECT id, numero_boleta, total, fecha, detalles
            FROM boletas
            WHERE usuario_id = ?
            ORDER BY fecha DESC
            LIMIT 1
        ');
        $stmt->bind_param('i', $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $boleta = $result->fetch_assoc();
        $stmt->close();
        
        if ($boleta) {
            send_response([
                'success' => true,
                'boleta' => $boleta
            ]);
        } else {
            send_response([
                'success' => false,
                'error' => 'No se encontraron boletas'
            ]);
        }
    }
    
    // ============================================
    // OBTENER BOLETA POR ID
    // ============================================
    if ($action === 'get_boleta') {
        $boleta_id = (int)($_GET['id'] ?? 0);
        
        $stmt = $db->prepare('
            SELECT id, numero_boleta, total, fecha, detalles
            FROM boletas
            WHERE id = ? AND usuario_id = ?
            LIMIT 1
        ');
        $stmt->bind_param('ii', $boleta_id, $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $boleta = $result->fetch_assoc();
        $stmt->close();
        
        if ($boleta) {
            send_response([
                'success' => true,
                'boleta' => $boleta
            ]);
        } else {
            send_response([
                'success' => false,
                'error' => 'Boleta no encontrada'
            ]);
        }
    }
    
    // Acción no válida
    send_response([
        'success' => false,
        'error' => 'Acción no válida'
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    send_response([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
