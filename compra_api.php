<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

function read_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function send_response($data) {
    echo json_encode($data);
    exit;
}

try {
    $body = read_json_body();
    $action = $body['action'] ?? '';
    
    $db = get_db();
    
    // ============================================
    // VERIFICAR SESIÓN
    // ============================================
    if ($action === 'check_session') {
        if (isset($_SESSION['cliente_id']) && $_SESSION['user_type'] === 'cliente') {
            send_response([
                'logged_in' => true,
                'user_info' => [
                    'id' => $_SESSION['cliente_id'],
                    'username' => $_SESSION['cliente_username'] ?? '',
                    'nombre' => $_SESSION['cliente_nombre'] ?? 'Cliente'
                ]
            ]);
        } else {
            send_response([
                'logged_in' => false
            ]);
        }
    }
    
    // ============================================
    // PROCESAR COMPRA
    // ============================================
    if ($action === 'process_purchase') {
        // Verificar que el usuario esté logueado
        if (!isset($_SESSION['cliente_id']) || $_SESSION['user_type'] !== 'cliente') {
            send_response([
                'success' => false,
                'error' => 'Debes iniciar sesión para realizar una compra'
            ]);
        }
        
        $usuario_id = (int)$_SESSION['cliente_id'];
        $items = $body['items'] ?? [];
        
        if (empty($items)) {
            send_response([
                'success' => false,
                'error' => 'El carrito está vacío'
            ]);
        }
        
        // Iniciar transacción
        $db->begin_transaction();
        
        try {
            $total = 0;
            $detalles = [];
            
            // Procesar cada item del carrito
            foreach ($items as $item) {
                $medicamento_id = (int)$item['id'];
                $cantidad = (int)$item['cantidad'];
                $precio_unitario = (float)$item['precio'];
                
                // Verificar stock disponible
                $stmt = $db->prepare('SELECT nombre, stock FROM medicamentos WHERE id = ? FOR UPDATE');
                $stmt->bind_param('i', $medicamento_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $medicamento = $result->fetch_assoc();
                $stmt->close();
                
                if (!$medicamento) {
                    throw new Exception("Producto no encontrado: ID $medicamento_id");
                }
                
                if ($medicamento['stock'] < $cantidad) {
                    throw new Exception("Stock insuficiente para {$medicamento['nombre']}. Disponible: {$medicamento['stock']}");
                }
                
                // Actualizar stock
                $nuevo_stock = $medicamento['stock'] - $cantidad;
                $stmt = $db->prepare('UPDATE medicamentos SET stock = ? WHERE id = ?');
                $stmt->bind_param('ii', $nuevo_stock, $medicamento_id);
                $stmt->execute();
                $stmt->close();
                
                // Registrar en tabla compras
                $subtotal = $precio_unitario * $cantidad;
                $stmt = $db->prepare('INSERT INTO compras (usuario_id, medicamento_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('iiidd', $usuario_id, $medicamento_id, $cantidad, $precio_unitario, $subtotal);
                $stmt->execute();
                $stmt->close();
                
                // Agregar a detalles para la boleta
                $detalles[] = [
                    'id' => $medicamento_id,
                    'nombre' => $medicamento['nombre'],
                    'cantidad' => $cantidad,
                    'precio' => $precio_unitario,
                    'subtotal' => $subtotal
                ];
                
                $total += $subtotal;
            }
            
            // Generar número de boleta único
            $numero_boleta = 'B' . date('Ymd') . '-' . str_pad((string)rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            // Verificar que sea único
            $stmt = $db->prepare('SELECT id FROM boletas WHERE numero_boleta = ?');
            $stmt->bind_param('s', $numero_boleta);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                // Si existe, agregar timestamp para hacerlo único
                $numero_boleta .= '-' . time();
            }
            $stmt->close();
            
            // Registrar boleta
            $detalles_json = json_encode($detalles);
            $stmt = $db->prepare('INSERT INTO boletas (usuario_id, numero_boleta, total, detalles) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isds', $usuario_id, $numero_boleta, $total, $detalles_json);
            $stmt->execute();
            $boleta_id = $stmt->insert_id;
            $stmt->close();
            
            // Actualizar puntos del cliente (1 punto por cada 10 soles)
            $puntos_ganados = (int)floor($total / 10);
            if ($puntos_ganados > 0) {
                $stmt = $db->prepare('UPDATE usuarios_clientes SET puntos = puntos + ? WHERE id = ?');
                $stmt->bind_param('ii', $puntos_ganados, $usuario_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Confirmar transacción
            $db->commit();
            
            // Enviar respuesta exitosa
            send_response([
                'success' => true,
                'boleta' => [
                    'id' => $boleta_id,
                    'numero_boleta' => $numero_boleta,
                    'total' => $total,
                    'fecha' => date('Y-m-d H:i:s'),
                    'detalles' => $detalles_json,
                    'puntos_ganados' => $puntos_ganados
                ]
            ]);
            
        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $db->rollback();
            
            send_response([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    // Acción no reconocida
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
