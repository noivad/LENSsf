<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dbType = defined('DB_TYPE') ? strtolower((string) DB_TYPE) : 'mysql';

        if ($dbType !== 'mysql') {
            throw new RuntimeException('Only MySQL is supported in the current configuration.');
        }

        $host = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
        $name = defined('DB_NAME') ? DB_NAME : 'lensf7';
        $user = defined('DB_USER') ? DB_USER : 'root';
        $pass = defined('DB_PASS') ? DB_PASS : '';
        $port = defined('DB_PORT') ? (int) DB_PORT : 3306;

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);

        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            self::initializeSchema();
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }

        return self::$pdo;
    }

    private static function initializeSchema(): void
    {
        $schemaFile = __DIR__ . '/../database/schema_mysql.sql';
        if (!file_exists($schemaFile)) {
            return;
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException('Failed to read schema file.');
        }

        try {
            self::$pdo->beginTransaction();
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
                if ($statement !== '') {
                    self::$pdo->exec($statement);
                }
            }
            self::$pdo->commit();
        } catch (PDOException $e) {
            self::$pdo->rollBack();
            throw new RuntimeException('Failed to initialize database schema: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
