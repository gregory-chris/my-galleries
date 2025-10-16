<?php
/**
 * Logging Utility Functions
 * Provides structured JSON logging for all application operations
 */

// Log levels (prefixed with APP_ to avoid conflicts with PHP's built-in LOG_* constants)
define('APP_LOG_DEBUG', 'DEBUG');
define('APP_LOG_INFO', 'INFO');
define('APP_LOG_WARNING', 'WARNING');
define('APP_LOG_ERROR', 'ERROR');

// Log types
define('LOG_TYPE_REQUEST', 'request');
define('LOG_TYPE_AUTH', 'auth');
define('LOG_TYPE_UPLOAD', 'upload');
define('LOG_TYPE_ERROR', 'error');
define('LOG_TYPE_DATABASE', 'database');

// Log directory
define('LOG_DIR', __DIR__ . '/../../logs/');

// Environment (can be set via environment variable)
define('IS_DEVELOPMENT', getenv('APP_ENV') !== 'production');

/**
 * Write a log entry to file
 */
function writeLog($level, $type, $message, $context = [], $requestId = null, $userId = null) {
    global $_SERVER;
    
    // Skip DEBUG logs in production
    if (!IS_DEVELOPMENT && $level === APP_LOG_DEBUG) {
        return;
    }
    
    // Build log entry
    $logEntry = [
        'timestamp' => gmdate('Y-m-d\TH:i:s.v\Z'),
        'level' => $level,
        'type' => $type,
        'message' => $message,
        'request_id' => $requestId ?? generateRequestId(),
    ];
    
    // Add optional fields
    if ($userId !== null) {
        $logEntry['user_id'] = $userId;
    }
    
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $logEntry['ip_address'] = $_SERVER['REMOTE_ADDR'];
    }
    
    if (!empty($context)) {
        $logEntry['context'] = $context;
    }
    
    // Convert to JSON
    $jsonLine = json_encode($logEntry, JSON_UNESCAPED_SLASHES) . "\n";
    
    // Determine log file based on type
    $logFile = LOG_DIR . $type . '.log';
    
    // Rotate logs if needed
    rotateLogs();
    
    // Write to type-specific log file
    file_put_contents($logFile, $jsonLine, FILE_APPEND | LOCK_EX);
    
    // Also write ERROR level to error.log
    if ($level === APP_LOG_ERROR && $type !== LOG_TYPE_ERROR) {
        $errorLogFile = LOG_DIR . 'error.log';
        file_put_contents($errorLogFile, $jsonLine, FILE_APPEND | LOCK_EX);
    }
    
    // In development, also output to stdout
    if (IS_DEVELOPMENT) {
        error_log("[{$level}] {$message}");
    }
}

/**
 * Generate a unique request ID
 */
function generateRequestId() {
    return substr(bin2hex(random_bytes(6)), 0, 12);
}

/**
 * Log API request
 */
function logRequest($method, $path, $statusCode, $durationMs, $userId = null, $requestId = null) {
    $context = [
        'method' => $method,
        'path' => $path,
        'status_code' => $statusCode,
        'duration_ms' => round($durationMs, 2),
    ];
    
    writeLog(APP_LOG_INFO, LOG_TYPE_REQUEST, 'API request completed', $context, $requestId, $userId);
}

/**
 * Log error
 */
function logError($message, $context = [], $exception = null, $requestId = null, $userId = null) {
    // Add exception details if provided
    if ($exception instanceof Exception) {
        $context['exception'] = get_class($exception);
        $context['exception_message'] = $exception->getMessage();
        $context['stack_trace'] = $exception->getTraceAsString();
    }
    
    writeLog(APP_LOG_ERROR, LOG_TYPE_ERROR, $message, $context, $requestId, $userId);
}

/**
 * Log authentication event
 */
function logAuth($event, $email, $success, $userId = null, $requestId = null) {
    $context = [
        'event' => $event,
        'email' => $email,
        'success' => $success,
    ];
    
    $level = $success ? APP_LOG_INFO : APP_LOG_WARNING;
    $message = ucfirst($event) . ($success ? ' successful' : ' failed');
    
    writeLog($level, LOG_TYPE_AUTH, $message, $context, $requestId, $userId);
}

/**
 * Log file upload operation
 */
function logUpload($galleryId, $fileCount, $totalSize, $success, $errorDetails = null, $requestId = null, $userId = null) {
    $context = [
        'gallery_id' => $galleryId,
        'file_count' => $fileCount,
        'total_size' => $totalSize,
        'success' => $success,
    ];
    
    if ($errorDetails !== null) {
        $context['error'] = $errorDetails;
    }
    
    $level = $success ? APP_LOG_INFO : APP_LOG_ERROR;
    $message = $success 
        ? "File upload successful: {$fileCount} files, " . formatBytes($totalSize)
        : "File upload failed: {$errorDetails}";
    
    writeLog($level, LOG_TYPE_UPLOAD, $message, $context, $requestId, $userId);
}

/**
 * Log database operation (development only)
 */
function logDatabase($query, $success, $errorMessage = null, $requestId = null) {
    if (!IS_DEVELOPMENT) {
        return; // Only log database queries in development
    }
    
    $context = [
        'query' => $query,
        'success' => $success,
    ];
    
    if ($errorMessage !== null) {
        $context['error'] = $errorMessage;
    }
    
    $level = $success ? APP_LOG_DEBUG : APP_LOG_ERROR;
    $message = $success ? 'Database query executed' : 'Database query failed';
    
    writeLog($level, LOG_TYPE_DATABASE, $message, $context, $requestId);
}

/**
 * Rotate logs monthly
 */
function rotateLogs() {
    static $rotated = false;
    
    // Only check once per request
    if ($rotated) {
        return;
    }
    $rotated = true;
    
    $logTypes = [LOG_TYPE_REQUEST, LOG_TYPE_AUTH, LOG_TYPE_UPLOAD, LOG_TYPE_ERROR, LOG_TYPE_DATABASE];
    $currentMonth = gmdate('Y-m');
    
    foreach ($logTypes as $type) {
        $logFile = LOG_DIR . $type . '.log';
        
        // Check if log file exists and needs rotation
        if (file_exists($logFile)) {
            $fileModTime = filemtime($logFile);
            $fileMonth = gmdate('Y-m', $fileModTime);
            
            // If the log file is from a previous month, rotate it
            if ($fileMonth !== $currentMonth) {
                $rotatedFile = LOG_DIR . $type . '-' . $fileMonth . '.log';
                rename($logFile, $rotatedFile);
            }
        }
    }
    
    // Clean up old logs (keep forever, but this can be modified if needed)
    // cleanupOldLogs();
}

/**
 * Clean up old log files (optional - currently logs are kept forever as per spec)
 */
function cleanupOldLogs($daysToKeep = 30) {
    $files = glob(LOG_DIR . '*.log');
    $now = time();
    
    foreach ($files as $file) {
        // Skip current day logs
        if (strpos($file, '-') === false) {
            continue;
        }
        
        $fileAge = $now - filemtime($file);
        $daysOld = $fileAge / 86400; // seconds in a day
        
        if ($daysOld > $daysToKeep) {
            unlink($file);
        }
    }
}

/**
 * Format bytes to human-readable string
 */
function formatBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}




