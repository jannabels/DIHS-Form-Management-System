<?php
/**
 * Frontend Performance Optimizations
 * This file contains functions to optimize frontend performance
 */

/**
 * Minify HTML output
 * @param string $buffer The HTML content to minify
 * @return string Minified HTML
 */
function minifyHtml($buffer) {
    if (strpos($buffer, '<pre>') !== false) {
        return $buffer;
    }
    
    $search = [
        '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
        '/[^\S ]+\</s',     // strip whitespaces before tags, except space
        '/(\s)+/s',         // shorten multiple whitespace sequences
        '/<!--(.|\s)*?-->/' // Remove HTML comments
    ];
    
    $replace = ['>', '<', '\\1', ''];
    $buffer = preg_replace($search, $replace, $buffer);
    
    return $buffer;
}

/**
 * Load JavaScript files asynchronously or deferred
 * @param string $url The URL of the script
 * @param string $type The type of loading ('async' or 'defer')
 * @return string HTML script tag
 */
function loadScript($url, $type = 'defer') {
    return '<script src="' . htmlspecialchars($url) . '" ' . $type . '></script>' . "\n";
}

/**
 * Load CSS files asynchronously
 * @param string $url The URL of the stylesheet
 * @return string HTML link tag with preload
 */
function loadStylesheet($url) {
    $html = '<link rel="preload" href="' . htmlspecialchars($url) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . "\n";
    $html .= '<noscript><link rel="stylesheet" href="' . htmlspecialchars($url) + '"></noscript>' . "\n";
    return $html;
}

/**
 * Add lazy loading to images
 * @param string $html The HTML content
 * @return string HTML with lazy loading added to images
 */
function addLazyLoading($html) {
    return preg_replace('/<img(.*?)>/i', '<img$1 loading="lazy">', $html);
}

/**
 * Optimize images by adding width and height attributes
 * @param string $html The HTML content
 * @param int $defaultWidth Default width if not specified
 * @param int $defaultHeight Default height if not specified
 * @return string HTML with optimized images
 */
function optimizeImages($html, $defaultWidth = 800, $defaultHeight = 600) {
    return preg_replace_callback(
        '/<img((?![^>]*width=)[^>]*)>/i',
        function($matches) use ($defaultWidth, $defaultHeight) {
            if (strpos($matches[1], 'width=') === false) {
                return '<img width="' . $defaultWidth . '" height="' . $defaultHeight . '" ' . $matches[1] . '>';
            }
            return $matches[0];
        },
        $html
    );
}

/**
 * Add resource hints for faster page loads
 * @return string HTML with resource hints
 */
function addResourceHints() {
    $hints = [
        // Preconnect to external domains
        '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>',
        '<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>',
        '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>',
        
        // Preload critical resources
        '<link rel="preload" href="/assets/css/critical.css" as="style">',
        '<link rel="preload" href="/assets/js/main.js" as="script">',
        
        // Prefetch likely next pages
        '<link rel="prefetch" href="/dashboard" as="document">',
        '<link rel="prefetch" href="/profile" as="document">'
    ];
    
    return implode("\n", $hints);
}

/**
 * Defer non-critical CSS
 * @param string $html The HTML content
 * @return string HTML with deferred CSS
 */
function deferNonCriticalCss($html) {
    // Match all link tags with rel="stylesheet"
    $pattern = '/<link(?!.*preload)(?=.*\srel=[\'\"]?stylesheet[\'\"]?)(.*?)>/i';
    $replacement = '<link rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" $1>';
    
    return preg_replace($pattern, $replacement, $html);
}

/**
 * Check if the current page is an admin page
 * @return bool True if current page is an admin page
 */
function is_admin() {
    $admin_dirs = ['/admin/', '/dashboard/', '/manage/'];
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    foreach ($admin_dirs as $dir) {
        if (strpos($current_path, $dir) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if current page is a login page
 * @return bool True if current page is a login page
 */
function is_login_page() {
    $login_pages = ['/login', '/signin', '/auth'];
    $current_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    foreach ($login_pages as $page) {
        if (strpos($current_path, $page) !== false) {
            return true;
        }
    }
    
    return false;
}

// Start output buffering with minification for non-admin and non-login pages
if (!is_admin() && !is_login_page()) {
    if (function_exists('ob_start') && !in_array(ob_get_status()['name'], ['ob_gzhandler', 'zlib output compression'])) {
        ob_start('minifyHtml');
    }
}
?>
