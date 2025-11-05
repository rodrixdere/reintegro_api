<?php
declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOException;

class Prospecto {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function listarActivos(): array {
        try {
            $stmt = $this->conn->query("
                SELECT
                    id_prospecto,
                    nombre_prospecto,
                    empresa,
                    telefono,
                    email,
                    direccion
                FROM prospectos
                WHERE estado = 'activo'
                ORDER BY nombre_prospecto
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error al listar prospectos: " . $e->getMessage());
            return [];
        }
    }

    public function obtenerPorId(int $id): ?array {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM prospectos
                WHERE id_prospecto = ?
            ");
            $stmt->execute([$id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);
            return $data ?: null;
        } catch (PDOException $e) {
            error_log("Error al obtener prospecto: " . $e->getMessage());
            return null;
        }
    }
}