<?php
/**
 * API Server Entry Point
 * Slim Framework application with request logging middleware
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/utils/logger.php';
require_once __DIR__ . '/auth/jwt.php';
require_once __DIR__ . '/db/users.php';
require_once __DIR__ . '/db/galleries.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

// Create Slim app
$app = AppFactory::create();

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Handle preflight requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add body parsing middleware
$app->addBodyParsingMiddleware();

/**
 * Request Logging Middleware
 * Generates request_id, logs all requests with timing
 */
$app->add(function (Request $request, $handler) {
    // Generate unique request ID
    $requestId = generateRequestId();
    $request = $request->withAttribute('request_id', $requestId);
    
    // Capture start time
    $startTime = microtime(true);
    
    // Process request
    $response = $handler->handle($request);
    
    // Calculate duration
    $duration = (microtime(true) - $startTime) * 1000;
    
    // Extract user ID if authenticated
    $userId = $request->getAttribute('user_id');
    
    // Log request
    logRequest(
        $request->getMethod(),
        $request->getUri()->getPath(),
        $response->getStatusCode(),
        $duration,
        $userId,
        $requestId
    );
    
    // Add request ID to response headers
    return $response->withHeader('X-Request-ID', $requestId);
});

/**
 * Authentication Middleware
 * Validates JWT token and attaches user to request
 */
$authMiddleware = function (Request $request, $handler) {
    $requestId = $request->getAttribute('request_id');
    $token = getTokenFromHeader();
    
    if (!$token) {
        logAuth('token_validation', null, false, null, $requestId);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => 'Missing authentication token',
            'request_id' => $requestId
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    // Verify JWT
    $decoded = verifyJWT($token);
    if (!$decoded) {
        logAuth('token_validation', null, false, null, $requestId);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => 'Invalid or expired token',
            'request_id' => $requestId
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    // Verify token exists in database
    $userId = $decoded->user_id;
    if (!verifyUserToken($userId, $token)) {
        logAuth('token_validation', null, false, $userId, $requestId);
        
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => 'Token has been revoked',
            'request_id' => $requestId
        ]));
        return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }
    
    // Attach user to request
    $request = $request->withAttribute('user_id', $userId);
    $request = $request->withAttribute('user_email', $decoded->email);
    
    return $handler->handle($request);
};

/**
 * POST /api/auth/signup
 * Create new user account
 */
$app->post('/api/auth/signup', function (Request $request, Response $response) {
    $requestId = $request->getAttribute('request_id');
    $data = $request->getParsedBody();
    
    // Validate input
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logError('Signup failed: Invalid email', ['email' => $email], null, $requestId);
        $response->getBody()->write(json_encode([
            'error' => 'Invalid email address',
            'request_id' => $requestId
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    if (strlen($password) < 8) {
        logError('Signup failed: Password too short', [], null, $requestId);
        $response->getBody()->write(json_encode([
            'error' => 'Password must be at least 8 characters',
            'request_id' => $requestId
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    try {
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Create user
        $userId = createUser($email, $passwordHash);
        
        // Generate JWT
        $jwtData = generateJWT($userId, $email);
        
        // Store token in database
        addAuthToken($userId, $jwtData['token'], $jwtData['expires_at']);
        
        // Log successful signup
        logAuth('signup', $email, true, $userId, $requestId);
        
        // Return response
        $response->getBody()->write(json_encode([
            'token' => $jwtData['token'],
            'user' => [
                'id' => $userId,
                'email' => $email,
                'created_at' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ]));
        
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logAuth('signup', $email, false, null, $requestId);
        logError('Signup error', ['error' => $e->getMessage()], $e, $requestId);
        
        $response->getBody()->write(json_encode([
            'error' => $e->getMessage() === 'Email already exists' 
                ? 'Email already exists' 
                : 'Signup failed',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
});

/**
 * POST /api/auth/login
 * User login
 */
$app->post('/api/auth/login', function (Request $request, Response $response) {
    $requestId = $request->getAttribute('request_id');
    $data = $request->getParsedBody();
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    try {
        // Get user
        $user = getUserByEmail($email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            logAuth('login', $email, false, null, $requestId);
            
            $response->getBody()->write(json_encode([
                'error' => 'Invalid email or password',
                'request_id' => $requestId
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // Generate JWT
        $jwtData = generateJWT($user['id'], $user['email']);
        
        // Store token in database
        addAuthToken($user['id'], $jwtData['token'], $jwtData['expires_at']);
        
        // Clean up expired tokens
        cleanupExpiredTokens($user['id']);
        
        // Log successful login
        logAuth('login', $email, true, $user['id'], $requestId);
        
        // Return response
        $response->getBody()->write(json_encode([
            'token' => $jwtData['token'],
            'user' => [
                'id' => $user['id'],
                'email' => $user['email']
            ]
        ]));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logAuth('login', $email, false, null, $requestId);
        logError('Login error', ['error' => $e->getMessage()], $e, $requestId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Login failed',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
});

/**
 * POST /api/auth/logout
 * User logout (invalidate current token)
 */
$app->post('/api/auth/logout', function (Request $request, Response $response) use ($authMiddleware) {
    $requestId = $request->getAttribute('request_id');
    $userId = $request->getAttribute('user_id');
    $email = $request->getAttribute('user_email');
    $token = getTokenFromHeader();
    
    try {
        // Remove token from database
        removeAuthToken($userId, $token);
        
        // Log logout
        logAuth('logout', $email, true, $userId, $requestId);
        
        $response->getBody()->write(json_encode([
            'message' => 'Logged out successfully'
        ]));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logError('Logout error', ['error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Logout failed',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

/**
 * GET /api/auth/me
 * Get current user info
 */
$app->get('/api/auth/me', function (Request $request, Response $response) use ($authMiddleware) {
    $userId = $request->getAttribute('user_id');
    
    try {
        $user = getUserById($userId);
        
        if (!$user) {
            $requestId = $request->getAttribute('request_id');
            $response->getBody()->write(json_encode([
                'error' => 'User not found',
                'request_id' => $requestId
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode([
            'id' => $user['id'],
            'email' => $user['email'],
            'created_at' => $user['created_at']
        ]));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        $requestId = $request->getAttribute('request_id');
        logError('Get user error', ['error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Failed to get user info',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

/**
 * GET /api/galleries
 * Get all galleries for current user
 */
$app->get('/api/galleries', function (Request $request, Response $response) use ($authMiddleware) {
    $userId = $request->getAttribute('user_id');
    
    try {
        $galleries = getGalleriesByUser($userId);
        
        $response->getBody()->write(json_encode([
            'galleries' => $galleries
        ]));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        $requestId = $request->getAttribute('request_id');
        logError('Get galleries error', ['error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Failed to get galleries',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

/**
 * POST /api/galleries
 * Create new gallery
 */
$app->post('/api/galleries', function (Request $request, Response $response) use ($authMiddleware) {
    $requestId = $request->getAttribute('request_id');
    $userId = $request->getAttribute('user_id');
    $data = $request->getParsedBody();
    
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    
    // Validate name
    $nameError = validateGalleryName($name);
    if ($nameError) {
        logError('Gallery validation failed', $nameError, null, $requestId, $userId);
        $response->getBody()->write(json_encode([
            'error' => $nameError['error'],
            'request_id' => $requestId
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    // Validate description
    $descError = validateGalleryDescription($description);
    if ($descError) {
        logError('Gallery validation failed', $descError, null, $requestId, $userId);
        $response->getBody()->write(json_encode([
            'error' => $descError['error'],
            'request_id' => $requestId
        ]));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
    }
    
    try {
        $galleryId = createGallery($userId, $name, $description);
        $gallery = getGalleryById($galleryId);
        $gallery['image_count'] = 0;
        
        $response->getBody()->write(json_encode($gallery));
        
        return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logError('Create gallery error', ['error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Failed to create gallery',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

/**
 * GET /api/galleries/{id}
 * Get gallery with images
 */
$app->get('/api/galleries/{id}', function (Request $request, Response $response, array $args) use ($authMiddleware) {
    $requestId = $request->getAttribute('request_id');
    $userId = $request->getAttribute('user_id');
    $galleryId = (int)$args['id'];
    
    try {
        // Verify ownership
        if (!verifyGalleryOwnership($galleryId, $userId)) {
            $response->getBody()->write(json_encode([
                'error' => 'Gallery not found',
                'request_id' => $requestId
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $gallery = getGalleryWithImages($galleryId);
        
        if (!$gallery) {
            $response->getBody()->write(json_encode([
                'error' => 'Gallery not found',
                'request_id' => $requestId
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        $response->getBody()->write(json_encode($gallery));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logError('Get gallery error', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Failed to get gallery',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

/**
 * PUT /api/galleries/{id}
 * Update gallery
 */
$app->put('/api/galleries/{id}', function (Request $request, Response $response, array $args) use ($authMiddleware) {
    $requestId = $request->getAttribute('request_id');
    $userId = $request->getAttribute('user_id');
    $galleryId = (int)$args['id'];
    $data = $request->getParsedBody();
    
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    
    try {
        // Verify ownership
        if (!verifyGalleryOwnership($galleryId, $userId)) {
            $response->getBody()->write(json_encode([
                'error' => 'Gallery not found',
                'request_id' => $requestId
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Validate name
        $nameError = validateGalleryName($name);
        if ($nameError) {
            logError('Gallery validation failed', $nameError, null, $requestId, $userId);
            $response->getBody()->write(json_encode([
                'error' => $nameError['error'],
                'request_id' => $requestId
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        // Validate description
        $descError = validateGalleryDescription($description);
        if ($descError) {
            logError('Gallery validation failed', $descError, null, $requestId, $userId);
            $response->getBody()->write(json_encode([
                'error' => $descError['error'],
                'request_id' => $requestId
            ]));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
        
        updateGallery($galleryId, $name, $description);
        $gallery = getGalleryById($galleryId);
        
        // Get image count
        $stmt = getDbConnection()->prepare('SELECT COUNT(*) as count FROM images WHERE gallery_id = :gallery_id');
        $stmt->execute(['gallery_id' => $galleryId]);
        $gallery['image_count'] = $stmt->fetch()['count'];
        
        $response->getBody()->write(json_encode($gallery));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logError('Update gallery error', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Failed to update gallery',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

/**
 * DELETE /api/galleries/{id}
 * Delete gallery and all its images
 */
$app->delete('/api/galleries/{id}', function (Request $request, Response $response, array $args) use ($authMiddleware) {
    $requestId = $request->getAttribute('request_id');
    $userId = $request->getAttribute('user_id');
    $galleryId = (int)$args['id'];
    
    try {
        // Verify ownership
        if (!verifyGalleryOwnership($galleryId, $userId)) {
            $response->getBody()->write(json_encode([
                'error' => 'Gallery not found',
                'request_id' => $requestId
            ]));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
        
        // Get images to delete files
        $gallery = getGalleryWithImages($galleryId);
        
        // Delete image files
        foreach ($gallery['images'] as $image) {
            $imagePath = __DIR__ . '/../public/uploads/' . $image['filename'];
            $thumbPath = __DIR__ . '/../public/uploads/' . $image['thumbnail_filename'];
            
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
        }
        
        // Delete gallery (CASCADE will delete image records)
        deleteGallery($galleryId);
        
        $response->getBody()->write(json_encode([
            'message' => 'Gallery deleted successfully'
        ]));
        
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
        
    } catch (Exception $e) {
        logError('Delete gallery error', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e, $requestId, $userId);
        
        $response->getBody()->write(json_encode([
            'error' => 'Failed to delete gallery',
            'details' => $e->getMessage(),
            'request_id' => $requestId
        ]));
        
        return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
})->add($authMiddleware);

// Run app
$app->run();

