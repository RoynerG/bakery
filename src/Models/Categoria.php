<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Categoria
 *
 * Etiquetas personalizables con emoji + nombre, usadas para clasificar
 * las notas del admin. CRUD completo desde la UI.
 */
final class Categoria
{
    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM categorias ORDER BY nombre ASC'
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM categorias WHERE id = ?',
            [$id]
        );
    }

    public static function create(string $emoji, string $nombre): int
    {
        $emoji  = trim($emoji);
        $nombre = trim($nombre);
        self::validate($emoji, $nombre);

        $db = Database::getInstance();
        try {
            $db->execute(
                'INSERT INTO categorias (emoji, nombre) VALUES (?, ?)',
                [$emoji, $nombre]
            );
        } catch (\PDOException $e) {
            // Nombre duplicado (UNIQUE)
            throw new \InvalidArgumentException('Ya existe una categoría con ese nombre.');
        }
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, string $emoji, string $nombre): bool
    {
        $emoji  = trim($emoji);
        $nombre = trim($nombre);
        self::validate($emoji, $nombre);

        try {
            $affected = Database::getInstance()->execute(
                'UPDATE categorias SET emoji = ?, nombre = ? WHERE id = ?',
                [$emoji, $nombre, $id]
            )->rowCount();
        } catch (\PDOException $e) {
            throw new \InvalidArgumentException('Ya existe una categoría con ese nombre.');
        }
        return $affected > 0;
    }

    public static function delete(int $id): bool
    {
        // Las notas que usen esta categoría quedarán con categoria_id = NULL
        $affected = Database::getInstance()->execute(
            'DELETE FROM categorias WHERE id = ?',
            [$id]
        )->rowCount();
        return $affected > 0;
    }

    private static function validate(string $emoji, string $nombre): void
    {
        if ($emoji === '') {
            throw new \InvalidArgumentException('El emoji es obligatorio.');
        }
        if (mb_strlen($emoji) > 8) {
            throw new \InvalidArgumentException('El emoji es demasiado largo.');
        }
        if ($nombre === '' || mb_strlen($nombre) < 2) {
            throw new \InvalidArgumentException('El nombre debe tener al menos 2 caracteres.');
        }
        if (mb_strlen($nombre) > 60) {
            throw new \InvalidArgumentException('El nombre es demasiado largo (máx 60 caracteres).');
        }
    }
}