#!/usr/bin/env php
<?php
/**
 * Generate Frontend Configuration from PHP Settings
 * 
 * This script reads PHP configuration values and creates a JSON file
 * that the frontend can use for validation.
 * 
 * Run this script whenever PHP configuration changes:
 * php generate-config.php
 */

// Function to convert PHP ini size values to bytes
function convertToBytes($value) {
    $value = trim($value);
    $unit = strtolower($value[strlen($value) - 1]);
    $value = (int)$value;
    
    switch ($unit) {
        case 'g':
            $value *= 1024 * 1024 * 1024;
            break;
        case 'm':
            $value *= 1024 * 1024;
            break;
        case 'k':
            $value *= 1024;
            break;
    }
    
    return $value;
}

// Get PHP configuration values
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');

// Convert to bytes
$uploadMaxFilesizeBytes = convertToBytes($uploadMaxFilesize);
$postMaxSizeBytes = convertToBytes($postMaxSize);
$memoryLimitBytes = convertToBytes($memoryLimit);

// Get application-specific limits from backend code
// These are defined in server/uploads/upload_handler.php
$maxBatchFiles = 20;

// Create configuration object
$config = [
    'upload' => [
        'maxFileSize' => $uploadMaxFilesizeBytes,
        'maxFileSizeFormatted' => $uploadMaxFilesize,
        'maxTotalSize' => $postMaxSizeBytes,
        'maxTotalSizeFormatted' => $postMaxSize,
        'maxFiles' => $maxBatchFiles,
        'allowedTypes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp'
        ],
        'allowedExtensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp'
        ]
    ],
    'system' => [
        'memoryLimit' => $memoryLimitBytes,
        'memoryLimitFormatted' => $memoryLimit,
        'maxExecutionTime' => (int)$maxExecutionTime
    ],
    'generated' => date('Y-m-d\TH:i:s\Z'),
    'phpVersion' => PHP_VERSION
];

// Output file path
$outputFile = __DIR__ . '/frontend/src/config/upload-limits.json';

// Ensure directory exists
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Write JSON file
$jsonContent = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($outputFile, $jsonContent);

// Output success message
echo "✓ Configuration generated successfully!\n";
echo "\n";
echo "Output file: frontend/src/config/upload-limits.json\n";
echo "\n";
echo "Upload Limits:\n";
echo "  - Max file size: {$uploadMaxFilesize} ({$uploadMaxFilesizeBytes} bytes)\n";
echo "  - Max total size: {$postMaxSize} ({$postMaxSizeBytes} bytes)\n";
echo "  - Max files per batch: {$maxBatchFiles}\n";
echo "  - Memory limit: {$memoryLimit}\n";
echo "  - Max execution time: {$maxExecutionTime}s\n";
echo "\n";
echo "Allowed types: " . implode(', ', $config['upload']['allowedExtensions']) . "\n";
echo "\n";

// Validate configuration
$warnings = [];

if ($uploadMaxFilesizeBytes < 10 * 1024 * 1024) {
    $warnings[] = "WARNING: upload_max_filesize is less than 10MB (application requires 10MB minimum)";
}

if ($postMaxSizeBytes < 200 * 1024 * 1024) {
    $warnings[] = "WARNING: post_max_size is less than 200MB (application requires 200MB for batch uploads)";
}

if ($postMaxSizeBytes <= $uploadMaxFilesizeBytes) {
    $warnings[] = "WARNING: post_max_size should be larger than upload_max_filesize";
}

if ($memoryLimitBytes < 256 * 1024 * 1024) {
    $warnings[] = "WARNING: memory_limit is less than 256MB (recommended for image processing)";
}

if (!empty($warnings)) {
    echo "Configuration Warnings:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ {$warning}\n";
    }
    echo "\n";
    echo "To fix these warnings, update your php.ini file:\n";
    echo "  upload_max_filesize = 10M\n";
    echo "  post_max_size = 200M\n";
    echo "  memory_limit = 256M\n";
    echo "\n";
} else {
    echo "✓ All configuration values are within recommended ranges.\n";
}

echo "\nRemember to rebuild the frontend after updating this configuration:\n";
echo "  cd frontend && npm run build\n";
echo "\n";

