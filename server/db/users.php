<?php
/**
 * User Database Queries
 */

require_once __DIR__ . '/connection.php';

/**
 * Create a new user
 * 
 * @param string $email
 * @param string $passwordHash
 * @return int User ID
 */
function createUser($email, $passwordHash) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO users (email, password_hash, auth_tokens, created_at)
            VALUES (:email, :password_hash, :auth_tokens, datetime("now", "utc"))
        ');
        
        $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'auth_tokens' => '[]'
        ]);
        
        return (int)$pdo->lastInsertId();
        
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Unique constraint violation
            throw new Exception('Email already exists');
        }
        throw $e;
    }
}

/**
 * Get user by email
 * 
 * @param string $email
 * @return array|false User data or false if not found
 */
function getUserByEmail($email) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    
    return $stmt->fetch();
}

/**
 * Get user by ID
 * 
 * @param int $userId
 * @return array|false User data or false if not found
 */
function getUserById($userId) {
    $pdo = getDbConnection();
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    
    return $stmt->fetch();
}

/**
 * Add authentication token to user
 * 
 * @param int $userId
 * @param string $token
 * @param string $expiresAt
 * @return bool
 */
function addAuthToken($userId, $token, $expiresAt) {
    $pdo = getDbConnection();
    
    $user = getUserById($userId);
    if (!$user) {
        return false;
    }
    
    // Parse existing tokens
    $tokens = json_decode($user['auth_tokens'], true) ?: [];
    
    // Add new token
    $tokens[] = [
        'token' => $token,
        'expires_at' => $expiresAt
    ];
    
    // Update user
    $stmt = $pdo->prepare('UPDATE users SET auth_tokens = :tokens WHERE id = :id');
    $stmt->execute([
        'tokens' => json_encode($tokens),
        'id' => $userId
    ]);
    
    return true;
}

/**
 * Remove authentication token from user
 * 
 * @param int $userId
 * @param string $token
 * @return bool
 */
function removeAuthToken($userId, $token) {
    $pdo = getDbConnection();
    
    $user = getUserById($userId);
    if (!$user) {
        return false;
    }
    
    // Parse existing tokens
    $tokens = json_decode($user['auth_tokens'], true) ?: [];
    
    // Remove the token
    $tokens = array_filter($tokens, function($t) use ($token) {
        return $t['token'] !== $token;
    });
    
    // Reindex array
    $tokens = array_values($tokens);
    
    // Update user
    $stmt = $pdo->prepare('UPDATE users SET auth_tokens = :tokens WHERE id = :id');
    $stmt->execute([
        'tokens' => json_encode($tokens),
        'id' => $userId
    ]);
    
    return true;
}

/**
 * Verify if token exists for user
 * 
 * @param int $userId
 * @param string $token
 * @return bool
 */
function verifyUserToken($userId, $token) {
    $user = getUserById($userId);
    if (!$user) {
        return false;
    }
    
    $tokens = json_decode($user['auth_tokens'], true) ?: [];
    
    foreach ($tokens as $t) {
        if ($t['token'] === $token) {
            // Check if token is expired
            $expiresAt = strtotime($t['expires_at']);
            if ($expiresAt > time()) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Clean up expired tokens for user
 * 
 * @param int $userId
 * @return int Number of tokens removed
 */
function cleanupExpiredTokens($userId) {
    $pdo = getDbConnection();
    
    $user = getUserById($userId);
    if (!$user) {
        return 0;
    }
    
    $tokens = json_decode($user['auth_tokens'], true) ?: [];
    $originalCount = count($tokens);
    $now = time();
    
    // Keep only non-expired tokens
    $tokens = array_filter($tokens, function($t) use ($now) {
        $expiresAt = strtotime($t['expires_at']);
        return $expiresAt > $now;
    });
    
    $tokens = array_values($tokens);
    
    // Update user if any tokens were removed
    if (count($tokens) < $originalCount) {
        $stmt = $pdo->prepare('UPDATE users SET auth_tokens = :tokens WHERE id = :id');
        $stmt->execute([
            'tokens' => json_encode($tokens),
            'id' => $userId
        ]);
    }
    
    return $originalCount - count($tokens);
}




