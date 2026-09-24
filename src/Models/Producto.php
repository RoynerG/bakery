<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Producto (Catalogo publico)
 *
 * Tipos validos:
 *   - destacado: portada (Kuchen, Pie, etc.)
 *   - clasica:   Tortas clasicas (con sabores)
 *   - premium:   Tortas premium (con descripcion)
 *   - extra:     Extras / adicionales
 *   - galeria:   Fotos para la pagina final
 */
final class Producto
{
    public const TIPOS = [
        'destacado' => 'Destacado (portada)',
        'clasica'   => 'Torta clasica',
        'premium'   => 'Torta premium',
        'extra'     => 'Extra / adicional',
        'galeria'   => 'Foto galeria',
    ];

    private const TIPOS_CON_VARIANTES_PROPIAS = ['destacado', 'extra'];

    public static function all(bool $soloVisibles = false): array
    {
        $sql = 'SELECT * FROM productos';
        if ($soloVisibles) $sql .= ' WHERE visible = 1';
        $sql .= ' ORDER BY tipo ASC, orden ASC, nombre ASC';
        return Database::getInstance()->fetchAll($sql);
    }

    public static function byTipo(string $tipo, bool $soloVisibles = true): array
    {
        $sql = 'SELECT * FROM productos WHERE tipo = ?';
        $params = [$tipo];
        if ($soloVisibles) {
            $sql .= ' AND visible = 1';
        }
        $sql .= ' ORDER BY orden ASC, nombre ASC';
        return Database::getInstance()->fetchAll($sql, $params);
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM productos WHERE id = ?',
            [$id]
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM productos WHERE slug = ?',
            [$slug]
        );
    }

    public static function create(array $data, ?string $imagenFilename): int
    {
        self::validate($data);
        $slugBase = trim((string)($data['slug'] ?? ''));
        $slug = self::ensureUniqueSlug($slugBase !== '' ? $slugBase : self::slugify($data['nombre']));

        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO productos
                (nombre, slug, descripcion, tipo, imagen, precio_desde, visible, orden)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                trim($data['nombre']),
                $slug,
                $data['descripcion'] ?? null,
                $data['tipo'],
                $imagenFilename,
                isset($data['precio_desde']) && $data['precio_desde'] !== '' ? parse_clp($data['precio_desde']) : null,
                !empty($data['visible']) ? 1 : 0,
                (int)($data['orden'] ?? 0),
            ]
        );
        $id = (int)$db->lastInsertId();
        if (self::usaVariantesPropias($data['tipo']) && !empty($data['variantes']) && is_array($data['variantes'])) {
            self::saveVariantes($id, $data['variantes']);
        }
        return $id;
    }

    public static function update(int $id, array $data, ?string $imagenFilename, bool $reemplazarImagen): bool
    {
        self::validate($data);
        $anterior = self::find($id);
        if (!$anterior) {
            throw new \InvalidArgumentException('Producto no encontrado.');
        }

        $slugBase = trim((string)($data['slug'] ?? ''));
        $slug = self::ensureUniqueSlug($slugBase !== '' ? $slugBase : self::slugify($data['nombre']), $id);

        $imagenSql = $imagenFilename !== null ? ', imagen = ?' : '';
        $params = [
            trim($data['nombre']),
            $slug,
            $data['descripcion'] ?? null,
            $data['tipo'],
            !empty($data['visible']) ? 1 : 0,
            (int)($data['orden'] ?? 0),
            isset($data['precio_desde']) && $data['precio_desde'] !== '' ? parse_clp($data['precio_desde']) : null,
            $id,
        ];
        if ($imagenSql !== '') {
            array_splice($params, count($params) - 1, 0, [$imagenFilename]);
        }

        $db = Database::getInstance();
        $db->execute(
            "UPDATE productos
                SET nombre = ?, slug = ?, descripcion = ?, tipo = ?,
                    visible = ?, orden = ?, precio_desde = ?
                    $imagenSql,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = ?",
            $params
        );

        if ($reemplazarImagen && $imagenFilename !== null) {
            if ($anterior && !empty($anterior['imagen']) && $anterior['imagen'] !== $imagenFilename) {
                self::borrarImagen($anterior['imagen']);
            }
        }

        if (!self::usaVariantesPropias($data['tipo'])) {
            $db->execute('DELETE FROM producto_variantes WHERE producto_id = ?', [$id]);
        } elseif (isset($data['variantes']) && is_array($data['variantes'])) {
            $db->execute('DELETE FROM producto_variantes WHERE producto_id = ?', [$id]);
            self::saveVariantes($id, $data['variantes']);
        }

        return true;
    }

    public static function delete(int $id): bool
    {
        try {
            $anterior = self::find($id);
            $affected = Database::getInstance()->execute(
                'DELETE FROM productos WHERE id = ?',
                [$id]
            )->rowCount();
            if ($affected > 0 && $anterior && !empty($anterior['imagen'])) {
                self::borrarImagen($anterior['imagen']);
            }
            return $affected > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public static function variantes(int $productoId): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM producto_variantes WHERE producto_id = ? ORDER BY orden ASC, id ASC',
            [$productoId]
        );
    }

    private static function saveVariantes(int $productoId, array $variantes): void
    {
        $db = Database::getInstance();
        $orden = 0;
        foreach ($variantes as $v) {
            $label = trim($v['label'] ?? '');
            if ($label === '') continue;
            $precio = parse_clp($v['precio'] ?? 0);
            $db->execute(
                'INSERT INTO producto_variantes (producto_id, label, precio, orden) VALUES (?, ?, ?, ?)',
                [$productoId, $label, $precio, $orden++]
            );
        }
    }

    private static function usaVariantesPropias(string $tipo): bool
    {
        return in_array($tipo, self::TIPOS_CON_VARIANTES_PROPIAS, true);
    }

    private static function validate(array $data): void
    {
        if (empty($data['nombre']) || mb_strlen(trim($data['nombre'])) < 2) {
            throw new \InvalidArgumentException('El nombre debe tener al menos 2 caracteres.');
        }
        if (!array_key_exists($data['tipo'] ?? '', self::TIPOS)) {
            throw new \InvalidArgumentException('Tipo de producto no valido.');
        }
        if (isset($data['precio_desde']) && $data['precio_desde'] !== '') {
            $p = parse_clp($data['precio_desde']);
            if ($p < 0) {
                throw new \InvalidArgumentException('El precio no puede ser negativo.');
            }
        }
    }

    private static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        return trim($text, '-') ?: 'producto';
    }

    private static function ensureUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base;
        $i = 1;
        while (true) {
            $existing = self::findBySlug($slug);
            if (!$existing || ($ignoreId !== null && (int)$existing['id'] === $ignoreId)) {
                return $slug;
            }
            $i++;
            $slug = $base . '-' . $i;
        }
    }

    private static function borrarImagen(string $filename): void
    {
        $path = UPLOADS_PATH . '/' . basename($filename);
        if (is_file($path)) @unlink($path);
    }
}
