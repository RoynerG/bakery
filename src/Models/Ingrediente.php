<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Ingrediente (Insumo)
 *
 * Unidad de medida válida: kilo, litro, pieza, gramo, mililitro.
 * costo_base = costo por 1 unidad de medida.
 */
final class Ingrediente
{
    public const UNIDADES = ['kilo', 'litro', 'pieza', 'gramo', 'mililitro'];

    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM ingredientes ORDER BY nombre ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM ingredientes WHERE id = ?',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        self::validate($data);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO ingredientes (nombre, unidad_medida, costo_base, notas)
             VALUES (?, ?, ?, ?)',
            [
                trim($data['nombre']),
                $data['unidad_medida'],
                (float)$data['costo_base'],
                isset($data['notas']) && $data['notas'] !== '' ? trim($data['notas']) : null,
            ]
        );
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        self::validate($data);
        $affected = Database::getInstance()->execute(
            'UPDATE ingredientes
                SET nombre = ?, unidad_medida = ?, costo_base = ?, notas = ?,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = ?',
            [
                trim($data['nombre']),
                $data['unidad_medida'],
                (float)$data['costo_base'],
                isset($data['notas']) && $data['notas'] !== '' ? trim($data['notas']) : null,
                $id,
            ]
        )->rowCount();
        return $affected > 0;
    }

    public static function delete(int $id): bool
    {
        try {
            $affected = Database::getInstance()->execute(
                'DELETE FROM ingredientes WHERE id = ?',
                [$id]
            )->rowCount();
            return $affected > 0;
        } catch (\PDOException $e) {
            // Si tiene recetas asociadas, RESTRICT impide el borrado
            return false;
        }
    }

    private static function validate(array $data): void
    {
        if (empty($data['nombre']) || mb_strlen(trim($data['nombre'])) < 2) {
            throw new \InvalidArgumentException('El nombre del ingrediente debe tener al menos 2 caracteres.');
        }
        if (!in_array($data['unidad_medida'] ?? '', self::UNIDADES, true)) {
            throw new \InvalidArgumentException('Unidad de medida no válida.');
        }
        if (!is_numeric($data['costo_base']) || (float)$data['costo_base'] < 0) {
            throw new \InvalidArgumentException('El costo base debe ser un número mayor o igual a 0.');
        }
    }

    /** Convierte la cantidad al costo real (kilo -> costo_base, gramo -> costo_base/1000, etc.) */
    public static function calcularSubtotal(array $ingrediente, float $cantidad): float
    {
        $factor = self::factorUnidad($ingrediente['unidad_medida']);
        return round(((float)$ingrediente['costo_base']) * $cantidad * $factor, 4);
    }

    /** Devuelve el factor multiplicador para alinear la unidad al costo_base (siempre kilo/litro/pieza) */
    public static function factorUnidad(string $unidad): float
    {
        return match ($unidad) {
            'kilo', 'litro', 'pieza' => 1.0,
            'gramo'                  => 0.001,
            'mililitro'              => 0.001,
            default                  => 1.0,
        };
    }
}
