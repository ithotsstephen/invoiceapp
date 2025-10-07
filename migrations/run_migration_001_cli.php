<?php
// Migration runner (safe) that does NOT include config.php to avoid requiring mysqli.
// It reads DB credentials from config.php defaults (or env) and then tries PDO.
// Usage: php migrations/run_migration_001_cli.php

$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/config.php';
if (!file_exists($configPath)) {
    echo "config.php not found at expected path: $configPath\n";
    exit(1);
}

$configText = file_get_contents($configPath);

function extractVar($text, $varName, $default = null) {
    // Try getenv fallback first
    $envPattern = "/\$\s*{$varName}\s*=\s*getenv\(\s*'{$varName}'\s*\)\s*\?\s*:\s*'([^']*)'/";
    if (preg_match($envPattern, $text, $m)) return $m[1];
    // Try direct assignment
    $pat = "/\$\s*{$varName}\s*=\s*'([^']*)'/";
    if (preg_match($pat, $text, $m2)) return $m2[1];
    return $default;
}

$DB_HOST = extractVar($configText, 'DB_HOST', 'localhost');
$DB_NAME = extractVar($configText, 'DB_NAME', '');
$DB_USER = extractVar($configText, 'DB_USER', '');
$DB_PASS = extractVar($configText, 'DB_PASS', '');

if (empty($DB_NAME) || empty($DB_USER)) {
    echo "Could not determine DB credentials from config.php. Please run the SQL manually or fix config.php.\n";
    exit(1);
}

echo "Using DB host: $DB_HOST, DB name: $DB_NAME, DB user: $DB_USER\n";

// Migration SQL
$sql = "ALTER TABLE `invoices` ADD COLUMN `approx_inr_value` DECIMAL(15,2) NULL DEFAULT NULL";

// Try PDO mysql
if (extension_loaded('pdo_mysql')) {
    try {
        $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Check column exists
        $stmt = $pdo->query("SHOW COLUMNS FROM `invoices` LIKE 'approx_inr_value'");
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($exists) {
            echo "Migration already applied: column `approx_inr_value` exists.\n";
            exit(0);
        }

        $pdo->exec($sql);
        echo "Migration applied via PDO: `approx_inr_value` added to invoices.\n";
        exit(0);
    } catch (PDOException $e) {
        echo "PDO error: " . $e->getMessage() . "\n";
        // fallthrough to print CLI command
    }
} else {
    echo "PDO MySQL extension not available in CLI PHP.\n";
}

// If we reach here, give the user an exact mysql CLI command they can run safely.
$escapedPass = addslashes($DB_PASS);
$cliExample = "mysql -h '{$DB_HOST}' -u '{$DB_USER}' -p'{$DB_PASS}' {$DB_NAME} -e \"{$sql};\"";

echo "\nCould not apply migration automatically. You can apply it manually using the mysql client.\n";
echo "Example command (runs without prompting for password):\n";
echo "{$cliExample}\n\n";
echo "Or run interactively (you'll be prompted for password):\n";
echo "mysql -h '{$DB_HOST}' -u '{$DB_USER}' -p {$DB_NAME} -e \"{$sql};\"\n";

exit(1);
