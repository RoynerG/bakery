<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: TortaArmada
 *
 * Permite costear una torta completa a partir de una receta base
 * (por ejemplo bizcocho) mas ingredientes extra (rellenos, fruta,
 * crema, decoracion, empaque).
 */
final class TortaArmada
{
    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT t.*, r.nombre AS receta_base_actual
               FROM tortas_armadas t
               LEFT JOIN recetas r ON r.id = t.receta_base_id
              ORDER BY t.updated_at DESC, t.id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        $torta = Database::getInstance()->fetchOne(
            'SELECT * FROM tortas_armadas WHERE id = ?',
            [$id]
        );
        if (!$torta) return null;

        $torta['extras'] = self::extras($id);
        return $torta;
    }

    public static function extras(int $tortaId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM torta_armada_extras WHERE torta_armada_id = ? ORDER BY orden ASC, id ASC',
            [$tortaId]
        );
    }

    public static function create(array $data, array $extras): int
    {
        self::validate($data);

        return Database::getInstance()->transaction(function (Database $db) use ($data, $extras) {
            $calc = self::calcular($data, $extras);

            $db->execute(
                'INSERT INTO tortas_armadas
                    (nombre, descripcion, receta_base_id, receta_base_nombre,
                     porciones_objetivo, base_porciones, base_costo_total, base_factor,
                     base_costo_usado, costo_extras, otros_costos, iva_porcentaje,
                     iva_monto, margen_porcentaje, ganancia_monto, costo_total,
                     precio_sugerido)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    trim($data['nombre']),
                    self::nullable($data['descripcion'] ?? null),
                    (int)$data['receta_base_id'],
                    $calc['base']['nombre'],
                    $calc['porciones_objetivo'],
                    $calc['base']['porciones'],
                    $calc['base']['costo_total'],
                    $calc['base_factor'],
                    $calc['base_costo_usado'],
                    $calc['costo_extras'],
                    $calc['otros_costos'],
                    $calc['iva_porcentaje'],
                    $calc['iva_monto'],
                    $calc['margen_porcentaje'],
                    $calc['ganancia_monto'],
                    $calc['costo_total'],
                    $calc['precio_sugerido'],
                ]
            );

            $id = (int)$db->lastInsertId();
            self::guardarExtras($db, $id, $calc['extras']);
            return $id;
        });
    }

    public static function update(int $id, array $data, array $extras): bool
    {
        self::validate($data);
        if (!self::find($id)) {
            throw new \InvalidArgumentException('La torta armada no existe.');
        }

        Database::getInstance()->transaction(function (Database $db) use ($id, $data, $extras) {
            $calc = self::calcular($data, $extras);

            $db->execute(
                'UPDATE tortas_armadas
                    SET nombre = ?, descripcion = ?, receta_base_id = ?, receta_base_nombre = ?,
                        porciones_objetivo = ?, base_porciones = ?, base_costo_total = ?,
                        base_factor = ?, base_costo_usado = ?, costo_extras = ?,
                        otros_costos = ?, iva_porcentaje = ?, iva_monto = ?,
                        margen_porcentaje = ?, ganancia_monto = ?, costo_total = ?,
                        precio_sugerido = ?, updated_at = CURRENT_TIMESTAMP
                  WHERE id = ?',
                [
                    trim($data['nombre']),
                    self::nullable($data['descripcion'] ?? null),
                    (int)$data['receta_base_id'],
                    $calc['base']['nombre'],
                    $calc['porciones_objetivo'],
                    $calc['base']['porciones'],
                    $calc['base']['costo_total'],
                    $calc['base_factor'],
                    $calc['base_costo_usado'],
                    $calc['costo_extras'],
                    $calc['otros_costos'],
                    $calc['iva_porcentaje'],
                    $calc['iva_monto'],
                    $calc['margen_porcentaje'],
                    $calc['ganancia_monto'],
                    $calc['costo_total'],
                    $calc['precio_sugerido'],
                    $id,
                ]
            );

            $db->execute('DELETE FROM torta_armada_extras WHERE torta_armada_id = ?', [$id]);
            self::guardarExtras($db, $id, $calc['extras']);
        });

        return true;
    }

    public static function delete(int $id): bool
    {
        return Database::getInstance()->transaction(function (Database $db) use ($id) {
            $db->execute('DELETE FROM torta_armada_extras WHERE torta_armada_id = ?', [$id]);
            return $db->execute('DELETE FROM tortas_armadas WHERE id = ?', [$id])->rowCount() > 0;
        });
    }

    public static function calcular(array $data, array $extras): array
    {
        $base = Receta::find((int)($data['receta_base_id'] ?? 0));
        if (!$base) {
            throw new \InvalidArgumentException('Selecciona una receta base valida.');
        }

        $porcionesObjetivo = max(1, (int)($data['porciones_objetivo'] ?? $base['porciones'] ?? 1));
        $basePorciones = max(1, (int)$base['porciones']);
        $baseCostoTotal = (float)$base['costo_total'];
        $baseFactor = $porcionesObjetivo / $basePorciones;
        $baseCostoUsado = round($baseCostoTotal * $baseFactor, 4);

        $extrasCalc = [];
        $costoExtras = 0.0;
        $orden = 0;
        foreach ($extras as $extra) {
            $ingredienteId = (int)($extra['ingrediente_id'] ?? 0);
            $cantidad = (float)($extra['cantidad'] ?? 0);
            if ($ingredienteId <= 0 || $cantidad <= 0) continue;

            $ing = Ingrediente::find($ingredienteId);
            if (!$ing) continue;

            $subtotal = Ingrediente::calcularSubtotal($ing, $cantidad);
            $extrasCalc[] = [
                'ingrediente_id' => $ingredienteId,
                'ingrediente_nombre' => $ing['nombre'],
                'unidad_medida' => $ing['unidad_medida'],
                'cantidad' => $cantidad,
                'costo_unitario' => (float)$ing['costo_base'],
                'subtotal' => $subtotal,
                'orden' => $orden++,
            ];
            $costoExtras += $subtotal;
        }

        $otrosCostos = max(0.0, parse_clp($data['otros_costos'] ?? 0));
        $ivaPorcentaje = max(0.0, (float)($data['iva_porcentaje'] ?? 0));
        $margenPorcentaje = max(0.0, (float)($data['margen_porcentaje'] ?? 60));

        $subtotal = $baseCostoUsado + $costoExtras + $otrosCostos;
        $ivaMonto = round($subtotal * $ivaPorcentaje / 100, 4);
        $costoTotal = round($subtotal + $ivaMonto, 4);
        $gananciaMonto = round($costoTotal * $margenPorcentaje / 100, 4);
        $precioSugerido = round($costoTotal + $gananciaMonto, 4);

        return [
            'base' => [
                'nombre' => $base['nombre'],
                'porciones' => $basePorciones,
                'costo_total' => $baseCostoTotal,
            ],
            'porciones_objetivo' => $porcionesObjetivo,
            'base_factor' => round($baseFactor, 6),
            'base_costo_usado' => $baseCostoUsado,
            'extras' => $extrasCalc,
            'costo_extras' => round($costoExtras, 4),
            'otros_costos' => $otrosCostos,
            'iva_porcentaje' => $ivaPorcentaje,
            'iva_monto' => $ivaMonto,
            'margen_porcentaje' => $margenPorcentaje,
            'ganancia_monto' => $gananciaMonto,
            'costo_total' => $costoTotal,
            'precio_sugerido' => $precioSugerido,
        ];
    }

    private static function guardarExtras(Database $db, int $tortaId, array $extras): void
    {
        foreach ($extras as $extra) {
            $db->execute(
                'INSERT INTO torta_armada_extras
                    (torta_armada_id, ingrediente_id, ingrediente_nombre, unidad_medida,
                     cantidad, costo_unitario, subtotal, orden)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tortaId,
                    $extra['ingrediente_id'],
                    $extra['ingrediente_nombre'],
                    $extra['unidad_medida'],
                    $extra['cantidad'],
                    $extra['costo_unitario'],
                    $extra['subtotal'],
                    $extra['orden'],
                ]
            );
        }
    }

    private static function validate(array $data): void
    {
        if (empty($data['nombre']) || mb_strlen(trim((string)$data['nombre'])) < 2) {
            throw new \InvalidArgumentException('El nombre de la torta debe tener al menos 2 caracteres.');
        }
        if (empty($data['receta_base_id']) || !is_numeric($data['receta_base_id'])) {
            throw new \InvalidArgumentException('Selecciona una receta base.');
        }
        if ((int)($data['porciones_objetivo'] ?? 0) <= 0) {
            throw new \InvalidArgumentException('Las porciones deben ser mayores a 0.');
        }
    }

    private static function nullable(?string $value): ?string
    {
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
