<?php
/**
 * JWT Helper Functions
 * Token generation and validation using firebase/php-jwt
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// JWT Secret - In production, this should be from environment variable
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production-' . md5(__DIR__));
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_HOURS', 24);

/**
 * Generate JWT token for user
 * 
 * @param int $userId
 * @param string $email
 * @return array ['token' => string, 'expires_at' => string]
 */
function generateJWT($userId, $email) {
    $issuedAt = time();
    $expirationTime = $issuedAt + (JWT_EXPIRATION_HOURS * 3600); // 24 hours
    
    $payload = [
        'iat' => $issuedAt,
        'exp' => $expirationTime,
        'user_id' => $userId,
        'email' => $email,
    ];
    
    $token = JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    
    return [
        'token' => $token,
        'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $expirationTime)
    ];
}

/**
 * Verify and decode JWT token
 * 
 * @param string $token
 * @return object|false Decoded token payload or false if invalid
 */
function verifyJWT($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
        return $decoded;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Extract user ID from JWT token
 * 
 * @param string $token
 * @return int|false User ID or false if invalid
 */
function getUserIdFromToken($token) {
    $decoded = verifyJWT($token);
    if ($decoded && isset($decoded->user_id)) {
        return $decoded->user_id;
    }
    return false;
}

/**
 * Get token from Authorization header
 * 
 * @return string|null Token or null if not found
 */
function getTokenFromHeader() {
    $headers = getallheaders();
    
    if (isset($headers['Authorization'])) {
        // Expected format: "Bearer {token}"
        $parts = explode(' ', $headers['Authorization']);
        if (count($parts) === 2 && $parts[0] === 'Bearer') {
            return $parts[1];
        }
    }
    
    return null;
}

/**
 * Truncate JWT token for logging (first 10 characters only)
 * 
 * @param string $token
 * @return string
 */
function truncateTokenForLogging($token) {
    return substr($token, 0, 10) . '...';
}




