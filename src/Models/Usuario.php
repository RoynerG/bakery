<?php
declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Modelo: Usuario
 *
 * Almacena credenciales para acceder al panel de administración.
 * Las contraseñas se guardan con password_hash() (bcrypt por defecto).
 */
final class Usuario
{
    public static function count(): int
    {
        return (int) Database::getInstance()->fetchColumn('SELECT COUNT(*) FROM usuarios');
    }

    public static function existsAny(): bool
    {
        return self::count() > 0;
    }

    public static function all(): array
    {
        return Database::getInstance()->fetchAll(
            'SELECT * FROM usuarios ORDER BY created_at ASC, id ASC'
        );
    }

    public static function findByUsuario(string $usuario): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM usuarios WHERE usuario = ? LIMIT 1',
            [$usuario]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::getInstance()->fetchOne(
            'SELECT * FROM usuarios WHERE id = ? LIMIT 1',
            [$id]
        );
    }

    public static function create(string $usuario, string $password, ?string $nombre = null): int
    {
        $usuario = trim($usuario);
        if (mb_strlen($usuario) < 3) {
            throw new \InvalidArgumentException('El usuario debe tener al menos 3 caracteres.');
        }
        if (mb_strlen($password) < 6) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres.');
        }
        if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $usuario)) {
            throw new \InvalidArgumentException('El usuario solo puede contener letras, números, guion bajo, punto o guion.');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $db = Database::getInstance();
        $db->execute(
            'INSERT INTO usuarios (usuario, password_hash, nombre) VALUES (?, ?, ?)',
            [$usuario, $hash, $nombre !== null ? trim($nombre) : null]
        );
        return (int) $db->lastInsertId();
    }

    public static function verifyPassword(string $usuario, string $password): ?array
    {
        $user = self::findByUsuario($usuario);
        if (!$user) return null;
        if (!password_verify($password, $user['password_hash'])) return null;
        return $user;
    }

    public static function touchLogin(int $id): void
    {
        Database::getInstance()->execute(
            'UPDATE usuarios SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$id]
        );
    }

    public static function changePassword(int $id, string $newPassword): void
    {
        if (mb_strlen($newPassword) < 6) {
            throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres.');
        }
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        Database::getInstance()->execute(
            'UPDATE usuarios SET password_hash = ? WHERE id = ?',
            [$hash, $id]
        );
    }

    /**
     * Actualiza datos del usuario (nombre y, opcionalmente, contraseña).
     */
    public static function update(int $id, ?string $nombre, ?string $newPassword = null): bool
    {
        $params = [];
        $sets   = [];

        if ($nombre !== null) {
            $sets[]   = 'nombre = ?';
            $params[] = trim($nombre) !== '' ? trim($nombre) : null;
        }
        if ($newPassword !== null && $newPassword !== '') {
            if (mb_strlen($newPassword) < 6) {
                throw new \InvalidArgumentException('La contraseña debe tener al menos 6 caracteres.');
            }
            $sets[]   = 'password_hash = ?';
            $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE usuarios SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $affected = Database::getInstance()->execute($sql, $params)->rowCount();
        return $affected > 0;
    }

    public static function delete(int $id): bool
    {
        $affected = Database::getInstance()->execute(
            'DELETE FROM usuarios WHERE id = ?',
            [$id]
        )->rowCount();
        return $affected > 0;
    }
}