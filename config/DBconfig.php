<?php
// Unified PostgreSQL configuration using PDO.
// Prefer DATABASE_URL / INTERNAL_DATABASE_URL (Render style). If not set,
// fall back to a local PostgreSQL instance.

// Try to read a full connection URL first
$database_url = getenv('INTERNAL_DATABASE_URL') ?: getenv('DATABASE_URL');

if ($database_url) {
    // Expected format: postgresql://user:pass@host:port/dbname
    $db_config = parse_url($database_url);
    if ($db_config === false) {
        die('Invalid DATABASE_URL format.');
    }

    $db_host = $db_config['host'] ?? 'localhost';
    $db_name = isset($db_config['path']) ? ltrim($db_config['path'], '/') : '';
    $db_user = $db_config['user'] ?? '';
    $db_pass = $db_config['pass'] ?? '';
    $db_port = $db_config['port'] ?? 5432;
    $use_ssl = true; // Render typically requires SSL
    $env_type = 'Production (Render/PostgreSQL via DATABASE_URL)';
} else {
    // Local PostgreSQL defaults (adjust as needed for your machine)
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_name = getenv('DB_NAME') ?: 'ojt_system';
    $db_user = getenv('DB_USER') ?: 'ojt_user';
    $db_pass = getenv('DB_PASS') ?: '';
    $db_port = getenv('DB_PORT') ?: 5432;
    $use_ssl = false;
    $env_type = 'Local (PostgreSQL)';
}

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    if ($use_ssl) {
        $dsn .= ';sslmode=require';
    }

    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $available_drivers = implode(', ', PDO::getAvailableDrivers());
    die("Database connection failed!<br>
         Environment: $env_type<br>
         DSN: $dsn<br>
         Available PDO drivers: $available_drivers<br>
         Error: " . $e->getMessage());
}
?>