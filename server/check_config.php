<?php
/**
 * PHP Configuration Check Script
 * Verifies all required PHP extensions are available
 */

echo "Checking PHP Configuration...\n\n";

$required_extensions = ['pdo_sqlite', 'gd', 'mbstring'];
$all_good = true;

echo "PHP Version: " . PHP_VERSION . " (" . (version_compare(PHP_VERSION, '7.4.0') >= 0 ? '✓' : '✗') . ")\n\n";

echo "Required Extensions:\n";
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "  - $ext: " . ($loaded ? '✓ Loaded' : '✗ Not loaded') . "\n";
    if (!$loaded) {
        $all_good = false;
    }
}

echo "\nGD Information:\n";
if (extension_loaded('gd')) {
    $gd_info = gd_info();
    echo "  - GD Version: " . $gd_info['GD Version'] . "\n";
    echo "  - JPEG Support: " . ($gd_info['JPEG Support'] ? '✓' : '✗') . "\n";
    echo "  - PNG Support: " . ($gd_info['PNG Support'] ? '✓' : '✗') . "\n";
    echo "  - GIF Support: " . ($gd_info['GIF Read Support'] && $gd_info['GIF Create Support'] ? '✓' : '✗') . "\n";
    echo "  - WebP Support: " . (isset($gd_info['WebP Support']) && $gd_info['WebP Support'] ? '✓' : '✗') . "\n";
}

echo "\n" . ($all_good ? "✓ All requirements met!" : "✗ Some requirements are missing!") . "\n";

exit($all_good ? 0 : 1);




