<?php
/**
 * Gallery Database Queries
 */

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../utils/logger.php';

/**
 * Get all galleries for a user
 * 
 * @param int $userId
 * @return array
 */
function getGalleriesByUser($userId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('
            SELECT 
                g.*,
                COUNT(i.id) as image_count
            FROM galleries g
            LEFT JOIN images i ON g.id = i.gallery_id
            WHERE g.user_id = :user_id
            GROUP BY g.id
            ORDER BY g.created_at DESC
        ');
        
        $stmt->execute(['user_id' => $userId]);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        logError('Failed to get galleries', ['error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Get gallery by ID
 * 
 * @param int $galleryId
 * @return array|false
 */
function getGalleryById($galleryId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('SELECT * FROM galleries WHERE id = :id');
        $stmt->execute(['id' => $galleryId]);
        
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        logError('Failed to get gallery', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Get gallery with images
 * 
 * @param int $galleryId
 * @return array|false
 */
function getGalleryWithImages($galleryId) {
    $pdo = getDbConnection();
    
    try {
        // Get gallery
        $gallery = getGalleryById($galleryId);
        
        if (!$gallery) {
            return false;
        }
        
        // Get images
        $stmt = $pdo->prepare('
            SELECT * FROM images 
            WHERE gallery_id = :gallery_id 
            ORDER BY uploaded_at ASC
        ');
        $stmt->execute(['gallery_id' => $galleryId]);
        $images = $stmt->fetchAll();
        
        $gallery['images'] = $images;
        
        return $gallery;
        
    } catch (PDOException $e) {
        logError('Failed to get gallery with images', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Create new gallery
 * 
 * @param int $userId
 * @param string $name
 * @param string $description
 * @return int Gallery ID
 */
function createGallery($userId, $name, $description = '') {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO galleries (user_id, name, description, created_at, updated_at)
            VALUES (:user_id, :name, :description, datetime("now", "utc"), datetime("now", "utc"))
        ');
        
        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description
        ]);
        
        return (int)$pdo->lastInsertId();
        
    } catch (PDOException $e) {
        logError('Failed to create gallery', ['user_id' => $userId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Update gallery
 * 
 * @param int $galleryId
 * @param string $name
 * @param string $description
 * @return bool
 */
function updateGallery($galleryId, $name, $description = '') {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('
            UPDATE galleries 
            SET name = :name, description = :description, updated_at = datetime("now", "utc")
            WHERE id = :id
        ');
        
        $stmt->execute([
            'id' => $galleryId,
            'name' => $name,
            'description' => $description
        ]);
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        logError('Failed to update gallery', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Delete gallery (CASCADE deletes images)
 * 
 * @param int $galleryId
 * @return int Number of affected rows
 */
function deleteGallery($galleryId) {
    $pdo = getDbConnection();
    
    try {
        // Get image count before delete (for logging)
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM images WHERE gallery_id = :gallery_id');
        $stmt->execute(['gallery_id' => $galleryId]);
        $imageCount = $stmt->fetch()['count'];
        
        // Delete gallery (CASCADE will delete images)
        $stmt = $pdo->prepare('DELETE FROM galleries WHERE id = :id');
        $stmt->execute(['id' => $galleryId]);
        
        $affectedRows = $stmt->rowCount();
        
        if ($affectedRows > 0) {
            logError('Gallery deleted with CASCADE', ['gallery_id' => $galleryId, 'image_count' => $imageCount], null, null, null);
        }
        
        return $affectedRows;
        
    } catch (PDOException $e) {
        logError('Failed to delete gallery', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Verify gallery belongs to user
 * 
 * @param int $galleryId
 * @param int $userId
 * @return bool
 */
function verifyGalleryOwnership($galleryId, $userId) {
    $gallery = getGalleryById($galleryId);
    
    if (!$gallery) {
        return false;
    }
    
    return $gallery['user_id'] == $userId;
}

/**
 * Validate gallery name
 * 
 * @param string $name
 * @return array|null Returns error array if invalid, null if valid
 */
function validateGalleryName($name) {
    if (empty($name)) {
        return ['field' => 'name', 'error' => 'Gallery name is required'];
    }
    
    if (strlen($name) < 1 || strlen($name) > 100) {
        return ['field' => 'name', 'error' => 'Gallery name must be between 1 and 100 characters'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9\s.,\-\'!]{1,100}$/', $name)) {
        return ['field' => 'name', 'error' => 'Gallery name contains invalid characters'];
    }
    
    return null;
}

/**
 * Validate gallery description
 * 
 * @param string $description
 * @return array|null Returns error array if invalid, null if valid
 */
function validateGalleryDescription($description) {
    if (strlen($description) > 500) {
        return ['field' => 'description', 'error' => 'Gallery description must be 500 characters or less'];
    }
    
    return null;
}

/**
 * Generate a unique 6-character share hash
 * 
 * @return string
 */
function generateUniqueShareHash() {
    $pdo = getDbConnection();
    $maxAttempts = 10;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Generate 6-character hash
        $hash = substr(bin2hex(random_bytes(3)), 0, 6);
        
        try {
            // Check if hash already exists
            $stmt = $pdo->prepare('SELECT id FROM galleries WHERE share_hash = :hash');
            $stmt->execute(['hash' => $hash]);
            
            if (!$stmt->fetch()) {
                // Hash is unique
                return $hash;
            }
            
            $attempts++;
        } catch (PDOException $e) {
            logError('Failed to check hash uniqueness', ['hash' => $hash, 'error' => $e->getMessage()], $e);
            $attempts++;
        }
    }
    
    // If we couldn't generate a unique hash after max attempts
    throw new Exception('Failed to generate unique share hash after ' . $maxAttempts . ' attempts');
}

/**
 * Enable public sharing for a gallery
 * 
 * @param int $galleryId
 * @param int $userId
 * @return array|false Returns gallery data with share_hash, or false on failure
 */
function enableGallerySharing($galleryId, $userId) {
    $pdo = getDbConnection();
    
    try {
        // Verify ownership
        if (!verifyGalleryOwnership($galleryId, $userId)) {
            return false;
        }
        
        // Get current gallery
        $gallery = getGalleryById($galleryId);
        
        if (!$gallery) {
            return false;
        }
        
        // If already has a hash, just enable it
        if (!empty($gallery['share_hash'])) {
            $stmt = $pdo->prepare('
                UPDATE galleries 
                SET is_public = 1, updated_at = datetime("now", "utc")
                WHERE id = :id
            ');
            $stmt->execute(['id' => $galleryId]);
        } else {
            // Generate new hash and enable
            $hash = generateUniqueShareHash();
            $stmt = $pdo->prepare('
                UPDATE galleries 
                SET share_hash = :hash, is_public = 1, shared_at = datetime("now", "utc"), updated_at = datetime("now", "utc")
                WHERE id = :id
            ');
            $stmt->execute([
                'id' => $galleryId,
                'hash' => $hash
            ]);
        }
        
        // Return updated gallery
        return getGalleryById($galleryId);
        
    } catch (Exception $e) {
        logError('Failed to enable gallery sharing', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Disable public sharing for a gallery
 * 
 * @param int $galleryId
 * @param int $userId
 * @return bool
 */
function disableGallerySharing($galleryId, $userId) {
    $pdo = getDbConnection();
    
    try {
        // Verify ownership
        if (!verifyGalleryOwnership($galleryId, $userId)) {
            return false;
        }
        
        // Set is_public to 0 (keep hash for potential re-enabling)
        $stmt = $pdo->prepare('
            UPDATE galleries 
            SET is_public = 0, updated_at = datetime("now", "utc")
            WHERE id = :id
        ');
        $stmt->execute(['id' => $galleryId]);
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        logError('Failed to disable gallery sharing', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Get gallery by share hash (public endpoint)
 * 
 * @param string $hash
 * @return array|false Returns gallery with images if public, false otherwise
 */
function getGalleryByShareHash($hash) {
    $pdo = getDbConnection();
    
    try {
        // Get gallery by hash (only if is_public = 1)
        $stmt = $pdo->prepare('
            SELECT * FROM galleries 
            WHERE share_hash = :hash AND is_public = 1
        ');
        $stmt->execute(['hash' => $hash]);
        $gallery = $stmt->fetch();
        
        if (!$gallery) {
            return false;
        }
        
        // Get images
        $stmt = $pdo->prepare('
            SELECT * FROM images 
            WHERE gallery_id = :gallery_id 
            ORDER BY uploaded_at ASC
        ');
        $stmt->execute(['gallery_id' => $gallery['id']]);
        $images = $stmt->fetchAll();
        
        $gallery['images'] = $images;
        
        return $gallery;
        
    } catch (PDOException $e) {
        logError('Failed to get gallery by share hash', ['hash' => $hash, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}




