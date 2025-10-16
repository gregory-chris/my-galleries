<?php
/**
 * Database Initialization Script
 * Creates the SQLite database and runs the schema
 */

$dbPath = __DIR__ . '/galleries.db';
$schemaPath = __DIR__ . '/schema.sql';

try {
    // Create database connection
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Enable foreign key constraints
    $pdo->exec('PRAGMA foreign_keys = ON');
    
    // Read and execute schema
    $schema = file_get_contents($schemaPath);
    $pdo->exec($schema);
    
    echo "✓ Database created successfully at: $dbPath\n";
    echo "✓ Schema executed successfully\n";
    echo "✓ Foreign key constraints enabled\n";
    
    // Test connection
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n✓ Tables created:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}




