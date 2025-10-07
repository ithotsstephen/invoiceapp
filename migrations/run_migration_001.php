<?php
// Simple migration runner for adding approx_inr_value to invoices
// Usage: php migrations/run_migration_001.php

require_once __DIR__ . '/../config.php';

$checkSql = "SHOW COLUMNS FROM `invoices` LIKE 'approx_inr_value'";
$result = $conn->query($checkSql);
if ($result === false) {
    echo "ERROR: Failed to check columns: " . $conn->error . PHP_EOL;
    exit(1);
}

if ($result->num_rows > 0) {
    echo "Migration already applied: column `approx_inr_value` exists.\n";
    exit(0);
}

$alter = "ALTER TABLE `invoices` ADD COLUMN `approx_inr_value` DECIMAL(15,2) NULL DEFAULT NULL";
if ($conn->query($alter) === TRUE) {
    echo "Migration applied: `approx_inr_value` added to invoices.\n";
    exit(0);
} else {
    echo "ERROR applying migration: " . $conn->error . PHP_EOL;
    exit(1);
}
