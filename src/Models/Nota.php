<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Nota
 *
 * Bloc de notas del admin. Cada nota puede tener una categoría
 * asociada (etiqueta con emoji + nombre).
 */
final class Nota
{
    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT n.*, c.emoji AS cat_emoji, c.nombre AS cat_nombre
               FROM notas n
          LEFT JOIN categorias c ON c.id = n.categoria_id
              ORDER BY n.updated_at DESC, n.id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT n.*, c.emoji AS cat_emoji, c.nombre AS cat_nombre
               FROM notas n
          LEFT JOIN categorias c ON c.id = n.categoria_id
              WHERE n.id = ?',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        self::validate($data);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO notas (titulo, contenido, categoria_id) VALUES (?, ?, ?)',
            [
                trim($data['titulo']),
                isset($data['contenido']) ? trim($data['contenido']) : '',
                self::normalizarCategoria($data['categoria_id'] ?? null),
            ]
        );
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        self::validate($data);
        $affected = Database::getInstance()->execute(
            'UPDATE notas
                SET titulo = ?, contenido = ?, categoria_id = ?,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = ?',
            [
                trim($data['titulo']),
                isset($data['contenido']) ? trim($data['contenido']) : '',
                self::normalizarCategoria($data['categoria_id'] ?? null),
                $id,
            ]
        )->rowCount();
        return $affected > 0;
    }

    public static function delete(int $id): bool
    {
        $affected = Database::getInstance()->execute(
            'DELETE FROM notas WHERE id = ?',
            [$id]
        )->rowCount();
        return $affected > 0;
    }

    private static function validate(array $data): void
    {
        if (empty($data['titulo']) || mb_strlen(trim($data['titulo'])) < 1) {
            throw new \InvalidArgumentException('El título es obligatorio.');
        }
        if (mb_strlen(trim($data['titulo'])) > 180) {
            throw new \InvalidArgumentException('El título es demasiado largo (máx 180 caracteres).');
        }
    }

    private static function normalizarCategoria($id): ?int
    {
        if ($id === null || $id === '' || $id === '0') return null;
        $id = (int)$id;
        return $id > 0 ? $id : null;
    }
}