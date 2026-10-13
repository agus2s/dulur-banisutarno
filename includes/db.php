<?php
// © 2026 Agus Supriyadi — Blog Dulur. Hak cipta dilindungi undang-undang.
require_once __DIR__ . '/../config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        // Connect tanpa DB dulu (untuk install), lalu use DB jika ada
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
        try {
            $pdo->exec('USE `' . str_replace('`', '', DB_NAME) . '`');
        } catch (PDOException $e) {
            // DB belum dibuat, biarkan install.php yang membuat
        }
    }
    return $pdo;
}
