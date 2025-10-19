<?php
/**
 * Migration Script: Add Public Sharing Columns to Galleries Table
 * 
 * Adds: share_hash, is_public, shared_at columns
 * Run this once to update existing database
 */

require_once __DIR__ . '/../server/db/connection.php';

try {
    $pdo = getDbConnection();
    
    echo "Starting migration...\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("PRAGMA table_info(galleries)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'name');
    
    $needsMigration = !in_array('share_hash', $columnNames);
    
    if (!$needsMigration) {
        echo "Migration already completed. Columns already exist.\n";
        exit(0);
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // Add new columns (SQLite doesn't support adding UNIQUE constraint with ALTER TABLE)
    echo "Adding share_hash column...\n";
    $pdo->exec("ALTER TABLE galleries ADD COLUMN share_hash TEXT");
    
    echo "Adding is_public column...\n";
    $pdo->exec("ALTER TABLE galleries ADD COLUMN is_public INTEGER DEFAULT 0");
    
    echo "Adding shared_at column...\n";
    $pdo->exec("ALTER TABLE galleries ADD COLUMN shared_at DATETIME");
    
    // Create unique index for share_hash (acts as UNIQUE constraint)
    echo "Creating unique index on share_hash...\n";
    $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_galleries_share_hash ON galleries(share_hash)");
    
    // Commit transaction
    $pdo->commit();
    
    echo "Migration completed successfully!\n";
    echo "Added columns: share_hash, is_public, shared_at\n";
    echo "Created index: idx_galleries_share_hash\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

