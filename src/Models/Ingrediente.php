<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Ingrediente (Insumo)
 *
 * Unidad de medida válida: kilo, litro, pieza, gramo, mililitro.
 * costo_base = costo por 1 unidad de medida.
 *
 * A partir de v1.1 cada ingrediente puede tener una imagen opcional.
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

    /**
     * Crea un ingrediente con su imagen opcional.
     *
     * @param array  $data           cabecera: nombre, unidad_medida, costo_base, notas
     * @param string|null $imagenFilename nombre ya subido a /uploads (o null)
     */
    public static function create(array $data, ?string $imagenFilename): int
    {
        self::validate($data);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO ingredientes (nombre, unidad_medida, costo_base, notas, imagen)
             VALUES (?, ?, ?, ?, ?)',
            [
                trim($data['nombre']),
                $data['unidad_medida'],
                (float)$data['costo_base'],
                isset($data['notas']) && $data['notas'] !== '' ? trim($data['notas']) : null,
                $imagenFilename,
            ]
        );
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza un ingrediente.
     *
     * @param array  $data
     * @param string|null $imagenFilename   nuevo archivo (si se subió uno)
     * @param bool   $reemplazarImagen      true si se debe actualizar la imagen
     */
    public static function update(int $id, array $data, ?string $imagenFilename, bool $reemplazarImagen): bool
    {
        self::validate($data);

        if ($reemplazarImagen) {
            $affected = Database::getInstance()->execute(
                'UPDATE ingredientes
                    SET nombre = ?, unidad_medida = ?, costo_base = ?, notas = ?,
                        imagen = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?',
                [
                    trim($data['nombre']),
                    $data['unidad_medida'],
                    (float)$data['costo_base'],
                    isset($data['notas']) && $data['notas'] !== '' ? trim($data['notas']) : null,
                    $imagenFilename,
                    $id,
                ]
            )->rowCount();

            // Borrar imagen anterior si estamos reemplazando
            $anterior = self::find($id);
            if ($anterior && !empty($anterior['imagen']) && $anterior['imagen'] !== $imagenFilename) {
                self::borrarImagen($anterior['imagen']);
            }
        } else {
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
        }
        return $affected > 0;
    }

    public static function delete(int $id): bool
    {
        try {
            $anterior = self::find($id);
            $affected = Database::getInstance()->execute(
                'DELETE FROM ingredientes WHERE id = ?',
                [$id]
            )->rowCount();

            if ($affected > 0 && $anterior && !empty($anterior['imagen'])) {
                self::borrarImagen($anterior['imagen']);
            }
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

    private static function borrarImagen(string $filename): void
    {
        $path = UPLOADS_PATH . '/' . basename($filename);
        if (is_file($path)) @unlink($path);
    }
}