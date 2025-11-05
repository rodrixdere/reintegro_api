<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class FacturaCombustible {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function crear(array $datos): ?int {
        $required = ['id_solicitud', 'numero_factura', 'fecha_factura', 'nombre_proveedor', 'litros', 'monto_pagado'];
        foreach ($required as $field) {
            if (!isset($datos[$field]) || $datos[$field] === '') return null;
        }

        try {
            $stmt = $this->conn->prepare("
                INSERT INTO facturas_combustible
                (id_solicitud, id_proveedor, numero_factura, fecha_factura, nombre_proveedor, litros, monto_pagado, archivo_factura, observaciones)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $datos['id_solicitud'],
                $datos['id_proveedor'] ?? null,
                $datos['numero_factura'],
                $datos['fecha_factura'],
                $datos['nombre_proveedor'],
                $datos['litros'],
                $datos['monto_pagado'],
                $datos['archivo_factura'] ?? null,
                $datos['observaciones'] ?? null
            ]);
            return (int)$this->conn->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error crear factura: " . $e->getMessage());
            return null;
        }
    }

    public function listarPorSolicitud(int $idSolicitud): array {
        try {
            $stmt = $this->conn->prepare("
                SELECT fc.*, pc.nombre_proveedor AS proveedor_nombre
                FROM facturas_combustible fc
                LEFT JOIN proveedores_combustible pc ON fc.id_proveedor = pc.id_proveedor
                WHERE fc.id_solicitud = ?
                ORDER BY fc.fecha_factura DESC
            ");
            $stmt->execute([$idSolicitud]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error listar facturas: " . $e->getMessage());
            return [];
        }
    }

    public function eliminar(int $id): bool {
        try {
            $stmt = $this->conn->prepare("DELETE FROM facturas_combustible WHERE id_factura = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error eliminar factura: " . $e->getMessage());
            return false;
        }
    }
}