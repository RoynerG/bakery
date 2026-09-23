<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Nota
 *
 * Bloc de notas simple del administrador. Cada nota tiene un color
 * pastel para identificarla visualmente.
 */
final class Nota
{
    public const COLORES = ['rosa', 'crema', 'menta', 'chocolate'];

    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM notas ORDER BY updated_at DESC, id DESC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM notas WHERE id = ?',
            [$id]
        );
    }

    public static function create(array $data): int
    {
        self::validate($data);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO notas (titulo, contenido, color) VALUES (?, ?, ?)',
            [
                trim($data['titulo']),
                isset($data['contenido']) ? trim($data['contenido']) : '',
                self::normalizarColor($data['color'] ?? 'rosa'),
            ]
        );
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        self::validate($data);
        $affected = Database::getInstance()->execute(
            'UPDATE notas
                SET titulo = ?, contenido = ?, color = ?,
                    updated_at = CURRENT_TIMESTAMP
              WHERE id = ?',
            [
                trim($data['titulo']),
                isset($data['contenido']) ? trim($data['contenido']) : '',
                self::normalizarColor($data['color'] ?? 'rosa'),
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

    private static function normalizarColor(string $color): string
    {
        return in_array($color, self::COLORES, true) ? $color : 'rosa';
    }
}