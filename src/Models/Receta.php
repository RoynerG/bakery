<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Receta
 *
 * Estructura:
 *   - recetas             (cabecera)
 *   - receta_ingredientes (ingredientes + cantidad + subtotal)
 *
 * Toda escritura se realiza dentro de una transacción para
 * garantizar la integridad de los datos.
 */
final class Receta
{
    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT id, nombre, descripcion, imagen, porciones, costo_total, created_at
               FROM recetas
              ORDER BY created_at DESC, id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        $receta = Database::getInstance()->fetchOne(
            'SELECT * FROM recetas WHERE id = ?',
            [$id]
        );
        if (!$receta) return null;

        $receta['ingredientes'] = self::ingredientes((int)$id);
        return $receta;
    }

    public static function ingredientes(int $recetaId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT ri.id, ri.ingrediente_id, ri.cantidad, ri.subtotal,
                    i.nombre, i.unidad_medida, i.costo_base, i.imagen
               FROM receta_ingredientes ri
               JOIN ingredientes i ON i.id = ri.ingrediente_id
              WHERE ri.receta_id = ?
              ORDER BY i.nombre ASC',
            [$recetaId]
        );
    }

    /**
     * Crea una receta con sus ingredientes.
     *
     * @param array $data cabecera: nombre, descripcion, instrucciones, porciones, imagen (opcional)
     * @param array $items lista: [['ingrediente_id'=>int,'cantidad'=>float], ...]
     * @param string|null $imagenFilename nombre de archivo ya subido
     */
    public static function create(array $data, array $items, ?string $imagenFilename): int
    {
        self::validate($data, $items);

        return Database::getInstance()->transaction(function (Database $db) use ($data, $items, $imagenFilename) {
            $costoTotal = self::calcularCosto($items);

            $db->execute(
                'INSERT INTO recetas (nombre, descripcion, instrucciones, imagen, porciones, costo_total)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    trim($data['nombre']),
                    isset($data['descripcion']) && $data['descripcion'] !== '' ? trim($data['descripcion']) : null,
                    trim($data['instrucciones']),
                    $imagenFilename,
                    max(1, (int)($data['porciones'] ?? 1)),
                    $costoTotal,
                ]
            );
            $recetaId = (int)$db->lastInsertId();
            self::guardarIngredientes($db, $recetaId, $items);
            return $recetaId;
        });
    }

    public static function update(int $id, array $data, array $items, ?string $imagenFilename, bool $reemplazarImagen): bool
    {
        self::validate($data, $items);
        $recetaActual = self::find($id);
        if (!$recetaActual) return false;

        Database::getInstance()->transaction(function (Database $db) use ($id, $data, $items, $imagenFilename, $reemplazarImagen, $recetaActual) {
            $costoTotal = self::calcularCosto($items);

            if ($reemplazarImagen && $imagenFilename) {
                $db->execute(
                    'UPDATE recetas
                        SET nombre = ?, descripcion = ?, instrucciones = ?,
                            imagen = ?, porciones = ?, costo_total = ?,
                            updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?',
                    [
                        trim($data['nombre']),
                        isset($data['descripcion']) && $data['descripcion'] !== '' ? trim($data['descripcion']) : null,
                        trim($data['instrucciones']),
                        $imagenFilename,
                        max(1, (int)($data['porciones'] ?? 1)),
                        $costoTotal,
                        $id,
                    ]
                );
                // Borrar imagen anterior si existe
                if (!empty($recetaActual['imagen'])) {
                    self::borrarImagen($recetaActual['imagen']);
                }
            } else {
                $db->execute(
                    'UPDATE recetas
                        SET nombre = ?, descripcion = ?, instrucciones = ?,
                            porciones = ?, costo_total = ?,
                            updated_at = CURRENT_TIMESTAMP
                      WHERE id = ?',
                    [
                        trim($data['nombre']),
                        isset($data['descripcion']) && $data['descripcion'] !== '' ? trim($data['descripcion']) : null,
                        trim($data['instrucciones']),
                        max(1, (int)($data['porciones'] ?? 1)),
                        $costoTotal,
                        $id,
                    ]
                );
            }

            $db->execute('DELETE FROM receta_ingredientes WHERE receta_id = ?', [$id]);
            self::guardarIngredientes($db, $id, $items);
        });

        return true;
    }

    public static function delete(int $id): bool
    {
        $receta = self::find($id);
        if (!$receta) return false;

        Database::getInstance()->transaction(function (Database $db) use ($id, $receta) {
            $db->execute('DELETE FROM recetas WHERE id = ?', [$id]);
            if (!empty($receta['imagen'])) {
                self::borrarImagen($receta['imagen']);
            }
        });
        return true;
    }

    private static function guardarIngredientes(Database $db, int $recetaId, array $items): void
    {
        foreach ($items as $item) {
            $ing = Ingrediente::find((int)$item['ingrediente_id']);
            if (!$ing) continue;
            $cantidad = (float)$item['cantidad'];
            $subtotal = Ingrediente::calcularSubtotal($ing, $cantidad);
            $db->execute(
                'INSERT INTO receta_ingredientes (receta_id, ingrediente_id, cantidad, subtotal)
                 VALUES (?, ?, ?, ?)',
                [$recetaId, (int)$item['ingrediente_id'], $cantidad, $subtotal]
            );
        }
    }

    private static function calcularCosto(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $ing = Ingrediente::find((int)$item['ingrediente_id']);
            if (!$ing) continue;
            $total += Ingrediente::calcularSubtotal($ing, (float)$item['cantidad']);
        }
        return round($total, 4);
    }

    private static function validate(array $data, array $items): void
    {
        if (empty($data['nombre']) || mb_strlen(trim($data['nombre'])) < 2) {
            throw new \InvalidArgumentException('El nombre de la receta debe tener al menos 2 caracteres.');
        }
        if (empty($data['instrucciones']) || mb_strlen(trim($data['instrucciones'])) < 5) {
            throw new \InvalidArgumentException('Las instrucciones son obligatorias.');
        }
        if (empty($items)) {
            throw new \InvalidArgumentException('Agrega al menos un ingrediente a la receta.');
        }
        foreach ($items as $i => $item) {
            if (empty($item['ingrediente_id']) || !is_numeric($item['ingrediente_id'])) {
                throw new \InvalidArgumentException("Ingrediente #" . ($i + 1) . " inválido.");
            }
            if (!is_numeric($item['cantidad']) || (float)$item['cantidad'] <= 0) {
                throw new \InvalidArgumentException("La cantidad del ingrediente #" . ($i + 1) . " debe ser mayor a 0.");
            }
        }
    }

    private static function borrarImagen(string $filename): void
    {
        $path = UPLOADS_PATH . '/' . basename($filename);
        if (is_file($path)) @unlink($path);
    }
}
