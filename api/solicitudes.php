<?php
declare(strict_types=1);

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once dirname(__DIR__) . '/config/Database.php';
require_once dirname(__DIR__) . '/models/Solicitud.php';
require_once dirname(__DIR__) . '/models/DetalleKilometraje.php';
require_once dirname(__DIR__) . '/models/FacturaCombustible.php';

use App\Config\Database;
use App\Models\{Solicitud, DetalleKilometraje, FacturaCombustible};

// CORS Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Connection
$database = new Database();
$db = $database->getConnection();
if (!$db) {
    error_log("ERROR: No hay conexión a BD");
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$solicitudModel = new Solicitud($db);
$detalleModel   = new DetalleKilometraje($db);
$facturaModel   = new FacturaCombustible($db);

// Routing - CORREGIDO para capturar el ID correctamente
$requestUri = $_SERVER['REQUEST_URI'];
$id = null;

// Intentar extraer ID de diferentes formatos de URL
if (preg_match('#/solicitudes\.php/(\d+)#', $requestUri, $m)) {
    $id = (int)$m[1];
} elseif (preg_match('#/solicitudes/(\d+)#', $requestUri, $m)) {
    $id = (int)$m[1];
}

error_log("REQUEST_URI: $requestUri | ID extraído: " . ($id ?? 'null'));

try {
    switch ($_SERVER['REQUEST_METHOD']) {

        case 'GET':
            if ($id) {
                // Obtener solicitud específica con detalles
                error_log("Buscando solicitud ID: $id");
                
                $s = $solicitudModel->obtenerPorId($id);
                if (!$s) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Solicitud no encontrada'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                
                // Cargar detalles de kilometraje
                $s['detalles_km'] = $detalleModel->listarPorSolicitud($id);
                error_log("Detalles KM encontrados: " . count($s['detalles_km']));
                
                // Cargar facturas
                $s['facturas'] = $facturaModel->listarPorSolicitud($id);
                error_log("Facturas encontradas: " . count($s['facturas']));
                
                echo json_encode($s, JSON_UNESCAPED_UNICODE);
            } else {
                // Listar solicitudes
                $idEmp = $_GET['id_empleado'] ?? null;
                $estado = $_GET['estado'] ?? null;
                
                if ($idEmp && is_numeric($idEmp)) {
                    // Listar por empleado
                    $list = $solicitudModel->listarPorEmpleado((int)$idEmp, $estado);
                } else {
                    // Listar todas (para pregas.php)
                    $list = $solicitudModel->listarTodas($estado);
                }
                echo json_encode($list, JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'POST':
            $rawInput = file_get_contents('php://input');
            
            if (empty($rawInput)) {
                http_response_code(400);
                echo json_encode(['error' => 'Cuerpo vacío'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON ERROR: " . json_last_error_msg());
                http_response_code(400);
                echo json_encode([
                    'error' => 'JSON malformado',
                    'json_error' => json_last_error_msg()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!is_array($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'Se esperaba un objeto JSON'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $idSolicitud = $solicitudModel->crear($data);
            if ($idSolicitud) {
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'id_solicitud' => $idSolicitud
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Fallo al crear solicitud'], JSON_UNESCAPED_UNICODE);
            }
            break;

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'ID en URL requerido'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $rawInput = file_get_contents('php://input');
            if (empty($rawInput)) {
                http_response_code(400);
                echo json_encode(['error' => 'Cuerpo vacío'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $data = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("PUT JSON ERROR: " . json_last_error_msg());
                http_response_code(400);
                echo json_encode(['error' => 'JSON malformado en PUT'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $ok = false;
            if (isset($data['accion']) && $data['accion'] === 'aprobar' && isset($data['aprobado_por'])) {
                $ok = $solicitudModel->aprobar($id, (int)$data['aprobado_por'], $data['observaciones'] ?? null);
            } else {
                $ok = $solicitudModel->actualizar($id, $data);
            }

            echo json_encode($ok ? ['success' => true] : ['error' => 'Actualización fallida'], JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    error_log("EXCEPCIÓN: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server crash',
        'msg' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}