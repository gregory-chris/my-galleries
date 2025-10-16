<?php
/**
 * Image Upload Handler
 * Handles file validation, thumbnail generation, and batch uploads
 */

require_once __DIR__ . '/../utils/logger.php';

// Upload configuration
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_BATCH_FILES', 20);
define('MAX_BATCH_SIZE', 200 * 1024 * 1024); // 200MB
define('UPLOAD_DIR', __DIR__ . '/../../public/uploads/');
define('THUMBNAIL_MAX_SIZE', 300);

// Allowed file types
define('ALLOWED_TYPES', [
    'image/jpeg' => ['jpg', 'jpeg'],
    'image/png' => ['png'],
    'image/gif' => ['gif'],
    'image/webp' => ['webp']
]);

/**
 * Validate uploaded file
 */
function validateFile($file, $requestId = null) {
    $errors = [];
    
    // Check if file has an error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
        logError('File upload error', ['error_code' => $file['error'], 'file' => $file['name']], null, $requestId);
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File size exceeds maximum limit of 10MB';
        logError('File size exceeds limit', ['size' => $file['size'], 'file' => $file['name']], null, $requestId);
    }
    
    // Get MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Check if MIME type is allowed
    if (!isset(ALLOWED_TYPES[$mimeType])) {
        $errors[] = 'File type not allowed. Allowed types: JPEG, PNG, GIF, WEBP';
        logError('Invalid MIME type', ['mime_type' => $mimeType, 'file' => $file['name']], null, $requestId);
    }
    
    // Get file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Verify extension matches MIME type
    if (isset(ALLOWED_TYPES[$mimeType]) && !in_array($extension, ALLOWED_TYPES[$mimeType])) {
        $errors[] = 'File extension does not match file type';
        logError('Extension mismatch', ['mime_type' => $mimeType, 'extension' => $extension, 'file' => $file['name']], null, $requestId);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'mime_type' => $mimeType,
        'extension' => $extension
    ];
}

/**
 * Validate batch of files
 */
function validateBatch($files, $requestId = null) {
    $errors = [];
    
    // Check number of files
    if (count($files) > MAX_BATCH_FILES) {
        $errors[] = "Too many files. Maximum " . MAX_BATCH_FILES . " files allowed per upload";
        logError('Too many files in batch', ['count' => count($files)], null, $requestId);
    }
    
    // Calculate total size
    $totalSize = array_sum(array_column($files, 'size'));
    if ($totalSize > MAX_BATCH_SIZE) {
        $errors[] = "Total batch size exceeds maximum limit of 200MB";
        logError('Batch size exceeds limit', ['total_size' => $totalSize], null, $requestId);
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'total_size' => $totalSize
    ];
}

/**
 * Generate unique filename
 */
function generateFilename($extension) {
    $timestamp = time();
    $hash = substr(bin2hex(random_bytes(6)), 0, 12);
    return "{$timestamp}_{$hash}.{$extension}";
}

/**
 * Create thumbnail from image
 */
function createThumbnail($sourcePath, $destPath, $mimeType, $requestId = null) {
    try {
        // Load source image based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $source = @imagecreatefromwebp($sourcePath);
                break;
            default:
                throw new Exception('Unsupported image type');
        }
        
        if (!$source) {
            throw new Exception('Failed to load source image');
        }
        
        // Get source dimensions
        $srcWidth = imagesx($source);
        $srcHeight = imagesy($source);
        
        // Calculate thumbnail dimensions (preserve aspect ratio, max 300x300)
        $ratio = min(THUMBNAIL_MAX_SIZE / $srcWidth, THUMBNAIL_MAX_SIZE / $srcHeight);
        $thumbWidth = round($srcWidth * $ratio);
        $thumbHeight = round($srcHeight * $ratio);
        
        // Create thumbnail
        $thumbnail = imagecreatetruecolor($thumbWidth, $thumbHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefilledrectangle($thumbnail, 0, 0, $thumbWidth, $thumbHeight, $transparent);
        }
        
        // Resample
        imagecopyresampled(
            $thumbnail, $source,
            0, 0, 0, 0,
            $thumbWidth, $thumbHeight,
            $srcWidth, $srcHeight
        );
        
        // Save thumbnail based on MIME type
        switch ($mimeType) {
            case 'image/jpeg':
                $result = imagejpeg($thumbnail, $destPath, 85);
                break;
            case 'image/png':
                $result = imagepng($thumbnail, $destPath, 8);
                break;
            case 'image/gif':
                $result = imagegif($thumbnail, $destPath);
                break;
            case 'image/webp':
                $result = imagewebp($thumbnail, $destPath, 85);
                break;
        }
        
        // Free memory
        imagedestroy($source);
        imagedestroy($thumbnail);
        
        if (!$result) {
            throw new Exception('Failed to save thumbnail');
        }
        
        return true;
        
    } catch (Exception $e) {
        logError('Thumbnail generation failed', ['error' => $e->getMessage(), 'source' => $sourcePath], $e, $requestId);
        return false;
    }
}

/**
 * Process uploaded file
 */
function processFile($file, $requestId = null) {
    // Validate file
    $validation = validateFile($file, $requestId);
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }
    
    $mimeType = $validation['mime_type'];
    $extension = $validation['extension'];
    
    // Generate filenames
    $filename = generateFilename($extension);
    $thumbnailFilename = 'thumb_' . $filename;
    
    $filePath = UPLOAD_DIR . $filename;
    $thumbnailPath = UPLOAD_DIR . $thumbnailFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => false, 'errors' => ['Failed to save uploaded file']];
    }
    
    // Get image dimensions
    $dimensions = @getimagesize($filePath);
    if (!$dimensions) {
        unlink($filePath);
        return ['success' => false, 'errors' => ['Failed to read image dimensions']];
    }
    
    $width = $dimensions[0];
    $height = $dimensions[1];
    
    // Create thumbnail
    if (!createThumbnail($filePath, $thumbnailPath, $mimeType, $requestId)) {
        unlink($filePath);
        return ['success' => false, 'errors' => ['Failed to create thumbnail']];
    }
    
    return [
        'success' => true,
        'filename' => $filename,
        'thumbnail_filename' => $thumbnailFilename,
        'original_filename' => $file['name'],
        'file_size' => $file['size'],
        'width' => $width,
        'height' => $height
    ];
}

/**
 * Process batch upload with rollback on failure
 */
function processBatchUpload($files, $requestId = null) {
    $uploadedFiles = [];
    $processedFiles = [];
    
    // Validate batch
    $batchValidation = validateBatch($files, $requestId);
    if (!$batchValidation['valid']) {
        return ['success' => false, 'errors' => $batchValidation['errors']];
    }
    
    // Process each file
    foreach ($files as $file) {
        $result = processFile($file, $requestId);
        
        if (!$result['success']) {
            // Rollback: delete all previously uploaded files
            foreach ($processedFiles as $processed) {
                $filePath = UPLOAD_DIR . $processed['filename'];
                $thumbPath = UPLOAD_DIR . $processed['thumbnail_filename'];
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
            
            logError('Batch upload failed, rolled back', [
                'failed_file' => $file['name'],
                'errors' => $result['errors'],
                'rolled_back_count' => count($processedFiles)
            ], null, $requestId);
            
            return ['success' => false, 'errors' => $result['errors']];
        }
        
        $processedFiles[] = $result;
    }
    
    return ['success' => true, 'files' => $processedFiles];
}

