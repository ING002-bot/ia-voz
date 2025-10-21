<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'omarcitoia';
const DB_USER = 'root';
const DB_PASS = '';

function get_db(): mysqli {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_errno) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit;
    }
    $mysqli->set_charset('utf8mb4');
    return $mysqli;
}
