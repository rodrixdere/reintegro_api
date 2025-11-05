<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

class Vehiculo {
    private PDO $conn;

    public function __construct(PDO $conn) {
        $this->conn = $conn;
    }

    public function listarActivos(): array {
        $stmt = $this->conn->query("
            SELECT
                id_vehiculo,
                placa,
                CONCAT(marca, ' ', modelo, ' (', anio, ')') AS nombre_completo,
                marca, modelo, anio, cilindrada, tipo_combustible, tipo_gasolina, consumo_promedio
            FROM vehiculo
            WHERE estado = 'activo'
            ORDER BY marca, modelo
        ");

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // FORZAR consumo_promedio como float
            $row['consumo_promedio'] = $row['consumo_promedio'] !== null 
                ? (float)$row['consumo_promedio'] 
                : 0.165;
            $result[] = $row;
        }
        return $result;
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM vehiculo WHERE id_vehiculo = ?");
        $stmt->execute([$id]);
        $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
        return $vehiculo ?: null;
    }
}