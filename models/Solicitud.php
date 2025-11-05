<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class Solicitud {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function crear(array $datos): ?int {
        $required = ['id_empleado', 'id_vehiculo', 'fecha_solicitud', 'periodo_inicio', 'periodo_fin'];
        foreach ($required as $field) {
            if (!isset($datos[$field])) {
                error_log("Campo faltante en crear(): $field");
                return null;
            }
        }

        try {
            $this->conn->beginTransaction();

            // Obtener valores actuales de configuración
            $stmt = $this->conn->prepare("
                SELECT valor FROM configuracion_sistema WHERE clave = ? AND activo = 1
            ");
            
            $stmt->execute(['valor_litro']);
            $valorLitro = $stmt->fetchColumn() ?: 677;
            
            $stmt->execute(['factor_gpk']);
            $factorGpk = $stmt->fetchColumn() ?: 2.00;

            // Insertar solicitud
            $stmt = $this->conn->prepare("
                INSERT INTO solicitudes_reintegro
                (id_empleado, id_vehiculo, fecha_solicitud, periodo_inicio, periodo_fin, 
                 valor_litro, factor_gpk, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $stmt->execute([
                $datos['id_empleado'],
                $datos['id_vehiculo'],
                $datos['fecha_solicitud'],
                $datos['periodo_inicio'],
                $datos['periodo_fin'],
                $valorLitro,
                $factorGpk
            ]);

            $idSolicitud = (int)$this->conn->lastInsertId();
            $this->conn->commit();
            
            return $idSolicitud > 0 ? $idSolicitud : null;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error al crear Solicitud: " . $e->getMessage());
            return null;
        }
    }

    public function obtenerPorId(int $id): ?array {
        try {
            $stmt = $this->conn->prepare("
                SELECT
                    s.id_solicitud,
                    s.id_empleado,
                    s.id_vehiculo,
                    s.fecha_solicitud,
                    s.periodo_inicio,
                    s.periodo_fin,
                    s.valor_litro,
                    s.factor_gpk,
                    s.km_total,
                    s.litros_consumidos,
                    s.costo_combustible,
                    s.costo_gpk,
                    s.total_solicitud,
                    s.litros_facturados,
                    s.total_facturados,
                    s.estado,
                    s.fecha_aprobacion,
                    s.aprobado_por,
                    s.observaciones,
                    s.motivo_rechazo,
                    CONCAT(v.marca, ' ', v.modelo, ' (', v.anio, ')') AS vehiculo,
                    v.placa,
                    v.cilindrada,
                    v.tipo_combustible,
                    v.tipo_gasolina,
                    v.consumo_promedio,
                    CONCAT(e.nombre, ' ', e.apellido1, ' ', COALESCE(e.apellido2, '')) AS empleado
                FROM solicitudes_reintegro s
                INNER JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
                INNER JOIN empleados e ON s.id_empleado = e.id_empleado
                WHERE s.id_solicitud = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$data) return null;
            
            // Asegurar que consumo_promedio sea float
            $data['consumo_promedio'] = $data['consumo_promedio'] !== null 
                ? (float)$data['consumo_promedio'] 
                : 0.165;
                
            return $data;
        } catch (PDOException $e) {
            error_log("Error al obtener solicitud: " . $e->getMessage());
            return null;
        }
    }

    public function listarPorEmpleado(int $idEmpleado, ?string $estado = null): array {
        try {
            $sql = "
                SELECT
                    s.id_solicitud,
                    s.fecha_solicitud,
                    s.periodo_inicio,
                    s.periodo_fin,
                    s.km_total,
                    s.total_solicitud,
                    s.estado,
                    s.fecha_aprobacion,
                    CONCAT(v.marca, ' ', v.modelo) AS vehiculo,
                    v.placa
                FROM solicitudes_reintegro s
                INNER JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
                WHERE s.id_empleado = ?
            ";
            $params = [$idEmpleado];

            if ($estado !== null) {
                $sql .= " AND s.estado = ?";
                $params[] = $estado;
            }
            $sql .= " ORDER BY s.fecha_solicitud DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al listar por empleado: " . $e->getMessage());
            return [];
        }
    }

    public function listarTodas(?string $estado = null): array {
        try {
            $sql = "
                SELECT
                    s.id_solicitud,
                    s.fecha_solicitud,
                    s.periodo_inicio,
                    s.periodo_fin,
                    s.km_total,
                    s.total_solicitud,
                    s.estado,
                    s.fecha_aprobacion,
                    CONCAT(e.nombre, ' ', e.apellido1) AS empleado,
                    CONCAT(v.marca, ' ', v.modelo) AS vehiculo,
                    v.placa
                FROM solicitudes_reintegro s
                INNER JOIN vehiculo v ON s.id_vehiculo = v.id_vehiculo
                INNER JOIN empleados e ON s.id_empleado = e.id_empleado
            ";
            
            $params = [];
            if ($estado !== null) {
                $sql .= " WHERE s.estado = ?";
                $params[] = $estado;
            }
            $sql .= " ORDER BY s.fecha_solicitud DESC";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al listar todas: " . $e->getMessage());
            return [];
        }
    }

    public function actualizar(int $id, array $datos): bool {
        $permitidos = ['estado', 'observaciones', 'motivo_rechazo'];
        $campos = [];
        $valores = [];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $campos[] = "$campo = ?";
                $valores[] = $datos[$campo];
            }
        }

        if (empty($campos)) return false;

        $valores[] = $id;
        $sql = "UPDATE solicitudes_reintegro SET " . implode(', ', $campos) . " WHERE id_solicitud = ?";

        try {
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($valores);
        } catch (PDOException $e) {
            error_log("Error actualizar solicitud: " . $e->getMessage());
            return false;
        }
    }

    public function aprobar(int $id, int $aprobadoPor, ?string $observaciones = null): bool {
        if ($aprobadoPor <= 0) return false;

        try {
            $stmt = $this->conn->prepare("
                UPDATE solicitudes_reintegro
                SET estado = 'aprobada',
                    fecha_aprobacion = NOW(),
                    aprobado_por = ?,
                    observaciones = ?
                WHERE id_solicitud = ?
            ");
            return $stmt->execute([$aprobadoPor, $observaciones, $id]);
        } catch (PDOException $e) {
            error_log("Error aprobar: " . $e->getMessage());
            return false;
        }
    }

    public function rechazar(int $id, string $motivo): bool {
        if (trim($motivo) === '') return false;
        return $this->actualizar($id, [
            'estado' => 'rechazada',
            'motivo_rechazo' => $motivo
        ]);
    }
}