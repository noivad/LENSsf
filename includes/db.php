<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            if (DB_TYPE === 'sqlite') {
                $dir = dirname(DB_PATH);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                self::$pdo = new PDO('sqlite:' . DB_PATH);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->exec('PRAGMA foreign_keys = ON');

                self::initializeSchema();
            } else {
                self::$pdo = new PDO(
                    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_NAME),
                    DB_USER,
                    DB_PASS
                );
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

        return self::$pdo;
    }

    private static function initializeSchema(): void
    {
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) {
            return;
        }

        $result = self::$pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if ($result->fetchColumn()) {
            return;
        }

        $sql = file_get_contents($schemaFile);
        self::$pdo->exec($sql);
    }
}
