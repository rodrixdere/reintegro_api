<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/DetalleKilometraje.php';

use App\Config\Database;
use App\Models\DetalleKilometraje;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexion'], JSON_UNESCAPED_UNICODE);
    exit;
}

$model = new DetalleKilometraje($db);
$method = $_SERVER['REQUEST_METHOD'];

// CORREGIDO: Capturar ID desde la URL - soporta ambos formatos
$requestUri = $_SERVER['REQUEST_URI'];
$id = null;

if (preg_match('#/kilometraje\.php/(\d+)#', $requestUri, $m)) {
    $id = (int)$m[1];
} elseif (preg_match('#/kilometraje/(\d+)#', $requestUri, $m)) {
    $id = (int)$m[1];
}

error_log("KILOMETRAJE - REQUEST_URI: $requestUri | ID extraído: " . ($id ?? 'null') . " | METHOD: $method");

try {
    switch ($method) {
        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $idDetalle = $model->crear($data);

            if ($idDetalle) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'id_detalle_km' => $idDetalle
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear detalle'], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'PUT':
            if (!$id) {
                error_log("KILOMETRAJE PUT: ID no encontrado en URL");
                http_response_code(400);
                echo json_encode(['error' => 'ID requerido para actualizar'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            if ($model->actualizar($id, $data)) {
                echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar'], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'DELETE':
            if (!$id) {
                error_log("KILOMETRAJE DELETE: ID no encontrado en URL: $requestUri");
                http_response_code(400);
                echo json_encode([
                    'error' => 'ID requerido para eliminar',
                    'debug_uri' => $requestUri
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            error_log("KILOMETRAJE DELETE: Intentando eliminar ID: $id");
            
            if ($model->eliminar($id)) {
                error_log("KILOMETRAJE DELETE: Eliminado exitosamente ID: $id");
                echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
            } else {
                error_log("KILOMETRAJE DELETE: Error al eliminar ID: $id");
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar'], JSON_UNESCAPED_UNICODE);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Metodo no permitido'], JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    error_log("KILOMETRAJE EXCEPTION: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
}