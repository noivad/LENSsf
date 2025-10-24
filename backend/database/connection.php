<?php

declare(strict_types=1);

function getDbConnection(): mysqli {
    $host = getenv('DB_HOST') ?: 'mysql.cyberdrunktank.com';
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_DATABASE') ?: 'lenssf';
    $username = getenv('DB_USERNAME') ?: 'lenssfadmin';
    $password = getenv('DB_PASSWORD') ?: 'squads@dinah3darlin_twisting';

    $mysqli = new mysqli($host, $username, $password, $database, (int) $port);

    if ($mysqli->connect_errno) {
        throw new RuntimeException('Database connection failed: ' . $mysqli->connect_error);
    }

    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
