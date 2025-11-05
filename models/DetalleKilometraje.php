<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class DetalleKilometraje {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function crear(array $datos): ?int {
        $required = ['id_solicitud', 'fecha_visita', 'nombre_visita', 'km_inicial', 'km_final'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $datos) || $datos[$field] === '') return null;
        }

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO detalle_kilometraje
                (id_solicitud, id_prospecto, fecha_visita, nombre_visita, km_inicial, km_final, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $datos['id_solicitud'],
                $datos['id_prospecto'] ?? null,
                $datos['fecha_visita'],
                $datos['nombre_visita'],
                $datos['km_inicial'],
                $datos['km_final'],
                $datos['observaciones'] ?? null
            ]);
            return (int)$this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error crear detalle km: " . $e->getMessage());
            return null;
        }
    }

    public function listarPorSolicitud(int $idSolicitud): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT dk.*, p.nombre_prospecto
                FROM detalle_kilometraje dk
                LEFT JOIN prospectos p ON dk.id_prospecto = p.id_prospecto
                WHERE dk.id_solicitud = ?
                ORDER BY dk.fecha_visita, dk.id_detalle_km
            ");
            $stmt->execute([$idSolicitud]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error listar detalles km: " . $e->getMessage());
            return [];
        }
    }

    public function actualizar(int $id, array $datos): bool {
        $required = ['fecha_visita', 'nombre_visita', 'km_inicial', 'km_final'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $datos) || $datos[$field] === '') return false;
        }

        try {
            $stmt = $this->conn->prepare("
                UPDATE detalle_kilometraje
                SET fecha_visita = ?, nombre_visita = ?, km_inicial = ?, km_final = ?, observaciones = ?
                WHERE id_detalle_km = ?
            ");
            return $stmt->execute([
                $datos['fecha_visita'],
                $datos['nombre_visita'],
                $datos['km_inicial'],
                $datos['km_final'],
                $datos['observaciones'] ?? null,
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Error actualizar detalle km: " . $e->getMessage());
            return false;
        }
    }

    public function eliminar(int $id): bool {
        try {
            $stmt = $this->conn->prepare("DELETE FROM detalle_kilometraje WHERE id_detalle_km = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminar detalle km: " . $e->getMessage());
            return false;
        }
    }
}