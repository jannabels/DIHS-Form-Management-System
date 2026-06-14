<?php
/**
 * Cross-Browser Compatibility Layer
 * This file ensures consistent behavior across different browsers
 */

// Set content type and character encoding
header('Content-Type: text/html; charset=utf-8');

// Add security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Detect browser and version
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_ie = (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident/') !== false);
$is_edge = (strpos($user_agent, 'Edg/') !== false);
$is_firefox = (strpos($user_agent, 'Firefox/') !== false);
$is_chrome = (strpos($user_agent, 'Chrome/') !== false);
$is_safari = (strpos($user_agent, 'Safari/') !== false && !$is_chrome);
$is_mobile = (preg_match('/(android|iphone|ipad|ipod)/i', $user_agent) === 1);

// Define browser-specific constants
define('IS_IE', $is_ie);
define('IS_EDGE', $is_edge);
define('IS_FIREFOX', $is_firefox);
define('IS_CHROME', $is_chrome);
define('IS_SAFARI', $is_safari);
define('IS_MOBILE', $is_mobile);

/**
 * Add vendor prefixes to CSS properties
 */
function vendorPrefix($property, $value) {
    $prefixes = ['-webkit-', '-moz-', '-ms-', '-o-', ''];
    $result = [];
    
    foreach ($prefixes as $prefix) {
        // Skip -ms- for some properties that don't need it
        if ($prefix === '-ms-' && in_array($property, ['transition', 'transform', 'flex'])) {
            continue;
        }
        
        $result[] = $prefix . $property . ':' . $value;
    }
    
    return implode(';', $result);
}

/**
 * Get CSS with vendor prefixes
 */
function getPrefixedCSS($styles) {
    $output = [];
    
    foreach ($styles as $property => $value) {
        if (in_array($property, ['transition', 'transform', 'appearance', 'user-select', 'flex', 'flex-grow', 'flex-shrink', 'flex-basis'])) {
            $output[] = vendorPrefix($property, $value);
        } else {
            $output[] = "$property:$value";
        }
    }
    
    return implode(';', $output);
}

/**
 * Add JavaScript polyfills for older browsers
 */
function addPolyfills() {
    $polyfills = [
        'https://polyfill.io/v3/polyfill.min.js?features=default%2CArray.prototype.includes%2CArray.prototype.find%2CArray.prototype.findIndex%2CArray.prototype.some%2CArray.prototype.every%2CArray.prototype.flat%2CArray.prototype.flatMap%2CString.prototype.padStart%2CString.prototype.padEnd%2CObject.entries%2CObject.values%2CPromise.prototype.finally%2Cfetch%2CIntersectionObserver%2CResizeObserver%2CrequestIdleCallback',
        'https://cdnjs.cloudflare.com/ajax/libs/core-js/3.25.0/minified.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/classlist/1.2.20171210/classList.min.js',
        'https://cdnjs.cloudflare.com/ajax/libs/intersection-observer/0.12.0/intersection-observer.min.js'
    ];
    
    $output = [];
    foreach ($polyfills as $polyfill) {
        $output[] = '<script src="' . htmlspecialchars($polyfill) . '" defer></script>';
    }
    
    // Add a modernizr-like feature detection
    $output[] = '<script>
    window.browserFeatures = {
        passiveEvents: (function() {
            var supportsPassive = false;
            try {
                var opts = Object.defineProperty({}, "passive", {
                    get: function() { supportsPassive = true; }
                });
                window.addEventListener("testPassive", null, opts);
                window.removeEventListener("testPassive", null, opts);
            } catch (e) {}
            return supportsPassive;
        })(),
        touch: "ontouchstart" in window || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0,
        webp: (function() {
            var elem = document.createElement("canvas");
            if (!!(elem.getContext && elem.getContext("2d"))) {
                return elem.toDataURL("image/webp").indexOf("data:image/webp") == 0;
            }
            return false;
        })()
    };
    </script>';
    
    return implode("\n", $output);
}

/**
 * Get browser-specific HTML classes
 */
function getBrowserClasses() {
    $classes = [];
    
    if (IS_IE) $classes[] = 'browser-ie';
    if (IS_EDGE) $classes[] = 'browser-edge';
    if (IS_FIREFOX) $classes[] = 'browser-firefox';
    if (IS_CHROME) $classes[] = 'browser-chrome';
    if (IS_SAFARI) $classes[] = 'browser-safari';
    if (IS_MOBILE) $classes[] = 'browser-mobile';
    
    return implode(' ', $classes);
}

/**
 * Load JavaScript with fallbacks
 */
function loadScript($src, $fallback = '', $attributes = []) {
    $default_attrs = [
        'src' => $src,
        'defer' => true
    ];
    
    $attrs = array_merge($default_attrs, $attributes);
    
    $attr_string = '';
    foreach ($attrs as $key => $value) {
        if ($value === true) {
            $attr_string .= ' ' . $key;
        } else if ($value !== false) {
            $attr_string .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
    }
    
    $output = '<script' . $attr_string . '></script>';
    
    if (!empty($fallback)) {
        $output .= '\n<noscript>' . $fallback . '</noscript>';
    }
    
    return $output;
}

/**
 * Load stylesheet with fallback
 */
function loadStylesheet($href, $media = 'all', $fallback = '') {
    $output = [
        '<link rel="preload" href="' . htmlspecialchars($href) . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">',
        '<noscript><link rel="stylesheet" href="' . htmlspecialchars($href) . '" media="' . htmlspecialchars($media) . '"></noscript>'
    ];
    
    if (!empty($fallback)) {
        $output[] = $fallback;
    }
    
    return implode("\n", $output);
}

// Add browser-specific body class
function addBrowserBodyClass() {
    echo '<script>
    document.documentElement.classList.add("js");
    document.body.classList.add("' . str_replace(' ', '", "', getBrowserClasses()) . '");
    </script>';
}

// Initialize browser compatibility features
addBrowserBodyClass();
?>
