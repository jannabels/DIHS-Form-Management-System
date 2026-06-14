<?php
/**
 * Check if ZipArchive extension is enabled
 * Run this file in your browser to check the status
 */

echo "<h2>PHP ZipArchive Extension Check</h2>";

if (class_exists('ZipArchive')) {
    echo "<p style='color: green; font-weight: bold;'>✓ ZipArchive extension is ENABLED</p>";
    echo "<p>Your system is ready to use PhpSpreadsheet with Excel files.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ ZipArchive extension is NOT ENABLED</p>";
    echo "<h3>To enable ZipArchive in XAMPP:</h3>";
    echo "<ol>";
    echo "<li>Locate your php.ini file. Common locations:<ul>";
    echo "<li>C:\\xampp\\php\\php.ini</li>";
    echo "<li>Or check: " . php_ini_loaded_file() . "</li>";
    echo "</ul></li>";
    echo "<li>Open php.ini in a text editor (as Administrator if needed)</li>";
    echo "<li>Search for: <code>;extension=zip</code></li>";
    echo "<li>Remove the semicolon to uncomment it: <code>extension=zip</code></li>";
    echo "<li>Save the file</li>";
    echo "<li>Restart Apache in XAMPP Control Panel</li>";
    echo "<li>Refresh this page to verify</li>";
    echo "</ol>";
    
    echo "<h3>Alternative: Check current php.ini location</h3>";
    $ini_file = php_ini_loaded_file();
    if ($ini_file) {
        echo "<p>Your php.ini file is located at: <strong>" . htmlspecialchars($ini_file) . "</strong></p>";
        echo "<p>You can open this file and search for 'extension=zip'</p>";
    } else {
        echo "<p>Could not determine php.ini location automatically.</p>";
    }
}

echo "<hr>";
echo "<h3>PHP Information</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded php.ini: " . (php_ini_loaded_file() ?: 'Not found') . "</p>";
echo "<p>Additional php.ini files: " . (php_ini_scanned_files() ?: 'None') . "</p>";

echo "<hr>";
echo "<h3>All Loaded Extensions</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
echo "<ul>";
foreach ($extensions as $ext) {
    $highlight = ($ext === 'zip') ? " style='color: green; font-weight: bold;'" : "";
    echo "<li{$highlight}>" . htmlspecialchars($ext) . "</li>";
}
echo "</ul>";

if (!in_array('zip', $extensions)) {
    echo "<p style='color: red;'><strong>Note: 'zip' extension is not in the list of loaded extensions.</strong></p>";
}
?>

