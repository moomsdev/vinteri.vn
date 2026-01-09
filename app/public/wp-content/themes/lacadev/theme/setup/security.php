<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Security Headers & Hardening
 * 
 * @package LacaDev
 */

/**
 * Add HTTP Security Headers
 */
add_action('send_headers', function() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME-sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // XSS Protection (legacy browsers)
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy (adjust as needed for your site)
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://images.dmca.com https://apis.google.com blob:; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com data:; ";
    $csp .= "connect-src 'self' https://www.youtube.com https://www.google-analytics.com https://stats.g.doubleclick.net https://apis.google.com ws: wss: webpack:; ";
    $csp .= "frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com https://docs.google.com https://www.google.com; ";
    $csp .= "media-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com; ";
    $csp .= "img-src 'self' data: https: http:; ";
    $csp .= "worker-src 'self' blob:; ";
    $csp .= "frame-ancestors 'self';";
    
    header("Content-Security-Policy: " . $csp);
    
    // Permissions Policy (Feature Policy)
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
});

/**
 * Remove WordPress version from head
 */
remove_action('wp_head', 'wp_generator');

/**
 * Disable XML-RPC if not needed
 */
add_filter('xmlrpc_enabled', '__return_false');

/**
 * Remove WP version from RSS feeds
 */
add_filter('the_generator', '__return_empty_string');

/**
 * Disable file editing in admin
 */
if (get_option('_hide_theme_editor') === 'yes' && !defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

/**
 * Limit login attempts (basic implementation)
 */
add_filter('authenticate', function($user, $username, $password) {
    if (empty($username) || empty($password)) {
        return $user;
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'login_attempts_' . md5($ip);
    $attempts = get_transient($transient_key);
    
    if ($attempts && $attempts >= 5) {
        return new WP_Error(
            'too_many_attempts',
            __('Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút.', 'laca')
        );
    }
    
    // If login fails, increment counter
    if (is_wp_error($user)) {
        $attempts = $attempts ? $attempts + 1 : 1;
        set_transient($transient_key, $attempts, 15 * MINUTE_IN_SECONDS);
    }
    
    return $user;
}, 30, 3);

/**
 * Clear login attempts on successful login
 */
add_action('wp_login', function($username) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'login_attempts_' . md5($ip);
    delete_transient($transient_key);
});
