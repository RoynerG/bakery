<?php
declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Capa de acceso a datos.
 *
 * Singleton que devuelve una conexión PDO configurada según
 * el driver definido en config/config.php (sqlite | mysql).
 *
 * Métodos auxiliares: execute, fetchAll, fetchOne, fetchColumn,
 * lastInsertId, transaction.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $driver = DB_DRIVER;

        try {
            if ($driver === 'sqlite') {
                $this->initSqlite();
            } elseif ($driver === 'mysql') {
                $this->initMysql();
            } else {
                throw new RuntimeException("Driver de base de datos no soportado: $driver");
            }

            $this->pdo->setAttribute(PDO::ATTR_ERRMODE,            PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES,   false);
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo conectar a la base de datos: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    private function initSqlite(): void
    {
        $path = SQLITE_PATH;
        $dir  = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!file_exists($path)) {
            // Crear archivo vacío; el instalador (install.php) sembrará el esquema.
            touch($path);
        }
        $this->pdo = new PDO('sqlite:' . $path);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA journal_mode = WAL');
    }

    private function initMysql(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
        ]);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function driver(): string
    {
        return DB_DRIVER;
    }

    public function execute(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->execute($sql, $params)->fetchAll();
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        $row = $this->execute($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchColumn(string $sql, array $params = [], int $col = 0): mixed
    {
        return $this->execute($sql, $params)->fetchColumn($col);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function __clone() {}
    public function __wakeup() { throw new RuntimeException('No se permite deserializar la conexión.'); }
}
