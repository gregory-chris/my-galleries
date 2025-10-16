<?php
/**
 * Database Connection Utility
 * Provides PDO connection to SQLite database
 */

function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $dbPath = __DIR__ . '/../../database/galleries.db';
        
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign key constraints
            $pdo->exec('PRAGMA foreign_keys = ON');
            
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    return $pdo;
}




