<?php

try {
    $dsn = 'mysql:host=db;port=3306;dbname=coprra;charset=utf8mb4';
    $pdo = new PDO($dsn, 'coprra', 'coprra', [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $stmt = $pdo->query('SELECT 1');
    echo "ok:\n".$stmt->fetchColumn()."\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n";
}
