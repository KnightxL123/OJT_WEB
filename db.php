<?php
// Central database connection using PDO + PostgreSQL (for Render) with local fallbacks

// If you want to force Postgres everywhere, set these env vars in Render:
// PGHOST, PGPORT, PGUSER, PGPASSWORD, PGDATABASE

$pgHost = getenv('PGHOST');
$pgPort = getenv('PGPORT') ?: '5432';
$pgUser = getenv('PGUSER');
$pgPass = getenv('PGPASSWORD');
$pgDb   = getenv('PGDATABASE');

try {
    if ($pgHost && $pgUser && $pgDb) {
        // Render / production: use PostgreSQL over SSL
        $dsn = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDb};sslmode=require";
        $pdo = new PDO($dsn, $pgUser, $pgPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        // Local development fallback: MySQL on XAMPP (adjust if needed)
        $mysqlHost = 'localhost';
        $mysqlDb   = 'ojt';
        $mysqlUser = 'root';
        $mysqlPass = '';

        $dsn = "mysql:host={$mysqlHost};dbname={$mysqlDb};charset=utf8mb4";
        $pdo = new PDO($dsn, $mysqlUser, $mysqlPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
} catch (PDOException $e) {
    // In production you may want to log this instead of echoing
    die('Database connection failed: ' . $e->getMessage());
}
