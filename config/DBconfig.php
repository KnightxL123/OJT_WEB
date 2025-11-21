<?php
// Detect environment: Render uses environment variables, local doesn't
// Check for actual Render-specific environment variables
$isProduction = (getenv('DATABASE_URL') !== false && getenv('DATABASE_URL') !== '') 
             || (getenv('RENDER') !== false && getenv('RENDER') !== '') 
             || (getenv('RENDER_SERVICE_NAME') !== false && getenv('RENDER_SERVICE_NAME') !== '');

if ($isProduction) {
    // Production: PostgreSQL on Render
    // Use DATABASE_URL if available (Render's standard approach)
    $database_url = getenv('DATABASE_URL');
    
    if ($database_url) {
        // Parse DATABASE_URL: postgresql://user:pass@host:port/dbname
        $db_config = parse_url($database_url);
        $db_host = $db_config['host'];
        $db_name = ltrim($db_config['path'], '/');
        $db_user = $db_config['user'];
        $db_pass = $db_config['pass'];
        $db_port = isset($db_config['port']) ? $db_config['port'] : 5432;
    } else {
        // Fallback: use Internal Database URL format from your Render dashboard
        $db_host = getenv('DB_HOST') ?: 'dpg-d44fmvcvpn1nc73f3k1rg-a.singapore-postgres.render.com';
        $db_name = getenv('DB_NAME') ?: 'ojt_system';
        $db_user = getenv('DB_USER') ?: 'ojt_user';
        $db_pass = getenv('DB_PASS') ?: 'uIR97YPSCah0V5xDMxmy0SUdfuXYJEPH';
        $db_port = getenv('DB_PORT') ?: 5432;
    }
    $db_driver = 'pgsql';
} else {
    // Local: MySQL on XAMPP
    $db_host = 'localhost';
    $db_name = 'ojt';
    $db_user = 'root';
    $db_pass = '';
    $db_port = 3306;
    $db_driver = 'mysql';
}

// Use PDO for both environments (works with MySQL and PostgreSQL)
try {
    $dsn = "$db_driver:host=$db_host;port=$db_port;dbname=$db_name";
    $conn = new PDO($dsn, $db_user, $db_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $env_type = $isProduction ? 'Production (Render)' : 'Local (XAMPP)';
    $available_drivers = implode(', ', PDO::getAvailableDrivers());
    die("Database connection failed!<br>
         Environment: $env_type<br>
         Trying driver: $db_driver<br>
         Available drivers: $available_drivers<br>
         Error: " . $e->getMessage());
}
?>