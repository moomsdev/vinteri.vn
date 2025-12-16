<?php
/**
 * Asset helpers.
 *
 * @package WPEmergeTheme
 */

use WPEmergeTheme\Facades\Theme;
use WPEmergeTheme\Facades\Assets;

/**
 * Enhanced asset loading with performance optimizations
 */
function app_action_theme_enqueue_assets()
{
    $template_dir = Theme::uri();
    $version = wp_get_theme()->get('Version');

    /**
     * Enqueue the built-in comment-reply script for singular pages.
     */
    if (is_singular()) {
        wp_enqueue_script('comment-reply');
    }

    /**
     * Critical JS (inline or very small) - load in head for critical functionality
     */
    if (file_exists(get_template_directory() . '/dist/critical.js')) {
        wp_enqueue_script('theme-critical-js', $template_dir . '/dist/critical.js', [], $version, false);
    }

    /**
     * Main JavaScript bundle (deferred)
     */
    // Main theme JS bundle - using wp_enqueue_script with full path
    wp_enqueue_script(
        'theme-js-bundle',
        $template_dir . '/dist/theme.js',
        ['jquery'],
        $version,
        true
    );
    
    // Localize AJAX data for search (now bundled in theme.js)
    wp_localize_script('theme-js-bundle', 'ajaxData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('search_nonce'),
    ]);

    // Archive page scripts
    if (is_home() || is_archive() || is_search()) {
    if (file_exists(get_template_directory() . '/dist/archive.js')) {
        wp_enqueue_script('theme-archive-js', $template_dir . '/dist/archive.js', ['theme-js-bundle'], $version, true);
    }
}

    if (is_single() && comments_open()) {
        if (file_exists(get_template_directory() . '/dist/comments.js')) {
            wp_enqueue_script('theme-comments-js', $template_dir . '/dist/comments.js', ['theme-js-bundle'], $version, true);
        }
    }

    /**
     * Enqueue styles with preload optimization
     */
    Assets::enqueueStyle('theme-css-bundle', $template_dir . '/dist/styles/theme.css');

    /**
     * Conditional CSS based on page type
     */
    if (is_single()) {
        if (file_exists(get_template_directory() . '/dist/styles/single.css')) {
            wp_enqueue_style('theme-single-css', $template_dir . '/dist/styles/single.css', ['theme-css-bundle'], $version);
        }
    }

    /**
     * Enqueue theme's style.css file to allow overrides for the bundled styles.
     */
    Assets::enqueueStyle('theme-styles', get_template_directory_uri() . '/style.css');

    /**
     * Inline Critical CSS nếu tồn tại (dist/critical/*.css)
     */
    add_action('wp_head', static function () use ($template_dir) {
        $critical_candidates = [];
        if (is_front_page() || is_home()) {
            $critical_candidates[] = get_template_directory() . '/dist/critical/home.css';
        }
        if (is_singular()) {
            $critical_candidates[] = get_template_directory() . '/dist/critical/single.css';
        }
        $critical_candidates[] = get_template_directory() . '/dist/critical/common.css';

        foreach ($critical_candidates as $path) {
            if (file_exists($path)) {
                $css = file_get_contents($path);
                if (!empty($css)) {
                    echo '<style id="critical-css">' . $css . '</style>';
                    break; // inline 1 file phù hợp đầu tiên
                }
            }
        }
    }, 1);

    /**
     * Localize script with minimal data
     */
    wp_localize_script('theme-js-bundle', 'themeData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('theme_nonce'),
        'isHome' => is_home(),
        'isMobile' => wp_is_mobile(),
        'currentUrl' => get_permalink(),
    ]);
}

/**
 * Enqueue admin assets.
 *
 * @return void
 */
function app_action_admin_enqueue_assets()
{
    $template_dir = Theme::uri();

    /**
     * Enqueue styles.
     */
    Assets::enqueueStyle(
        'theme-admin-css-bundle',
        $template_dir . '/dist/styles/admin.css'
    );

    // Enqueue front-end styles in admin area
    //  Assets::enqueueStyle('theme-css-bundle', $template_dir . '/dist/styles/theme.css');
}

/**
 * Enqueue login assets.
 *
 * @return void
 */
function app_action_login_enqueue_assets()
{
    $template_dir = Theme::uri();

    /**
     * Enqueue scripts.
     */
    // Assets::enqueueScript(
    //     'theme-login-js-bundle',
    //     $template_dir . '/dist/login.js',
    //     ['jquery'],
    //     true
    // );

    /**
     * Enqueue styles.
     */
    Assets::enqueueStyle(
        'theme-login-css-bundle',
        $template_dir . '/dist/styles/login.css'
    );
}

/**
 * Enqueue editor assets.
 *
 * @return void
 */
function app_action_editor_enqueue_assets()
{
    $template_dir = Theme::uri();

    /**
     * Enqueue scripts.
     */
    Assets::enqueueScript(
        'theme-editor-js-bundle',
        $template_dir . '/dist/editor.js',
        ['jquery'],
        true
    );

    /**
     * Enqueue styles.
     */
    Assets::enqueueStyle(
        'theme-editor-css-bundle',
        $template_dir . '/dist/styles/editor.css'
    );
}

/**
 * Add favicon proxy.
 *
 * @return void
 * @link WPEmergeTheme\Assets\Assets::addFavicon()
 */
function app_action_add_favicon()
{
    Assets::addFavicon();
}

/**
 * Advanced script optimization with defer/async/preload
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    // Scripts to defer (non-critical)
    $defer_scripts = [
        'theme-js-bundle',
        'theme-admin-js-bundle',
        'theme-login-js-bundle',
        'theme-editor-js-bundle',
        'theme-archive-js',
        'theme-comments-js'
    ];

    // Scripts to async (tracking, analytics)
    $async_scripts = [
        'google-analytics',
        'facebook-pixel',
        'hotjar'
    ];

    // Scripts to preload (critical)
    $preload_scripts = [
        'theme-critical-js'
    ];

    if (in_array($handle, $defer_scripts)) {
        return str_replace('<script ', '<script defer ', $tag);
    }

    if (in_array($handle, $async_scripts)) {
        return str_replace('<script ', '<script async ', $tag);
    }

    if (in_array($handle, $preload_scripts)) {
        // Add preload hint for critical scripts
        echo '<link rel="preload" href="' . $src . '" as="script">';
    }

    return $tag;
}, 10, 3);

/**
 * Advanced style optimization with preload
 */
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    // Non-critical styles to load asynchronously
    $non_critical_styles = [
        'theme-single-css',
        'fontawesome',
        'google-fonts'
    ];

    // Critical styles to preload
    $critical_styles = [
        'theme-css-bundle'
    ];

    if (in_array($handle, $non_critical_styles)) {
        // Load non-critical CSS asynchronously
        return '<link rel="preload" href="' . $href . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'" id="' . $handle . '">' .
            '<noscript><link rel="stylesheet" href="' . $href . '"></noscript>';
    }

    if (in_array($handle, $critical_styles)) {
        // Add preload for critical CSS
        echo '<link rel="preload" href="' . $href . '" as="style">';
    }

    return $tag;
}, 10, 3);

/**
 * Enhanced resource hints for performance
 * Optimized: preconnect (critical, max 3), dns-prefetch (less critical), prefetch (navigation)
 */
add_filter('wp_resource_hints', function ($hints, $relation_type) {
    // Preconnect: Chỉ cho critical origins (MAX 3 để tránh overhead)
    if ('preconnect' === $relation_type) {
        // Google Fonts (critical nếu dùng)
        $hints[] = [
            'href' => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous'
        ];
        
        // Thêm từ options nếu admin config
        $custom_preconnect = get_option('_custom_preconnect_domains', '');
        if (!empty($custom_preconnect)) {
            $domains = array_filter(array_map('trim', explode("\n", $custom_preconnect)));
            foreach (array_slice($domains, 0, 2) as $domain) { // Max 2 thêm
                $hints[] = [
                    'href' => 'https://' . $domain,
                    'crossorigin' => 'anonymous'
                ];
            }
        }
    }

    // DNS-Prefetch: Cho less critical domains
    if ('dns-prefetch' === $relation_type) {
        $dns_domains = [
            '//fonts.googleapis.com',
            '//cdnjs.cloudflare.com',
        ];
        
        // Thêm từ options
        $custom_dns = get_option('_custom_dns_prefetch_domains', '');
        if (!empty($custom_dns)) {
            $domains = array_filter(array_map('trim', explode("\n", $custom_dns)));
            foreach ($domains as $domain) {
                $dns_domains[] = '//' . $domain;
            }
        }
        
        $hints = array_merge($hints, $dns_domains);
    }

    // Prefetch: Likely navigation targets
    if ('prefetch' === $relation_type) {
        if (is_home() || is_front_page()) {
            // Prefetch blog page
            $blog_page_id = get_option('page_for_posts');
            if ($blog_page_id) {
                $hints[] = get_permalink($blog_page_id);
            }
        }
        
        if (is_singular()) {
            // Prefetch next/prev post
            $next_post = get_next_post();
            if ($next_post) {
                $hints[] = get_permalink($next_post->ID);
            }
        }
    }

    return $hints;
}, 10, 2);

// Hook vào action để enqueue assets thông qua function có sẵn thay vì thêm action mới
add_action('wp_enqueue_scripts', 'app_action_theme_enqueue_assets');

/**
 * Thêm manifest vào head nếu tồn tại
 */
add_action('wp_head', static function () {
    // Ưu tiên manifest trong dist/, nếu không có thì dùng bản nguồn ở resources/pwa/
    $dist_path = get_template_directory() . '/dist/manifest.json';
    $dist_uri  = get_template_directory_uri() . '/dist/manifest.json';
    $src_path  = get_template_directory() . '/resources/pwa/manifest.json';
    $src_uri   = get_template_directory_uri() . '/resources/pwa/manifest.json';

    if (file_exists($dist_path)) {
        echo '<link rel="manifest" href="' . esc_url($dist_uri) . '">';
        echo '<meta name="theme-color" content="#111">';
        return;
    }

    if (file_exists($src_path)) {
        echo '<link rel="manifest" href="' . esc_url($src_uri) . '">';
        echo '<meta name="theme-color" content="#111">';
    }
}, 2);

/**
 * Đăng ký Service Worker ở footer nếu tồn tại
 */
add_action('wp_footer', static function () {
    // Ưu tiên SW ở dist/, fallback sang resources/pwa/ khi dev
    $dist_path = get_template_directory() . '/dist/sw.js';
    $dist_uri  = get_template_directory_uri() . '/dist/sw.js';
    $src_path  = get_template_directory() . '/resources/pwa/sw.js';
    $src_uri   = get_template_directory_uri() . '/resources/pwa/sw.js';

    $sw_uri = '';
    if (file_exists($dist_path)) {
        $sw_uri = $dist_uri;
    } elseif (file_exists($src_path)) {
        $sw_uri = $src_uri;
    }

    if ($sw_uri) {
        echo '<script>if("serviceWorker" in navigator){window.addEventListener("load",function(){navigator.serviceWorker.register("' . esc_js($sw_uri) . '");});}</script>';
    }
}, 100);
