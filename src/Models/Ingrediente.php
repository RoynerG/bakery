<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Ingrediente (Insumo)
 *
 * Unidad de medida válida: kilo, litro, pieza, gramo, mililitro.
 * costo_base = costo por 1 kilo / 1 litro / 1 pieza (unidad base).
 *
 * A partir de v1.1 cada ingrediente puede tener una imagen opcional.
 * A partir de v1.2 la app acepta datos de compra (cantidad + unidad +
 * precio) y calcula el costo_base automáticamente, replicando el flujo
 * del Excel de la usuaria.
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
     * Espera en $data:
     *   - nombre           string
     *   - unidad_medida    'kilo'|'litro'|'pieza'|'gramo'|'mililitro' (cómo se usa en recetas)
     *   - cantidad_compra  float > 0   (ej. 1000)
     *   - unidad_compra    'kilo'|...  (ej. 'gramo')
     *   - precio_compra    float >= 0  (ej. 1120)
     *   - notas            string opcional
     *
     * El costo_base se calcula automáticamente a partir de la compra.
     */
    public static function create(array $data, ?string $imagenFilename): int
    {
        self::validate($data);
        $cantidad = parse_clp($data['cantidad_compra']);
        $precio   = parse_clp($data['precio_compra']);
        $costoBase = self::calcularCostoBase($cantidad, $data['unidad_compra'], $precio);

        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO ingredientes
                (nombre, unidad_medida, costo_base,
                 cantidad_compra, unidad_compra, precio_compra,
                 notas, imagen)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['nombre']),
                $data['unidad_medida'],
                $costoBase,
                $cantidad,
                $data['unidad_compra'],
                $precio,
                isset($data['notas']) && $data['notas'] !== '' ? trim($data['notas']) : null,
                $imagenFilename,
            ]
        );
        return (int)$db->lastInsertId();
    }

    /**
     * Actualiza un ingrediente.
     *
     * @param string|null $imagenFilename   nuevo archivo (si se subió uno)
     * @param bool        $reemplazarImagen true si se debe actualizar la imagen
     */
    public static function update(int $id, array $data, ?string $imagenFilename, bool $reemplazarImagen): bool
    {
        self::validate($data);
        $cantidad = parse_clp($data['cantidad_compra']);
        $precio   = parse_clp($data['precio_compra']);
        $costoBase = self::calcularCostoBase($cantidad, $data['unidad_compra'], $precio);

        if ($reemplazarImagen) {
            $affected = Database::getInstance()->execute(
                'UPDATE ingredientes
                    SET nombre = ?, unidad_medida = ?, costo_base = ?,
                        cantidad_compra = ?, unidad_compra = ?, precio_compra = ?,
                        notas = ?, imagen = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?',
                [
                    trim($data['nombre']),
                    $data['unidad_medida'],
                    $costoBase,
                    $cantidad,
                    $data['unidad_compra'],
                    $precio,
                    isset($data['notas']) && $data['notas'] !== '' ? trim($data['notas']) : null,
                    $imagenFilename,
                    $id,
                ]
            )->rowCount();

            $anterior = self::find($id);
            if ($anterior && !empty($anterior['imagen']) && $anterior['imagen'] !== $imagenFilename) {
                self::borrarImagen($anterior['imagen']);
            }
        } else {
            $affected = Database::getInstance()->execute(
                'UPDATE ingredientes
                    SET nombre = ?, unidad_medida = ?, costo_base = ?,
                        cantidad_compra = ?, unidad_compra = ?, precio_compra = ?,
                        notas = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?',
                [
                    trim($data['nombre']),
                    $data['unidad_medida'],
                    $costoBase,
                    $cantidad,
                    $data['unidad_compra'],
                    $precio,
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
        if (!in_array($data['unidad_compra'] ?? '', self::UNIDADES, true)) {
            throw new \InvalidArgumentException('Unidad de compra no válida.');
        }
        $cantidad = parse_clp($data['cantidad_compra'] ?? 0);
        if ($cantidad <= 0) {
            throw new \InvalidArgumentException('La cantidad comprada debe ser mayor a 0.');
        }
        $precio = parse_clp($data['precio_compra'] ?? -1);
        if ($precio < 0) {
            throw new \InvalidArgumentException('El precio de compra debe ser mayor o igual a 0.');
        }
    }

    /**
     * Deriva el costo por unidad base (kilo/litro/pieza) a partir de
     * los datos de compra del ingrediente.
     *
     *   costo_base = precio_compra / (cantidad_compra * factor_unidad_compra)
     *
     * Ejemplos:
     *   Compré 1000 g por $1120  →  1120 / (1000 * 0.001) = $1120 / kilo
     *   Compré 1 pieza por $200  →  200 / (1 * 1)        = $200 / pieza
     *   Compré 250 g por $2690  →  2690 / (250 * 0.001)  = $10760 / kilo
     */
    public static function calcularCostoBase(float $cantidadCompra, string $unidadCompra, float $precioCompra): float
    {
        if ($cantidadCompra <= 0) return 0.0;
        $factor = self::factorUnidad($unidadCompra);
        $enUnidadBase = $cantidadCompra * $factor; // kilo / litro / pieza
        return round($precioCompra / $enUnidadBase, 4);
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
