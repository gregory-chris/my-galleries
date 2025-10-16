<?php
/**
 * Image Database Queries
 */

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/../utils/logger.php';

/**
 * Create image record
 */
function createImage($galleryId, $filename, $originalFilename, $thumbnailFilename, $fileSize, $width, $height) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('
            INSERT INTO images (
                gallery_id, filename, original_filename, thumbnail_filename,
                file_size, width, height, uploaded_at
            )
            VALUES (
                :gallery_id, :filename, :original_filename, :thumbnail_filename,
                :file_size, :width, :height, datetime("now", "utc")
            )
        ');
        
        $stmt->execute([
            'gallery_id' => $galleryId,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'thumbnail_filename' => $thumbnailFilename,
            'file_size' => $fileSize,
            'width' => $width,
            'height' => $height
        ]);
        
        return (int)$pdo->lastInsertId();
        
    } catch (PDOException $e) {
        logError('Failed to create image record', [
            'gallery_id' => $galleryId,
            'filename' => $filename,
            'error' => $e->getMessage()
        ], $e);
        throw $e;
    }
}

/**
 * Get image by ID
 */
function getImageById($imageId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('SELECT * FROM images WHERE id = :id');
        $stmt->execute(['id' => $imageId]);
        
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        logError('Failed to get image', ['image_id' => $imageId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Get all images for a gallery
 */
function getImagesByGallery($galleryId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('
            SELECT * FROM images 
            WHERE gallery_id = :gallery_id 
            ORDER BY uploaded_at ASC
        ');
        $stmt->execute(['gallery_id' => $galleryId]);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        logError('Failed to get images', ['gallery_id' => $galleryId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Delete image
 */
function deleteImage($imageId) {
    $pdo = getDbConnection();
    
    try {
        $stmt = $pdo->prepare('DELETE FROM images WHERE id = :id');
        $stmt->execute(['id' => $imageId]);
        
        return $stmt->rowCount() > 0;
        
    } catch (PDOException $e) {
        logError('Failed to delete image', ['image_id' => $imageId, 'error' => $e->getMessage()], $e);
        throw $e;
    }
}

/**
 * Get gallery ID for an image
 */
function getImageGalleryId($imageId) {
    $image = getImageById($imageId);
    return $image ? $image['gallery_id'] : false;
}

